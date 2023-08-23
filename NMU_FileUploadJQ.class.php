<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class FileUploadJQ
{
	private $strObjName = "";
	private $strDirHTTP = "";
	private $strDir = "";
	private $strStagingDir = "";
	private $strUploadDir = "";
	private $strBaseDirHTTP = "";
	private $strSizeDir = "";
	private $strThumbDir = "";
	private $strShortDir = "";
	private $strBaseDir = "";
	private $aFiles = [];
	private $aAllowedPer = [];

	private $strDB = "";
	private $strTableName = "";
	private $strFullTable = "";
	private $strDBFieldName = "";
	private $classSqlQuery;
	private $classScreen;
	private $bIsConfigured = false;
	private $strImageMsg = "";
	private $bSaveOnFinish = "";
	private $aPrefixesUsed = "";
	private $aToDelete = [];
	private $classSession;

	function __construct($strObjName, $bReset)
	{
		try
		{
			$this->classSession = new SessionMgmt();

			$classNameValidation = new ObjectNameMgmt();
			$classNameValidation->ObjectNameMgmt_VarifyNameIsUnique($strObjName);

			$this->strObjName = $strObjName;
			$this->classScreen = new ScreenElements($strObjName."_FileObject", true);

			$this->classSqlQuery = new SqlDataQueries();

			if (!$bReset)
			{
				$this->FileUploadJQ_SelfLoad();
			}

			$this->strImageMsg = "<p>A caption is required for every image uploaded to Campus Connect.  Captions are used to succinctly describe the content conveyed by the image.  The provided caption will also be used as alternative text for the image in order to meet web accessibility standards.</p>";
			$this->strStagingDir = "/htdocs/Webb/MiscUploads/Temporary/";
			$this->strUploadDir = "/htdocs/cmsphp/Includes/Libraries/JS/jQuery-FileUpload/server/php/files/";
			$this->strThumbDir = "thumbnail/";
			$this->strBaseDirHTTP = "https://".$_SERVER["HTTP_HOST"]."/Webb/MiscUploads/";
			$this->strBaseDir = "/htdocs/Webb/MiscUploads/";
			$this->aPrefixesUsed = [];

			$this->FileUploadJQ_CleanUploadsDir();
			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_Configure($classSqlQuery, $strDB, $strTableName, $strStoreName, $bSaveToDBOnComplete)
	{
		try
		{
			if ($classSqlQuery != "")
			{
				$this->classSqlQuery = $classSqlQuery;
			}
			else
			{
				$this->classSqlQuery = new SqlDataQueries();
			}

			if ($strDB == "")
			{
				$this->strDB = "www_webadmin";
			}
			else
			{
				$this->strDB = $strDB;
			}

			if ($strStoreName == "")
			{
				$strStoreName = $this->strObjName;
			}

			$this->strTableName = $strTableName;
			$this->strFullTable = $this->strDB.".".$this->strTableName;
			$this->strDirHTTP = "https://".$_SERVER["HTTP_HOST"]."/Webb/MiscUploads/".$strStoreName.'/';
			$this->strDir = "/htdocs/Webb/MiscUploads/".$strStoreName.'/';
			$this->strShortDir = $strStoreName.'/';
			$this->strSizeDir = "Sizes";
			$this->bSaveOnFinish = $bSaveToDBOnComplete;
			$this->strDBFieldName = "FileUploadJQ_".$strStoreName;

			if (!file_exists($this->strDir))
			{
				if (!mkdir($this->strDir))
				{
					throw new Exception('Unable to make directory '.$this->strDir.'.');
				}
			}
			chmod($this->strDir, 0777);

			if ($this->strTableName != "" && $this->strDBFieldName != "")
			{
				$this->FileUploadJQ_CreateField();
			}

			if (!file_exists($this->strDir.$this->strThumbDir))
			{
				if (!mkdir($this->strDir.$this->strThumbDir))
				{
					throw new Exception('Unable to make directory '.$this->strDir.$this->strThumbDir.'.');
				}
			}
			chmod($this->strDir.$this->strThumbDir, 0777);

			$this->bIsConfigured = true;

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetNumberFiles($iID)
	{
		try
		{
			if ($iID != "")
			{
				$aFiles = $this->FileUploadJQ_GetFiles($iID);
			}
			else
			{
				$aFiles = $this->aFiles;
			}

			$iCount = 0;
			foreach ($aFiles as $strPrefix => $strJunk)
			{
				foreach ($aFiles[$strPrefix] as $aItem)
				{
					if ($aItem['FileName'] != "")
					{
						$iCount++;
					}
				}
			}
			return $iCount;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetNumberFilesPrefix($strPrefix, $iID)
	{
		try
		{
			if ($strPrefix == "")
			{
				throw new Exception("FileUploadJQ_GetNumberFilesDBPrefix requires an Prefix.");
			}

			$aFiles = $this->FileUploadJQ_GetFilesPrefix($strPrefix, $iID);

			$iCount = 0;
			foreach ($aFiles[$strPrefix] as $aItem)
			{
				if ($aItem['FileName'] != "")
				{
					$iCount++;
				}
			}
			return $iCount;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_StoreToDBOnCompletion($strFieldName, $iID)
	{
		try
		{
			$this->FileUploadJQ_CreateField();

			if (($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] == "image" && $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption'] != "") || $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption']['ActualType'] != "image")
			{
				if ($iID == "")
				{
					$strQuery = "INSERT INTO ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".addslashes(serialize($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)]))."'";
					$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

					$iID = $aResults['ID'];
				}
				else
				{
					$strQuery = "SELECT ".addslashes($this->strDBFieldName)." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
					$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
					$aTempFiles = unserialize($aResults[0][$this->strDBFieldName]);
					$aTempFiles[$strFieldName] = $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName];

					$strQuery = "UPDATE ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".addslashes(serialize($aTempFiles))."' WHERE ID=".$iID;
					$this->classSqlQuery->MySQL_Queries($strQuery);
				}
			}

			$this->FileUploadJQ_StoreSelf();

			return $iID;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_DisplayUploaders($strPrefix, $strText, $strAllowedType, $bAddClear, $strDisplayImage, $iLoadFromDBID, $iNumberToShow)
	{
		try
		{
			if ($this->bIsConfigured == false)
			{
				print'<div style="color:red">Please configure object before use.</div>';
			}
			elseif (substr($strPrefix, strlen($strPrefix) - 1, 1) == "-")
			{
				throw new exception("(FileUploadJQ_DisplayUploaders) Prefix must not end in a dash ('-')");
			}
			elseif (in_array($strPrefix, $this->aPrefixesUsed))
			{
				print'<div style="color:red">The prefix '.$strPrefix.' is already in use.</div>';
			}
			else
			{
				$this->aAllowedPer[$strPrefix] = $iNumberToShow;

				print'<style> #'.$strPrefix.' { display:none; } </style>';

				$strScript = '
					if (typeof $aAllUploaders == "undefined")
					{
						$aAllUploaders = [];
						$aAllNumberAllowed = [];
					}
					
					$aAllUploaders.push("'.$strPrefix.'");
					$aAllNumberAllowed.push("'.$iNumberToShow.'");
				';
				ProcessJavascript::ProcessJavascript_WrapOutput($strScript);

				print'<div id="'.$strPrefix.'" class="UploadMultiDispWrapper">';
				for ($I = 1; $I <= $iNumberToShow; $I++)
				{
					$this->FileUploadJQ_DisplayUploader($this->FileUploadJQ_FieldNameMake($strPrefix, $I, $iNumberToShow), $strText, $strAllowedType, $bAddClear, $strDisplayImage, $iLoadFromDBID);
				}
				print'</div>';
				if ($bAddClear)
				{
					print'<div style="clear:both"></div>';
				}

				$bShownAtLeastOne = false;
				foreach ($this->FileUploadJQ_GetFilesPrefix($strPrefix, "") as $strName => $aValues)
				{
					if ($bShownAtLeastOne && $aValues['FileName'] == "")
					{
						$strScript = ' document.getElementById("'.$strName.'UploadWrapper").style.display = "none"; ';
						ProcessJavascript::ProcessJavascript_WrapOutput($strScript);
					}
					elseif ($aValues['FileName'] == "")
					{
						$bShownAtLeastOne = true;
					}
				}

				print'<style> #'.$strPrefix.' { display:inline-block; } </style>';
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_DisplayUploader($strFieldName, $strText, $strAllowedType, $bAddClear, $strDisplayImage, $iLoadFromDBID)
	{
		try
		{
			if (!isset($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]) || count($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]) == 0 || !isset($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName']) ||
				($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'] != "" && !file_exists($this->strDir.$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'])) ||
				((!isset($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption']) || $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption'] == "") && (isset($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName']) && $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'] != "" && exif_imagetype($this->strDir.$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName']) > 0)))
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
			}

			if ($iLoadFromDBID != "")
			{
				$this->FileUploadJQ_LoadFromDBField($strFieldName, $iLoadFromDBID);
			}

			if ($strAllowedType == "")
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['AllowedType'] = "any";
			}
			else
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['AllowedType'] = $strAllowedType;
			}

			$strScript = '
			jQuery(document).ready(function() { 
				jQuery("#'.$strFieldName.'").fileupload({
					dataType: "json",
					change: function (e, data) { },
					submit: function (e, data) { jQuery("body").css( "cursor", "wait" ); },
					start: function (e, data) { FileUploadJQ_UploadStart("'.$strFieldName.'"); },
					send: function (e, data) {  },
					progressall: function (e, data) { FileUploadJQ_ProgressAll("'.$strFieldName.'", data); },
					done: function (e, data) { FileUploadJQ_ProcessjQueryUploadResponse("'.$strFieldName.'", data, "'.$this->strObjName.'", "'.$this->strShortDir.'", "'.$this->strDirHTTP.'"); }, 
					error: function (e, data) { console.log("Error") }
				});

				$strFileName = "'.$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'].'";
				$strCaption = "'.$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption'].'";
				$strImageField = "";
				$strActualType = "'.$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'].'";
				FileUploadJQ_ConfigureInitial("'.$strFieldName.'", $strFileName, $strCaption, $strActualType, "'.$strDisplayImage.'", "'.$this->strDirHTTP.'");
			});
			';
			ProcessJavascript::ProcessJavascript_WrapOutput($strScript);

			print'<div class="UploadWrapper" id="'.$strFieldName.'UploadWrapper">';
			print'<div class="UploadParentDiv" id="'.$strFieldName.'UploadParentDiv">';
			$strAccept = "";
			if ($strAllowedType == "image")
			{
				$strAccept = 'accept="image/*"';
			}

			print'<button id="'.$strFieldName.'UploadButton" class="btn btn-default glyphicon glyphicon-upload" onclick="return false;" onMouseOver="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'darkgrey\', \'UploadButton\')" onMouseOut="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'inherit\', \'UploadButton\')"></button>';
			print'<input title="" id="'.$strFieldName.'" '.$strAccept.' type="file" name="files[]" data-url="/cgi-bin/Includes/Libraries/JS/jQuery-FileUpload/server/php/" multiple class="UploadFileInput" onMouseOver="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'darkgrey\', \'UploadButton\')" onMouseOut="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'inherit\', \'UploadButton\')">';
			print'<div class="UploadName">'.$strText.'</div>';
			print'</div>';

			print'<div id="'.$strFieldName.'UploadCaptionWrapper" class="UploadCaptionWrapper">';
			print '<div class="row">'.$this->classScreen->ScreenElements_AddInputField($strFieldName."UploadCaptionField", 'Caption'.$this->classScreen->ScreenElements_RequireLabel(true), "col-md-12", false, 'onkeyup="FileUploadJQ_CaptionDisability(\''.$strFieldName.'\')" ', "caption required", "").'</div>';

			$strAddionalForCaption = 'FileUploadJQ_SaveCaption(\''.$strFieldName.'\', \''.$strDisplayImage.'\', \''.$iLoadFromDBID.'\', \''.$this->strObjName.'\', \''.$this->strShortDir.'\', \''.$this->bSaveOnFinish.'\', \''.$this->strDirHTTP.'\', \''.$this->strThumbDir.'\')';
			$this->classScreen->ScreenElements_AddButton($strFieldName."CaptionButtons", $strFieldName."UploadCaptionButton", "finish upload", '', false, $strAddionalForCaption, "");
			print $this->classScreen->ScreenElements_AddButtonGroup($strFieldName."CaptionButtons", 0);
			print'</div>';

			print'<div id="'.$strFieldName.'UploadResultWrapper" class="UploadResultWrapper">';
			print'<div id="'.$strFieldName.'UploadImage" class="UploadResultImage"></div>';
			print'<div id="'.$strFieldName.'UploadResultCaption" class="UploadResultCaption"></div>';

			print'<button id="'.$strFieldName.'UploadDelete" class="btn btn-default glyphicon glyphicon-trash" onclick="FileUploadJQ_FileUploadRemove(\''.$strFieldName.'\', \''.$this->strObjName.'\', \''.$this->strShortDir.'\'); return false;" onMouseOver="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'darkgrey\', \'UploadDelete\')" onMouseOut="FileUploadJQ_UploadFlipColor(\''.$strFieldName.'\', \'inherit\', \'UploadDelete\')"></button>';
			print'<div id="'.$strFieldName.'Result" class="UploadResult" style="padding-bottom:15px"></div>';
			print'</div>';

			print'<div id="'.$strFieldName.'Progress" class="UploadLineWrapper"> <div id="'.$strFieldName.'ProgressLine" class="UploadLine"></div> </div>';
			print'<div style="clear:both"></div>';
			print'</div>';

			if ($bAddClear)
			{
				print'<div style="clear:both"></div>';
			}

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_AddJavascriptClearUploaderFieldsAll()
	{
		try
		{
			return 'FileUploadJQ_PresetEmptyFieldsAll($aAllUploaders, $aAllNumberAllowed, "'.$this->strObjName.'", "'.$this->strShortDir.'");';
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_AddJavascriptClearUploaderFieldsPrefix($strPrefix)
	{
		try
		{
			return 'FileUploadJQ_FileUploadJQ_PresetEmptyFields("'.$this->strObjName.'", "'.$strPrefix.'", "'.$this->aAllowedPer[$strPrefix].'", "'.$this->strShortDir.'");';
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function FileUploadJQ_FieldNameFind($strPrefix, $iNumber)
	{
		try
		{
			$aPrefixFiles = $this->aFiles[$strPrefix];
			$iZerosToPad = count($aPrefixFiles) - count($iNumber);
			for ($I = 1; $I <= $iZerosToPad; $I++)
			{
				$strPrefix .= "0";
			}
			$strPrefix .= $iNumber;

			return $strPrefix;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_FieldNameMake($strPrefix, $iCurrentNumber, $iMaxNumber)
	{
		try
		{
			if (is_numeric(substr($strPrefix, strlen($strPrefix) - 1, 1)))
			{
				throw new exception("(FileUploadJQ_FieldNameMake) Prefixes cannot end in a number: ".$strPrefix);
			}
			else
			{
				while (strlen($iCurrentNumber) < strlen($iMaxNumber))
				{
					$iCurrentNumber = "0".$iCurrentNumber;
				}

				return $strPrefix.$iCurrentNumber;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_FieldNameGetPrefix($strFieldName)
	{
		try
		{
			if (!is_numeric(substr($strFieldName, strlen($strFieldName) - 1, 1)))
			{
				throw new exception("(FileUploadJQ_FieldNameGetPrefix) Fieldname must end in a number: ".$strFieldName.". You've done something wrong in the original creation of the uploaders. Fields are built off the original prefix by adding numbers. ");
			}
			if ($strFieldName != "")
			{
				$iDecrement = 1;
				while (is_numeric(substr($strFieldName, strlen($strFieldName) - $iDecrement, 1)))
				{
					$iDecrement++;
				}
				$iDecrement--;

				$strPrefix = substr($strFieldName, 0, strlen($strFieldName) - $iDecrement);

				return $strPrefix;
			}
			else
			{
				return true;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function FileUploadJQ_FieldNameGetNumberFromFieldName($strFieldName, $strPrefix)
	{
		try
		{
			if ($strFieldName == "")
			{
				throw new exception("(FileUploadJQ_FieldNameGetNumberFromFieldName) requires a field name. ");
			}
			if ($strPrefix == "")
			{
				throw new exception("(FileUploadJQ_FieldNameGetNumberFromFieldName) requires a prefix. ");
			}

			$iNumber = str_replace($strPrefix, "", $strFieldName);

			$iIncrement = 0;
			while (substr($iNumber, $iIncrement, 1) == 0)
			{
				$iIncrement++;
			}
			$iIncrement--;

			$iNumber = substr($iNumber, $iIncrement, strlen($iIncrement) - 1);

			return $iNumber;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_SavePrefixToDB($iID, $strPrefix)
	{
		try
		{
			$this->FileUploadJQ_CreateField();

			$aExistingFiles = [];

			$strQuery = "SELECT ".addslashes($this->strDBFieldName)." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
			if (count($aResults) > 0)
			{
				$aExistingFiles = $aResults[0][addslashes($this->strDBFieldName)];
			}

			$aFilesToStore = [];
			foreach ($this->aFiles[$strPrefix] as $strName => $aRow)
			{
				if (($aRow['ActualType'] == "image" && $aRow['Caption'] != "") || $aRow['ActualType'] != "image")
				{
					$aFilesToStore[$strName] = $aRow;
				}
			}
			$aExistingFiles[$strPrefix] = $aFilesToStore;

			$strSerialized = serialize($aExistingFiles);

			if ($iID == "")
			{
				$strQuery = "INSERT INTO ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".serialize($strSerialized)."'";
			}
			else
			{
				$strQuery = "UPDATE ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".addslashes($strSerialized)."' WHERE ID=".$iID;
			}
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			if ($iID == "")
			{
				$iID = $aResults['ID'];
			}

			$this->FileUploadJQ_StoreSelf();

			return $iID;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_SaveAllToDB($iID)
	{
		try
		{
			$this->FileUploadJQ_CreateField();

			$aFilesToStore = [];
			foreach ($this->aFiles as $strPrefix => $strJunk)
			{
				foreach ($this->aFiles[$strPrefix] as $strName => $aRow)
				{
					if (($aRow['ActualType'] == "image" && $aRow['Caption'] != "") || $aRow['ActualType'] != "image")
					{
						$aFilesToStore[$strPrefix][$strName] = $aRow;
					}
					else
					{
						$aFilesToStore[$strPrefix][$strName] = $this->FileUploadJQ_StubOutFileArray();
					}
				}
			}
			$strSerialized = serialize($aFilesToStore);


			if ($iID == "")
			{
				$strQuery = "INSERT INTO ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".serialize($strSerialized)."'";
			}
			else
			{
				$strQuery = "UPDATE ".addslashes($this->strFullTable)." SET ".addslashes($this->strDBFieldName)."='".addslashes($strSerialized)."' WHERE ID=".$iID;
			}
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			if ($iID == "")
			{
				$iID = $aResults['ID'];
			}

			$this->FileUploadJQ_StoreSelf();

			return $iID;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_LoadFromDBField($strFieldName, $iID)
	{
		try
		{
			if ($strFieldName == "")
			{
				throw new Exception("FileUploadJQ_LoadFromDB requires a fieldname.");
			}

			if ($iID == "")
			{
				throw new Exception("FileUploadJQ_LoadFromDB requires an ID.");
			}

			$this->FileUploadJQ_CreateField();

			$strQuery = "SELECT ".addslashes($this->strDBFieldName)." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
			$aFiles = unserialize($aResults[0][$this->strDBFieldName]);

			if (isset($aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]))
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName] = $aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName];
			}
			else
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
			}

			if (isset($aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]) && $aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'] != "")
			{
				if (exif_imagetype($this->FileUploadJQ_GetDirectoryPath($aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'])) > 0)
				{
					$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] = "image";
				}
				else
				{
					$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] = "file";
				}
			}

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_LoadFromDBAll($iID)
	{
		try
		{
			if ($iID == "")
			{
				throw new Exception("FileUploadJQ_LoadAllFromDB requires an ID.");
			}
			if ($this->strFullTable == "")
			{
				throw new Exception("FileUploadJQ_LoadAllFromDB requires an that the tablename be set in configure.");
			}


			$strQuery = "SELECT ".addslashes($this->strDBFieldName)." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
			$this->aFiles = unserialize($aResults[0][$this->strDBFieldName]);

			$this->FileUploadJQ_StoreSelf();

			return $this->aFiles;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_LoadFromDBPrefix(/** @noinspection PhpUnusedParameterInspection */
		$strPrefix, $iID)
	{
		try
		{
			print'Disabled';
			/*
			$strQuery = "SELECT ".addslashes($this->strDBFieldName)." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			$aFiles = unserialize($aResults[0][$this->strDBFieldName]);
			if(isset($aFiles[$strPrefix]))
				$this->aFiles[$strPrefix] = $aFiles[$strPrefix];
			else
				$this->aFiles[$strPrefix] = "";

			$this->FileUploadJQ_StoreSelf();

			return $this->aFiles[$strPrefix];
            */
			return "";
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_AddCaption($strFieldName, $strCaption)
	{
		try
		{
			$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['Caption'] = $strCaption;
			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_DisplayAll($iMaxWidth, $iMaxHeight, $bWriteCaption, $bAlignBottom, $bAddModal, $iLoadFromDBID, $bCrop = false)
	{
		try
		{
			if ($iLoadFromDBID != "")
			{
				$aFiles = $this->FileUploadJQ_LoadFromDBAll($iLoadFromDBID);
			}
			else
			{
				$aFiles = $this->aFiles;
			}

			print'<div class="FileUploadJQ_DisplayAll" id="FileUploadJQ_DisplayAll-wrapper">';
			foreach ($aFiles as $strPrefix => $strJunk)
			{
				foreach ($aFiles[$strPrefix] as $aFile)
				{
					if (isset($aFile['FileName']) && $aFile['FileName'] != "")
					{
						$this->FileUploadJQ_DisplayFile($this->strDir.$aFile['FileName'], $iMaxWidth, $iMaxHeight, $bWriteCaption, $aFile['Caption'], $bAlignBottom, $bAddModal, $bCrop);
					}
				}
			}
			print'</div>';
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_DisplayPrefix($strFieldName, $iMaxWidth, $iMaxHeight, $bWriteCaption, $bAlignBottom, $bAddModal, $iLoadFromDBID)
	{
		try
		{
			if ($iLoadFromDBID != "")
			{
				$this->FileUploadJQ_LoadFromDBPrefix("", $iLoadFromDBID);
			}

			foreach ($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)] as $aFile)
			{
				if (isset($aFile['FileName']) && $aFile['FileName'] != "")
				{
					print'<div class="FileUploadJQ_DisplayAll">';
					$this->FileUploadJQ_DisplayFile($this->strDir.$aFile['FileName'], $iMaxWidth, $iMaxHeight, $bWriteCaption, $aFile['Caption'], $bAlignBottom, $bAddModal);
					print'</div>';
				}
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function FileUploadJQ_DisplayField($strPrefix, $iNumber, $iMaxWidth, $iMaxHeight, $bWriteCaption, $bAlignBottom, $bAddModal, $iLoadFromDBID)
	{
		try
		{
			$iMax = $this->FileUploadJQ_GetNumberFiles($iLoadFromDBID);

			$strFieldName = $this->FileUploadJQ_FieldNameMake($strPrefix, $iNumber, $iMax);

			if (isset($this->aFiles[$strPrefix][$strFieldName]['FileName']) && $this->aFiles[$strPrefix][$strFieldName]['FileName'] != "")
			{
				$this->FileUploadJQ_DisplayFile($this->strDir.$this->aFiles[$strPrefix][$strFieldName]['FileName'], $iMaxWidth, $iMaxHeight, $bWriteCaption, $this->aFiles[$strPrefix][$strFieldName]['Caption'], $bAlignBottom, $bAddModal);
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function FileUploadJQ_DisplayFile($strFile, $iMaxWidth, $iMaxHeight, $bWriteCaption, $strCaption, $bAlignBottom, $bAddModal, $bCrop = false)
	{
		try
		{
			if ($strFile != "" && file_exists($strFile))
			{

				if ($bAlignBottom)
				{
					print'<div style="display:inline-block; padding-right:10px;">';
				}

				if (exif_imagetype($strFile) > 1)
				{
					print '<img src="'.$this->FileUploadJQ_GetHTTPPath($this->FileUploadJQ_CreateSizedImage($strFile, $iMaxWidth, $iMaxHeight, $bCrop)).'" id="'.str_replace("/", ":", $strFile).'">';

					if ($bWriteCaption && $strCaption != "")
						print'<div style="width:'.$iMaxWidth.'; text-align:center;">'.$strCaption.'</div>';
				}
				else
				{
					$strNameOnly = $strFile;
					if (strstr($strFile, "/"))
					{
						$aParts = explode("/", $strFile);
						$strNameOnly = $aParts[count($aParts) - 1];
					}

					$strFontSize = "";
					if (strlen($strNameOnly) > 25)
					{
						$strFontSize = " smaller-2015 ";
					}

					print '<div class="center-2015"><a href="'.$this->FileUploadJQ_GetHTTPPath($strFile).'" target="_blank" class="smaller-2015"><span class="glyphicon glyphicon-file fade-2015 grey-2015" style="font-size:'.round(($iMaxWidth / 3), 0).'px;"></span></a></div>';
					print '<div class="center-2015 '.$strFontSize.'" style="max-width:'.$iMaxWidth.'px"><a href="'.$this->FileUploadJQ_GetHTTPPath($strFile).'" target="_blank" class="grey-2015">'.$strNameOnly.'</a></div>';
				}

				if ($bAlignBottom)
				{
					print'</div>';
				}

				return true;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetFileAttribute($strPrefix, $iNumber, $strElementName, $iID)
	{
		try
		{
			if ($strPrefix == "")
			{
				throw new exception("FileUploadJQ_GetFileAttribute requires a prefix.");
			}
			if ($iNumber == "")
			{
				throw new exception("FileUploadJQ_GetFileAttribute requires a number.");
			}
			$aTemp = $this->FileUploadJQ_StubOutFileArray();
			if (!isset($aTemp[$strElementName]))
			{
				throw new exception("FileUploadJQ_GetFileAttribute: ".$strElementName." doesn not exist in the file object.");
			}

			if ($iID != "")
			{
				$iMax = $this->FileUploadJQ_GetAllowedPerForPrefix($strPrefix, $iID);
			}
			else
			{
				$iMax = count($this->aFiles[$strPrefix]);
			}
			$strFieldName = $this->FileUploadJQ_FieldNameMake($strPrefix, $iNumber, $iMax);

			if ($iID != "")
			{
				$strResult = "";
				$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

				if (count($aResults) > 0)
				{
					$aFiles = unserialize($aResults[0][$this->strDBFieldName]);

					if (isset($aFiles[$strPrefix][$strFieldName][$strElementName]) && $aFiles[$strPrefix][$strFieldName][$strElementName] != "")
					{
						$strResult = $aFiles[$strPrefix][$strFieldName][$strElementName];
					}
				}

				return $strResult;
			}
			else
			{
				return $this->aFiles[$strPrefix][$strFieldName][$strElementName];
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetFileType($strFieldName)
	{
		try
		{
			return $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'];
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_RemoveMarkForRemoval($strFieldName)
	{
		try
		{
			if ($strFieldName == "")
			{
				throw new exception("FileUploadJQ_RemoveMarkForRemoval requires a field name.");
			}

			$strPrefix = $this->FileUploadJQ_FieldNameGetPrefix($strFieldName);

			if ($this->aFiles[$strPrefix][$strFieldName]['FileName'] != "")
			{
				$this->aToDelete[] = $this->aFiles[$strPrefix][$strFieldName]['FileName'];
				$this->aFiles[$strPrefix][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
			}

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_RemoveUploadedFile($strPrefix, $iNumber, $iID)
	{
		try
		{
			if ($strPrefix == "")
			{
				throw new exception("FileUploadJQ_RemoveUploadedFile requires a prefix");
			}
			if ($iNumber == "")
			{
				throw new exception("FileUploadJQ_RemoveUploadedFile requires a field number");
			}

			if (count($this->aFiles[$strPrefix]) > 0)
			{
				$strFieldName = $this->FileUploadJQ_FieldNameMake($strPrefix, $iNumber, count($this->aFiles[$strPrefix]));
				if (isset($this->aFiles[$strPrefix][$strFieldName]['FileName']) && $this->aFiles[$strPrefix][$strFieldName]['FileName'] != "")
				{
					$this->FileUploadJQ_DeleteFile($this->strDir.$this->aFiles[$strPrefix][$strFieldName]['FileName'], "");
					$this->aFiles[$strPrefix][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
				}
			}

			if ($iID != "")
			{
				$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

				if (count($aResults) > 0)
				{
					$aFiles = unserialize($aResults[0][$this->strDBFieldName]);
					if (count($aFiles[$strPrefix]) > 0)
					{
						$strFieldName = $this->FileUploadJQ_FieldNameMake($strPrefix, $iNumber, count($aFiles[$strPrefix]));
						if (isset($aFiles[$strPrefix][$strFieldName]['FileName']) && $aFiles[$strPrefix][$strFieldName]['FileName'] != "")
						{
							$this->FileUploadJQ_DeleteFile($this->strDir.$aFiles[$strPrefix][$strFieldName]['FileName'], "");
						}
					}
				}

				$strQuery = "DELETE FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$this->classSqlQuery->MySQL_Queries($strQuery);
			}

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_DeleteFile($strFilename, $iID)
	{
		try
		{
			$strExtraDir = "";
			if (strstr($strFilename, "/"))
			{
				$aParts = explode("/", $strFilename);
				$strExtraDir = $aParts[0];
			}

			if ($strFilename != "")
			{
				if (file_exists($this->strDir.$strFilename))
				{
					if (!unlink($this->strDir.$strFilename))
					{
						throw new Exception('Unable to unlink file '.$this->strDir.$strFilename);
					}
				}

				if (file_exists($this->strDir.$strExtraDir))
				{
					if ($strExtraDir != "" && count(scandir($this->strDir.$strExtraDir)) == 2)
					{
						if (!rmdir($this->strDir.$strExtraDir))
						{
							throw new Exception('Unable to unlink directory '.$this->strDir.$strExtraDir);
						}
					}
				}

				if (file_exists($this->strDir.$this->strThumbDir.$strFilename))
				{
					if (!unlink($this->strDir.$this->strThumbDir.$strFilename))
					{
						throw new Exception('Unable to unlink file '.$this->strDir.$this->strThumbDir.$strFilename);
					}
				}

				if (file_exists($this->strDir.$this->strThumbDir.$strExtraDir))
				{
					if ($strExtraDir != "" && count(scandir($this->strDir.$this->strThumbDir.$strExtraDir)) == 2)
					{
						if (!rmdir($this->strDir.$this->strThumbDir.$strExtraDir))
						{
							throw new Exception('Unable to unlink directory '.$this->strDir.$this->strThumbDir.$strExtraDir);
						}
					}
				}
			}

			if ($iID != "")
			{
				$strQuery = "DELETE FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$this->classSqlQuery->MySQL_Queries($strQuery);
			}

			$this->FileUploadJQ_StoreSelf();

			return "";
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_RemoveUploadedFileAll($iID)
	{
		try
		{
			foreach ($this->aFiles as $strPrefix => $strJunk)
			{
				foreach ($this->aFiles[$strPrefix] as $strFieldName => $aValues)
				{
					$this->FileUploadJQ_RemoveUploadedFile($this->strDir.$aValues['FileName'], "", "");
					$this->aFiles[$strPrefix][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
				}
			}

			if ($iID != "")
			{
				$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

				foreach (unserialize($aResults[0][$this->strDBFieldName]) as $strPrefix => $strJunk)
				{
					foreach ($this->aFiles[$strPrefix] as $strFieldName => $aValues)
					{
						$this->FileUploadJQ_DeleteFile($this->strDir.$aValues['FileName'], "");
					}
				}

				$strQuery = "DELETE FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$this->classSqlQuery->MySQL_Queries($strQuery);
			}

			$this->FileUploadJQ_StoreSelf();

			return "";
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_ResetField($strFieldName)
	{
		try
		{
			$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();

			$this->FileUploadJQ_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_ResetFieldAll()
	{
		try
		{
			$aArray = [];
			foreach ($this->aFiles as $strPrefix => $strJunk)
			{
				foreach ($this->aFiles[$strPrefix] as $strFieldName => $strValue)
				{
					$aArray[] = $strFieldName;
					$this->aFiles[$strPrefix][$strFieldName] = $this->FileUploadJQ_StubOutFileArray();
				}
			}

			return $aArray;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_MoveFile($strFieldName, $strOrig, $strAdjustedName)
	{
		try
		{
			$strTempDirname = $this->FileUpload_GetUniquePathForFile($strOrig, $this->strShortDir);

			if (!rename($this->strUploadDir.$strAdjustedName, $this->strDir.$strTempDirname.$strOrig))
			{
				throw new Exception('Unable to rename '.$strOrig.' to '.$this->strDir.$strTempDirname.$strOrig);
			}
			chmod($this->strDir.$strTempDirname.$strOrig, 0777);

			if (file_exists($this->strUploadDir.$this->strThumbDir.$strAdjustedName))
			{
				if (!rename($this->strUploadDir.$this->strThumbDir.$strAdjustedName, $this->strDir.$this->strThumbDir.$strTempDirname.$strOrig))
				{
					throw new Exception('Unable to rename '.$strOrig.' to '.$this->strDir.$this->strThumbDir.$strTempDirname.$strOrig);
				}
			}
			chmod($this->strDir.$this->strThumbDir.$strTempDirname.$strOrig, 0777);

			$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['FileName'] = $strTempDirname.$strOrig;
			if (exif_imagetype($this->strDir.$strTempDirname.$strOrig) > 0)
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] = "image";
			}
			else
			{
				$this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] = "file";
			}

			$this->FileUploadJQ_StoreSelf();

			return "";
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetFiles($iID)
	{
		try
		{
			if ($iID != "")
			{
				$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
				if ($aResults[0][$this->strDBFieldName] != "")
				{
					return unserialize($aResults[0][$this->strDBFieldName]);
				}
				else
				{
					return "";
				}
			}
			else
			{
				return $this->aFiles;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetFilesPrefix($strPrefix, $iID)
	{
		try
		{
			if ($strPrefix == "")
			{
				throw new exception("FileUploadJQ_GetFilesPrefix requires a prefix");
			}

			if ($iID != "")
			{
				$aTemp = $this->FileUploadJQ_GetFiles($iID);
				return $aTemp[$strPrefix];
			}
			else
			{
				return $this->aFiles[$strPrefix];
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetDirectoryPath($strFile)
	{
		try
		{
			return $this->strDir.$strFile;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_CreateSizedImage($strFileName, $iMaxWidth, $iMaxHeight, $bCrop = false)
	{
		try
		{
			if ($this->FileUploadJQ_GetAcceptableDirs($strFileName))
			{
				if (file_exists($strFileName))
				{
					if (exif_imagetype($strFileName) > 0)
					{
						$aParts = explode("/", $strFileName);
						$strFileNameOnly = $aParts[count($aParts) - 1];
						$strSubDir = str_replace($strFileNameOnly, "", $strFileName);
						$strNewName = $iMaxWidth."-".$iMaxHeight."-".$strFileNameOnly;

						if (!file_exists($strSubDir.$strNewName))
						{
							$objImageMagick = new \Imagick($strFileName);
							if (!$bCrop)
							{
								$objImageMagick->thumbnailImage($iMaxWidth, $iMaxHeight, true);
							}
							else
							{
								$objImageMagick->cropThumbnailImage($iMaxWidth, $iMaxHeight);
							}
							$objImageMagick->writeImage($strSubDir.$strNewName);

							chmod($strSubDir.$strNewName, 0777);
						}

						return $this->FileUploadJQ_GetHTTPPath($strSubDir.$strNewName);
					}
					else
					{
						throw new Exception('File not an image: '.$strFileName);
					}
				}
			}
			else
			{
				throw new Exception('Files must be in cmsphp, Webb, www, web or Content to be converted.');
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetAllowedPerForPrefix($strPrefix, $iID)
	{
		try
		{
			if ($iID != "")
			{
				$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
				$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
				$aFiles = unserialize($aResults[0][$this->strDBFieldName]);

				$iCount = 0;
				if (isset($aFiles[$strPrefix]))
				{
					$iCount = count($aFiles[$strPrefix]);
				}

				return $iCount;
			}
			elseif (isset($this->aAllowedPer[$strPrefix]))
			{
				return $this->aAllowedPer[$strPrefix];
			}
			else
			{
				return 0;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_IsFieldImage($strFieldName)
	{
		try
		{
			if (isset($this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType']) && $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName]['ActualType'] == "image")
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_IsFileImage($strFile)
	{
		try
		{
			if (file_exists($strFile) && exif_imagetype($strFile) > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_FileResetUploader()
	{
		return 'FileUploadJQ_FileResetUploader("'.$this->strObjName.'");';
	}

	function FileUploadJQ_SelfDump()
	{
		try
		{
			$classDump = new Dump();
			$classDump->Display($this);
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function FileUploadJQ_SelfLoad()
	{
		try
		{
			$aSessions = $this->classSession->SessionMgmt_Select($this->strObjName);
			foreach ($aSessions as $strName => $strValue)
			{
				$this->{$strName} = $strValue;
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function FileUploadJQ_GetFieldsByPrefix($iID, $strPrefix)
	{
		try
		{
			$aPrefixResults = [];
			if ($this->strDB != "")
			{
				if ($this->strDB != "")
				{
					$strQuery = "SELECT ".$this->strDBFieldName." FROM ".addslashes($this->strFullTable)." WHERE ID=".$iID;
					$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
					$aFiles = unserialize($aResults[0][$this->strDBFieldName]);
					foreach ($aFiles[$strPrefix] as $strName => $aFileInfo)
					{
						$aPrefixResults[] = $strName;
					}
				}
				else
				{
					foreach ($this->aFiles[$strPrefix] as $strName => $aFileInfo)
					{
						$aPrefixResults[] = $strName;
					}
				}

				return $aPrefixResults;
			}
			else
			{
				throw new exception("Object not configrued for 'FileUploadJQ_GetFieldsByPrefix'. Please call 'FileUploadJQ_Configure'");
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetFilesField($strFieldName)
	{
		try
		{
			return $this->aFiles[$this->FileUploadJQ_FieldNameGetPrefix($strFieldName)][$strFieldName];
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_CreateField()
	{
		try
		{
			if ($this->strDB != "")
			{
				$strQuery = "SELECT TABLE_SCHEMA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".addslashes($this->strDB)."' LIMIT 1";
				$aDBResults = $this->classSqlQuery->MySQL_Queries($strQuery);
				if (count($aDBResults) == 0)
				{
					throw new Exception("Unable to complete FileUploadJQ_CreateField as database '".$this->strDB."' does not exist");
				}

				$strQuery = "SELECT TABLE_SCHEMA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".addslashes($this->strDB)."' AND TABLE_NAME='".addslashes($this->strTableName)."' LIMIT 1";
				$aTableResults = $this->classSqlQuery->MySQL_Queries($strQuery);
				if (count($aTableResults) == 0)
				{
					$strQuery = "CREATE TABLE IF NOT EXISTS ".addslashes($this->strFullTable)." (ID bigint(20) NOT NULL) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;";
					$this->classSqlQuery->MySQL_Queries($strQuery);

					$strQuery = "ALTER TABLE ".addslashes($this->strFullTable)." ADD PRIMARY KEY (`ID`)";
					$this->classSqlQuery->MySQL_Queries($strQuery);

					$strQuery = "ALTER TABLE ".addslashes($this->strFullTable)." MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1";
					$this->classSqlQuery->MySQL_Queries($strQuery);

					$strQuery = "ALTER TABLE ".addslashes($this->strFullTable)." ADD (".$this->strDBFieldName." text)";
					$this->classSqlQuery->MySQL_Queries($strQuery);
				}
				else
				{
					$strQuery = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".addslashes($this->strDB)."' AND TABLE_NAME='".addslashes($this->strTableName)."' AND COLUMN_NAME='".addslashes($this->strDBFieldName)."' LIMIT 1";
					$aColResults = $this->classSqlQuery->MySQL_Queries($strQuery);
					if (count($aColResults) == 0)
					{
						$strQuery = "ALTER TABLE ".addslashes($this->strFullTable)." ADD (".$this->strDBFieldName." text)";
						$this->classSqlQuery->MySQL_Queries($strQuery);
					}
				}
			}
			else
			{
				throw new exception("Object not configrued for 'FileUploadJQ_CreateField'. Please call 'FileUploadJQ_Configure'");
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_CleanUploadsDir()
	{
		try
		{
			$objDirectory = new RecursiveDirectoryIterator($this->strUploadDir);
			$objIterator = new RecursiveIteratorIterator($objDirectory);
			foreach ($objIterator as $strName => $strValue)
			{
				$aParts = explode("/", $strName);
				$strSmallName = $aParts[count($aParts) - 1];
				if ($strSmallName != "." && $strSmallName != ".." && substr($strSmallName, 0, 1) != "." && filemtime($strName) < (time() - (60 * 60)))
				{
					unlink($strName);
				}
			}
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUpload_GetUniquePathForFile($strOrig, $strSubFolder)
	{
		try
		{
			$strTempDirname = "";
			if (!file_exists($this->strBaseDir.$strSubFolder))
			{
				if (!mkdir($this->strBaseDir.$strSubFolder))
				{
					throw new Exception('GetUniquePathForFile: Unable to make directory '.$this->strBaseDir.$strSubFolder.$strTempDirname.' for file: '.$strOrig);
				}
			}

			$strBaseFolder = $this->strBaseDir.$strSubFolder;
			if (substr($this->strBaseDir.$strSubFolder, strlen($this->strBaseDir.$strSubFolder) - 1, 1) != "/")
			{
				$strBaseFolder .= "/";
			}

			if (file_exists($strBaseFolder.$strOrig) || file_exists($strBaseFolder.$this->strThumbDir.$strOrig))
			{
				$strTempDirname = CORE_GenerateUniqueNumber();
				while (file_exists($strBaseFolder.$strTempDirname) || file_exists($strBaseFolder.$this->strThumbDir.$strTempDirname))
				{
					$strTempDirname .= '0';
				}

				if (!file_exists($strBaseFolder.$strTempDirname))
				{
					if (!mkdir($strBaseFolder.$strTempDirname))
					{
						throw new Exception('GetUniquePathForFile: Unable to make directory '.$strBaseFolder.$strTempDirname.' for file: '.$strOrig);
					}
				}
				chmod($strBaseFolder.$strTempDirname, 0777);

				if (!file_exists($strBaseFolder.$this->strThumbDir))
				{
					if (!mkdir($strBaseFolder.$this->strThumbDir))
					{
						throw new Exception('GetUniquePathForFile: Unable to make directory '.$strBaseFolder.$this->strThumbDir.' for file: '.$strOrig);
					}
				}
				chmod($strBaseFolder.$this->strThumbDir, 0777);

				if (!file_exists($strBaseFolder.$this->strThumbDir.$strTempDirname))
				{
					if (!mkdir($strBaseFolder.$this->strThumbDir.$strTempDirname))
					{
						throw new Exception('GetUniquePathForFile: Unable to make directory '.$strBaseFolder.$this->strThumbDir.$strTempDirname.' for file: '.$strOrig);
					}
				}
				chmod($strBaseFolder.$this->strThumbDir.$strTempDirname, 0777);

				$strTempDirname .= '/';
			}

			return $strTempDirname;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_StubOutFileArray()
	{
		try
		{
			$aTemp = [];
			$aTemp['AllowedType'] = "";
			$aTemp['ActualType'] = "";
			$aTemp['FileName'] = "";
			$aTemp['Caption'] = "";
			return $aTemp;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function FileUploadJQ_GetHTTPPath($strFile)
	{
		try
		{
			$strHTTPPath = str_replace("/htdocs", "https://".$_SERVER['HTTP_HOST'], $strFile);
			$strHTTPPath = str_replace("cmsphp", "cgi-bin", $strHTTPPath);

			return $strHTTPPath;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_GetAcceptableDirs($strDir)
	{
		try
		{
			if (!strstr($strDir, "cmsphp") && !strstr($strDir, "Webb") && !strstr($strDir, "www") && !strstr($strDir, "web") && !strstr($strDir, "Content"))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FileUploadJQ_StoreSelf()
	{
		try
		{
			$this->classSession->SessionMgmt_Set($this->strObjName, $this);
			return true;
		}
		catch (Exception $ex)
		{
			return ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}

