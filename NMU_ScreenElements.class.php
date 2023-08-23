<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class ScreenElements
{
	# All references to this class are of the same instance. The purpose of it is to encapsulate a variety of screen objects in a persistant object
	private $aRadios = [];
	private $aChecks = [];
	private $aButtonGroups = [];

	private $strPrefix = "";
	private $aElementsDetails = [];
	private $aPrefixesUsed = [];
	private $classNameMgmt = "";
	private $classSession = "";
	private $strObjName = "";
	private $aEditors = [];
	private $strFormName = "";
	private $aIssues = [];

	function __construct($strName, $bReset)
	{
		try
		{
			$this->classSession = new SessionMgmt();
			$this->classNameMgmt = new ObjectNameMgmt();
			$this->strObjName = $strName;

			if (!$bReset)
				$this->ScreenElements_SelfLoad();
			$this->aButtonGroups = [];

			$this->ScreenElements_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_SetPrefix($strPrefix)
	{
		try
		{
			$this->aPrefixesUsed[] = $strPrefix;
			$this->strPrefix = $strPrefix."_";
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	###########################################
	############### FORM FIELDS ###############
	###########################################

	function ScreenElements_AddForm($strURL, $strFormName)
	{
		try
		{
			if ($strURL == "")
				$strURL = CORE_GetURLPublic(Const_RawURL, $_REQUEST[Const_Action], $_REQUEST[Const_Phase], $_REQUEST[Const_ElementID], "", "");

			if ($strFormName != "")
				$this->strFormName = $strFormName;
			else
				$this->strFormName = "myForm";

			$strOutput = '<form action="'.$strURL.'"  method="post" name="'.$this->strFormName.'" id="'.$this->strFormName.'" enctype="multipart/form-data">';

			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function ScreenElements_AddInputField($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional, $strPlaceHolder, $strPattern)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = $strPattern;
			$aAttributes['PlaceHolder'] = $strPlaceHolder;

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'text', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function ScreenElements_AddInputFieldUpload($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional, $strDirectory)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Directory'] = $strDirectory;
			$aAttributes['BaseFieldName'] = $strFieldName;
			$aAttributes['FieldNameExt'] = "_Uploader";
			$strFieldName .= $aAttributes['FieldNameExt'];

			$strTemp = "";
			if($_SESSION[$aAttributes['BaseFieldName']] != "")
			{
				if (strstr($_SESSION[$strFieldName], "http"))
					$strTemp = '<img id="'.$strFieldName.'_Image" src="'.$_SESSION[$aAttributes['BaseFieldName']].'">';
				else
					$strTemp = '<img id="'.$strFieldName.'_Image" src="https://'.$_SERVER["HTTP_HOST"].'/'.$_SESSION[$aAttributes['BaseFieldName']].'">';
			}
			$aAttributes['File'] = '<span id="'.$strFieldName.'_ImageWrapper">'.$strTemp.'</span>';

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'file', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	
	function ScreenElements_GetUploadFilePath($strSubDir)
	{
		$aResult = [];
		$aResult["ImageDir"] = "/htdocs/Webb/Images/".$strSubDir."/";
		$aResult["HttpImageDir"] = "/Webb/Images/".$strSubDir."/";
		$aResult["HttpFullImageDir"] = "https://".$_SERVER["HTTP_HOST"]."/Webb/Images/".$strSubDir."/";

		return $aResult;
	}


	function ScreenElements_AddInputFieldUpload_OLD($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional, $strFile)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;

			if ($strFile != "" && file_exists($strFile))
				$aAttributes['File'] = '<img src="https://'.$_SERVER["HTTP_HOST"].'/'.str_replace("/htdocs", "", $strFile).'">';

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'fileold', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function ScreenElements_AddInputFieldEmail($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = "^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$";
			$aAttributes['PlaceHolder'] = "someone@somewhere.com";

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'email', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldZip($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = "^\d{5}(?:[-\s]\d{4})?$";
			$aAttributes['PlaceHolder'] = "#####/#####-####";

			$strJava = 'ScreenElements_CompletePartialZip(\''.$strFieldName.'\')';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onchange');

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'text', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldNumber($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional, $iDecimalPlaces)
	{
		try
		{
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, "text-align:right;", "style");

			$aAttributes['DecimalPlaces'] = $iDecimalPlaces;
			$aAttributes['Step'] = 1;
			$aAttributes['PlaceHolder'] = "0";
			$aAttributes['Pattern'] = "[0-9]+([\.][0-9]+)?";
			$aAttributes['Required'] = $bRequired;

			if ($aAttributes['DecimalPlaces'] > 0)
			{
				$aAttributes['Pattern'] = "^[+-]?[0-9]+([\.][0-9]+)?";
				$aAttributes['Step'] = ($aAttributes['Step'] / pow(10, $aAttributes['DecimalPlaces']));

				$aAttributes['PlaceHolder'] .= ".";
				for ($I = 0; $I < $aAttributes['DecimalPlaces']; $I++)
				{
					$aAttributes['PlaceHolder'] .= "0";
				}
			}

			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $aAttributes['Step'], "step");
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'ScreenElements_RoundNumber(\''.$strFieldName.'\', '.$aAttributes['DecimalPlaces'].');', "onchange");

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'number', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function ScreenElements_AddInputFieldPwd($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional, $strPlaceHolder)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = "";
			if ($strPlaceHolder == "")
			{
				$aAttributes['PlaceHolder'] = "********";
			}
			else
			{
				$aAttributes['PlaceHolder'] = $strPlaceHolder;
			}

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'password', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldPhone($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = "^([0-9]{3}-[0-9]{3}-[0-9]{4}|[0-9]{10})$";
			$aAttributes['PlaceHolder'] = "xxx-xxx-xxxx";

			$strJava = ' ScreenElements_CompletePartialPhone(\''.$strFieldName.'\'); ';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onchange');

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, "tel", $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldDate($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			#if(isset($_SESSION[$strFieldName]) && !is_numeric($_SESSION[$strFieldName]))
			#	$_SESSION[$strFieldName] = $_SESSION[$strFieldName];
			if (isset($_SESSION[$strFieldName]) && is_numeric($_SESSION[$strFieldName]) && $_SESSION[$strFieldName] != 0)
				$_SESSION[$strFieldName] = date("m/d/Y", $_SESSION[$strFieldName]);
			elseif (isset($_SESSION[$strFieldName]) && $_SESSION[$strFieldName] == 0)
				$_SESSION[$strFieldName] = "";

			$aAttributes['Required'] = $bRequired;
			#$aAttributes['Pattern'] = "(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d\d";
			$aAttributes['Pattern'] = "([1-9]|0[1-9]|1[012])[- /.]([1-9]|0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d{2}$";
			$aAttributes['PlaceHolder'] = "MM/DD/YYYY";

			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'z-index:20000', "style");
			$strFieldDefinition = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'date', $strAdditional, $strColClass, $aAttributes);

			$strFunction = ' jQuery( "#'.$strFieldName.'" ).datepicker(); ';
			ProcessJavascript::ProcessJavascript_WrapOutput($strFunction);

			if ($strTitle != "")
				$strOutput = $this->ScreenElements_FormatFieldOutput($strFieldName, $strTitle, $strFieldDefinition, $strColClass, $bRequired);

			$this->ScreenElements_StoreSelf();

			if (isset($strOutput) && $strTitle != "")
				return $strOutput;
			else
				return $strFieldDefinition;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldTime($strFieldName, $strTitle, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			if (isset($_SESSION[$strFieldName]) && is_numeric($_SESSION[$strFieldName]) && $_SESSION[$strFieldName] != 0)
				$_SESSION[$strFieldName] = date("h:i a", $_SESSION[$strFieldName]);
			elseif (isset($_SESSION[$strFieldName]) && $_SESSION[$strFieldName] == 0)
				$_SESSION[$strFieldName] = "";

			$aAttributes['Required'] = $bRequired;
			$aAttributes['Pattern'] = "([01]?[0-9]|2[0-3]):[0-5][0-9] [Aa|Pp][Mm]";
			$aAttributes['PlaceHolder'] = "hh:mm am/pm";

			$strJava = ' ScreenElements_TimeSelectInit(\''.$strFieldName.'\'); ';
			ProcessJavascript::ProcessJavascript_WrapOutput($strJava);

			$strJava = ' ScreenElements_TimeSelectOpen(\''.$strFieldName.'\'); ';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onclick');

			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, $strTitle, 'time', $strAdditional, $strColClass, $aAttributes);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddInputFieldHidden($strFieldName, $strValue)
	{
		try
		{
			$_SESSION[$strFieldName] = $strValue;
			$strOutput = $this->ScreenElements_AddInputFieldCommon($strFieldName, "", 'hidden', '', "", "");
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	##### AddRadioOption stores each individual radio button. All are then displayed by AddRadioGroup
	function ScreenElements_AddRadioOption($strGroupName, $strLabel, $strValue, $strAdditional)
	{
		try
		{
			if (!isset($_SESSION[$strGroupName]))
				$_SESSION[$strGroupName] = "";

			#$strValue = str_replace(" ", "_", $strValue);

			$strChecked = "";
			if ($_SESSION[$strGroupName] == $strValue)
				$strChecked = ' checked="checked" ';

			$strOutput = '<input type="radio" name="'.$strGroupName.'" id="'.$strGroupName.'_'.$strValue.'" value="'.$strValue.'" '.$strChecked.' '.$strAdditional.'/> '.$strLabel;
			$strOutput = $this->ScreenElements_WrapLabel($strOutput, "", "screen-element-not-bold");
			$strOutput = '<div class="ToBeReplaceBy-ScreenElements_AddRadioGroup">'.$strOutput.'</div>';

			$aTemp = [];
			$aTemp['Field'] = $strOutput;
			$aTemp['Displayed'] = false;

			$this->aRadios[$strGroupName][] = $aTemp;

			$this->ScreenElements_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns the group of radio buttons created in ScreenElements_AddRadioOption
	function ScreenElements_AddRadioGroup($strGroupName, $strLabel, $bInline, $bRequired, $iMinimumPxForInline)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes["Label"] = $strLabel;
			$this->ScreenElements_PrefixAndPushElement($strGroupName, "Radio", $aAttributes);

			if (count($this->aRadios[$strGroupName]) == 0)
				$this->ScreenElements_DevError('Development error. Must call ScreenElements_AddRadioOption at least once.');

			$strClass = 'radio';
			if ($bInline)
			{
				$bFound = false;
				for ($I = 0; $I < count($this->aRadios[$strGroupName]); $I++)
					if ($this->aRadios[$strGroupName][$I]['Displayed'])
						$bFound = true;

				if ($bFound == false)
					$this->ScreenElements_OnResize($strGroupName.'-form-group', $iMinimumPxForInline, 'ScreenElements_SwapClass(\''.$strGroupName.'-form-group\', \'div\', \'radio-inline\', \'radio\')', 'ScreenElements_SwapClass(\''.$strGroupName.'-form-group\', \'div\', \'radio\', \'radio-inline\')');
				$strClass = 'radio-inline';
			}

			$strOptions = "";
			for ($I = 0; $I < count($this->aRadios[$strGroupName]); $I++)
			{
				if (!$this->aRadios[$strGroupName][$I]['Displayed'])
				{
					$strOptions .= str_replace('ToBeReplaceBy-ScreenElements_AddRadioGroup', $strClass, $this->aRadios[$strGroupName][$I]['Field']);
					$this->aRadios[$strGroupName][$I]['Displayed'] = true;
				}
			}

			$strOutput = "";
			if ($strLabel != "")
			{
				$strLabel = $this->ScreenElements_FormatFieldHead($strLabel, $bRequired);
				$strOutput = $this->ScreenElements_WrapLabel($strLabel, "", "");
			}
			$strOutput .= $this->ScreenElements_WrapFormGroup($strOptions, $strGroupName);

			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### AddCheck stores each individual checkbox. All are then displayed by AddCheckGroup
	function ScreenElements_AddCheck($strGroupName, $strFieldname, $strLabel, $strAdditional)
	{
		try
		{
			$aAttributes["Label"] = $strLabel;
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldname, "Checkbox", $aAttributes);

			if (!isset($_SESSION[$strFieldname]))
				$_SESSION[$strFieldname] = "";

			$strChecked = "";
			if ($_SESSION[$strFieldname] == 1)
				$strChecked = ' checked="checked" ';

			#$strBox = '<input type="checkbox" name="'.$strFieldName.'" id="'.$strFieldName.'" value="'.$strFieldname.'" '.$strChecked.' class="'.$strGroupName.'" '.$strAdditional.'/> '.$strLabel;
			$strBox = '<input type="checkbox" name="'.$strFieldName.'" id="'.$strFieldName.'" value="1" '.$strChecked.' class="'.$strGroupName.'" '.$strAdditional.'/> '.$strLabel;
			$strBox = $this->ScreenElements_WrapLabel($strBox, "", "screen-element-not-bold");
			$strBox = '<div class="ToBeReplaceBy-ScreenElements_AddCheckGroup">'.$strBox.'</div>';

			$aTemp = [];
			$aTemp['Field'] = $strBox;
			$aTemp['Displayed'] = false;

			$this->aChecks[$strGroupName][] = $aTemp;

			$this->ScreenElements_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns the group of checkboxes created in ScreenElements_AddCheck
	function ScreenElements_AddCheckGroup($strGroupName, $strLabel, $bInline, $iMinimumPxForInline)
	{
		try
		{
			if (count($this->aChecks[$strGroupName]) == 0)
				$this->ScreenElements_DevError('Development error. Must call ScreenElements_AddCheck at least once.');

			$strClass = "checkbox";
			if ($bInline)
			{
				$this->ScreenElements_OnResize($strGroupName.'-form-group', $iMinimumPxForInline, 'ScreenElements_SwapClass(\''.$strGroupName.'-form-group\', \'div\', \'checkbox-inline\', \'checkbox\')', 'ScreenElements_SwapClass(\''.$strGroupName.'-form-group\', \'div\', \'checkbox\', \'checkbox-inline\')');
				$strClass = "checkbox-inline";
			}

			$strBoxes = "";
			for ($I = 0; $I < count($this->aChecks[$strGroupName]); $I++)
			{
				if (!$this->aChecks[$strGroupName][$I]['Displayed'])
				{
					$strBoxes .= str_replace('ToBeReplaceBy-ScreenElements_AddCheckGroup', $strClass, $this->aChecks[$strGroupName][$I]['Field']);
					$this->aChecks[$strGroupName][$I]['Displayed'] = true;
				}
			}

			$strOutput = "";
			if ($strLabel != "")
			{
				$strLabel = $this->ScreenElements_FormatFieldHead($strLabel, false);
				$strOutput = $this->ScreenElements_WrapLabel($strLabel, "", "");
			}
			$strOutput .= $this->ScreenElements_WrapFormGroup($strBoxes, $strGroupName);

			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns hrefs for checking//unchecking all checkboxes
	function ScreenElements_AddCheckAll($strCheckGroup, $strColClass)
	{
		try
		{
			$strOutput = '<a href="" onclick="ScreenElements_CheckUncheck(\''.$strCheckGroup.'\', true); return false;">check all</a> / <a href="" onclick="ScreenElements_CheckUncheck(\''.$strCheckGroup.'\', false); return false; ">uncheck all</a>';
			$strOutput = $this->ScreenElements_WrapFormGroup($strOutput, $strCheckGroup."-checkall");
			$strOutput = $this->ScreenElements_WrapColClass($strOutput, $strColClass);

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns a selectbox filled with the $aResults field. The array should have two elements per row: ID and Name.
	function ScreenElements_AddSelect($strFieldName, $strLabel, $aResults, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes["Label"] = $strLabel;
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldName, "Select", $aAttributes);

			if ($aResults[0]["ID"] != "" && $aResults[0]["Name"] != "")
				array_unshift($aResults, ['ID' => '', 'Name' => '']);

			$strJava = 'ScreenElements_ValidateField(event)';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onchange');

			$strOutput = '<select name="'.$strFieldName.'" id="'.$strFieldName.'" class="form-control" data-nmu-req="'.$bRequired.'" '.$strAdditional.'>';
			foreach ($aResults as $aRow)
				$strOutput .= $this->ScreenElements_AddSelect_AddOption($strFieldName, $aRow['ID'], $aRow['Name']);
			$strOutput .= '</select>';

			$strOutput = $this->ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strOutput, $strColClass, $bRequired);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddSelectBuildArray($aArray, $strID, $strName)
	{
		try
		{
			$aTemp = [];
			$aTemp['ID'] = $strID;
			$aTemp['Name'] = $strName;

			$aArray[] = $aTemp;

			return $aArray;
		}
		catch (Exception $ex)
		{
			$this->ScreenElements_AddErrorDiv($ex->getMessage());
		}
	}

	##### Returns a select filed with U.S. state abreviations as value and name.
	function ScreenElements_AddState($strFieldName, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes["Label"] = "State";
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldName, "State", $aAttributes);

			$classSqlQuery = new SqlDataQueries();
			$strQuery = "SELECT StateFullname AS ID, StateAbr AS Name FROM cms_common_data_states ORDER BY StateAbr";
			$aStateResults = $classSqlQuery->MySQL_Queries($strQuery);
			$strOutput = $this->ScreenElements_AddSelect($strFieldName, "State", $aStateResults, $strColClass, $bRequired, $strAdditional);

			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns a multi-selectbox filled with the $aResults field. The array should have two elements per row: ID and Name.
	function ScreenElements_AddMultiSelect($strFieldName, $strLabel, $aResults, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes["Label"] = $strLabel;
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldName, "MultiSelect", $aAttributes);

			if ($aResults[0]["ID"] != "" && $aResults[0]["Name"] != "")
				array_unshift($aResults, ['ID' => '', 'Name' => '']);

			$size = (count($aResults)) > 8 ? 8 : count($aResults);

			$strJava = 'ScreenElements_ValidateField(event)';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onchange');

			$strOutput = '<select multiple name="'.$strFieldName.'[]" id="'.$strFieldName.'" class="'.$strFieldName.' form-control" size="'.$size.'" data-nmu-req="'.$bRequired.'" '.$strAdditional.'>';
			foreach ($aResults as $aRow)
				$strOutput .= $this->ScreenElements_AddSelect_AddOption($strFieldName, $aRow['ID'], $aRow['Name']);
			$strOutput .= '</select>';

			$strOutput = $this->ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strOutput, $strColClass, $bRequired);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Returns a CKEditor wysiwyg
	function ScreenElements_AddWYSIWYG($strFieldName, $strLabel, $strToolbarType, $iMaxChars, $strColClass, $bRequired, $strAdditional)
	{
		try
		{
			# strToolbarType can be "Std" or "Simple". Default is "Std"
			$aAttributes['Required'] = $bRequired;
			$aAttributes["Label"] = $strLabel;
			$this->ScreenElements_PrefixAndPushElement($strFieldName."-WYSIWYG", "WYSIWYG", $aAttributes);

			if ($iMaxChars == "")
				$iMaxChars = 0;

			$aTemp = [];
			$aTemp['strLabel'] = $strLabel;
			$aTemp['strToolbarType'] = $strToolbarType;
			$aTemp['iMaxChars'] = $iMaxChars;
			$this->aEditors[$strFieldName] = $aTemp;

			if (isset($_SESSION[$strFieldName]) && $_SESSION[$strFieldName] != "")
				$strInitValue = $_SESSION[$strFieldName];
			else
				$strInitValue = "";

			$strOutput = '<div id="'.$strFieldName.'-validation-div"><textarea name="'.$strFieldName.'" id="'.$strFieldName.'" class="form-control" data-nmu-req="'.$bRequired.'" data-nmu-special-type="wysiwyg" '.$strAdditional.'>'.$strInitValue.'</textarea></div>';
			if ($iMaxChars > 0)
				$strOutput .= '<div id="JS_AddEditor_'.$strFieldName.'_Counter" style="text-align:right; padding-top:5px;">Characters remaining: '.$iMaxChars.'</div>';

			ProcessJavascript::ProcessJavascript_WrapOutput(' CKEDITOR.replace( "'.$strFieldName.'", { '.$this->ScreenElements_WYSIWYG_ToolTypes("").', }); CKEDITOR.add;');

			$strRquiredProcessing = "";
			if ($bRequired)
			{
				$strRquiredProcessing = '
					CKEDITOR.instances.'.$strFieldName.'.document.on( "focusout", function( evt ) {
						$strData = CKEDITOR.instances.'.$strFieldName.'.getData();
						if($strData == "")
							ScreenElements_MarkCKEditorValidOrNot(\''.$strFieldName.'\', false);
						else
							ScreenElements_MarkCKEditorValidOrNot(\''.$strFieldName.'\', true);
					});';
			}


			$strCharLimitJava = "";
			if ($iMaxChars > 0)
			{
				$strCharLimitJava = '
					CKEDITOR.instances.'.$strFieldName.'.document.on( "keydown", function( evt ) {
						ScreenElements_WYSIWYG_StopInput(evt, "'.$strFieldName.'", "'.$iMaxChars.'");
					});

					this.document.on( "keyup", function(evt) {
						ScreenElements_WYSIWYG_CheckLength(evt, "'.$strFieldName.'", "'.$iMaxChars.'");
					});
				';
			}

			$strJava = '
				jQuery(document).ready(function() { 
					CKEDITOR.instances.'.$strFieldName.'.on("contentDom", function() {
						'.$strCharLimitJava.'
						'.$strRquiredProcessing.'
					});
				})
			';

			ProcessJavascript::ProcessJavascript_WrapOutput($strJava);

			$strOutput = $this->ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strOutput, $strColClass, $bRequired);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddTextArea($strFieldName, $strLabel, $iRows, $strColClass, $bRequired, $strAdditional, $strPlaceHolder)
	{
		try
		{
			$aAttributes['Required'] = $bRequired;
			$aAttributes['PlaceHolder'] = $strPlaceHolder;
			$aAttributes["Label"] = $strLabel;
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldName, "TextArea", $aAttributes);

			$strFieldValue = '';
			if (isset($_SESSION[$strFieldName]))
			{
				$strFieldValue = $_SESSION[$strFieldName];
			}

			$strJava = 'ScreenElements_ValidateField(event)';
			$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strJava, 'onchange');

			$strOutput = '<textarea name="'.$strFieldName.'" id="'.$strFieldName.'" class="form-control" rows="'.$iRows.'" data-nmu-req="'.$bRequired.'" '.$strAdditional.' placeholder="'.$strPlaceHolder.'">'.$strFieldValue.'</textarea>';

			$strOutput = $this->ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strOutput, $strColClass, $bRequired);
			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddButton($strGroupName, $strFieldName, $strLabel, $strAdditional, $bValidate, $strAction, $strURL)
	{
		try
		{
			# Event priority is Validate, Action, then URL
			# Action should take the form of a javascript function call
			# If action and URL are blank, form will submit
			if ($bValidate)
				$strAdditional = $this->ScreenElements_PrependItem($strAdditional, 'ScreenElements_ValidateForm(\''.$this->strFormName.'\', \''.$strAction.'\', \''.$strURL.'\');', "onclick");
			elseif ($strAction != "")
				$strAdditional = $this->ScreenElements_AppendItem($strAdditional, $strAction, "onclick");
			elseif ($strURL != "")
				$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'window.location=\''.$strURL."'", "onclick");
			else
				$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'document.getElementById(\''.$this->strFormName.'\').submit();', "onclick");
			$this->aButtonGroups[$strGroupName][] = '<button type="button" id="'.$strFieldName.'" name="'.$strFieldName.'" class="btn btn-default screen-elements-btn-inline" '.$strAdditional.'>'.$strLabel.'</button>';

			$this->ScreenElements_StoreSelf();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddButtonGroup($strGroupName, $iMinimumPxForInline)
	{
		try
		{
			$this->ScreenElements_OnResize($strGroupName, $iMinimumPxForInline, 'ScreenElements_SwapClass(\''.$strGroupName.'\', \'button\', \'screen-elements-btn-inline\', \'screen-elements-btn-full\')', 'ScreenElements_SwapClass(\''.$strGroupName.'\', \'button\', \'screen-elements-btn-full\', \'screen-elements-btn-inline\')');

			$strButtons = "";
			foreach ($this->aButtonGroups[$strGroupName] as $strButton)
				$strButtons .= $strButton;
			$strOutput = '<div id="'.$strGroupName.'" class="screen-elements-btn-group">'.$strButtons.'</div>';

			$this->ScreenElements_StoreSelf();

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_AddErrorDiv($strColClass)
	{
		$strOutput = '<div class="row">'.$this->ScreenElements_WrapColClass('<div id="'.$this->strObjName.'_ErrorMessage" class="screen-elements-error-div"></div>', $strColClass).'</div>';

		$this->ScreenElements_StoreSelf();
		return $strOutput;
	}


	###########################################
	############ Database Mgmt Functs #########
	###########################################

	function ScreenElements_DataLoadSessions($aResults)
	{
		try
		{
			foreach ($aResults as $aRow)
				foreach ($aRow as $strName => $strValue)
					if ((strstr(strtolower($strName), "phone") || strstr(strtolower($strName), "fax")) && is_numeric($strValue) && strlen($strValue) == 10)
						$_SESSION[$strName] = $this->ScreenElements_PhoneDisplay($strValue);
					else
						$_SESSION[$strName] = $strValue;

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	###########################################
	############ Format Field Data  ###########
	###########################################
	function ScreenElements_PhoneDisplay($iPhone)
	{
		try
		{
			if ($iPhone != '' && is_numeric($iPhone) && strlen($iPhone) == 10)
				return substr($iPhone, 0, 3).'-'.substr($iPhone, 3, 3).'-'.substr($iPhone, 6, 4);
			else
				return $iPhone;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_PhoneEncode($strPhone)
	{
		try
		{
			if ($strPhone != '')
			{
				$iPhone = str_replace("-", "", $strPhone);
				if (is_numeric($iPhone))
					return $iPhone;
				else
					return "unknown format";
			}
			else
				return "unknown format";
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_TimeDisplay($iStartDateTime, $iEndDateTime)
	{
		try
		{
			if ($iStartDateTime == "")
				$iStartDateTime = 0;

			if ($iEndDateTime == "")
				$iEndDateTime = 0;

			$strTime = "";
			if ($iStartDateTime != 0 && $iEndDateTime == 0)
				$strTime = date("g:ia", $iStartDateTime);

			if ($iStartDateTime != 0 && $iEndDateTime != 0)
			{
				if (date("a", $iStartDateTime) != date("a", $iEndDateTime))
					$strTime = date("g:ia - ", $iStartDateTime).date("g:ia", $iEndDateTime);
				else
					$strTime = date("g:i - ", $iStartDateTime).date("g:ia", $iEndDateTime);
			}

			return $strTime;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_DateTimeEncode($strDate, $strTime)
	{
		try
		{
			if ($strDate != "" || $strTime != "")
				return strtotime($strDate." ".$strTime);
			else
				return 0;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	public static function ScreenElements_DateDecode($strFieldName, $iDateTime)
	{
		try
		{
			$strDate = "";
			if ($iDateTime != "" && $iDateTime != 0)
				$strDate = date("m/d/Y", $iDateTime);

			if ($strFieldName != "" && $strDate != "")
				$_SESSION[$strFieldName] = $strDate;

			return $strDate;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_TimeDecode($strFieldName, $iDateTime)
	{
		try
		{
			$_SESSION[$strFieldName] = "";
			if ($iDateTime != "" && $iDateTime != 0)
			{
				$_SESSION[$strFieldName] = date("g:i a", $iDateTime);
			}

			return $_SESSION[$strFieldName];
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	###########################################
	####### Data Validation Functions  ########
	###########################################

	function ScreenElements_ValidatePhone($strFieldname, $bIsRequired)
	{
		try
		{
			$iNumber = str_replace("-", "", $_REQUEST[$strFieldname]);
			if ($iNumber != "" && (!is_numeric($iNumber) || strlen($iNumber) != 10))
				$this->aIssues[] = $strFieldname.' is invalid. (Valid: ###-###-####)';
			if ($iNumber == "" && $bIsRequired)
				$this->aIssues[] = $strFieldname.' is required';

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ScreenElements_ValidateFields()
	{
		try
		{
			$this->aIssues = [];

			foreach ($this->aElementsDetails as $aInput)
			{
				if ($aInput['FieldType'] == "WYSIWYG")
					$strValue = $_REQUEST[str_replace("-WYSIWYG", "", $aInput['FieldName'])];
				else
					$strValue = $_REQUEST[$aInput['FieldName']];

				if ($aInput['Required'] && $strValue == "")
					$this->aIssues[] = $aInput['Label'].' is a required field';
				elseif ($strValue != "" && $aInput['Pattern'] != "")
				{
					$iResult = preg_match('/'.str_replace('/', '\/', $aInput['Pattern']).'/', $strValue);
					if ($iResult == 0)
						$this->aIssues[] = $aInput['Label'].' does not match the required format. Format must be: '.$aInput['PlaceHolder'];
				}
			}

			$this->ScreenElements_StoreSelf();

			return $this->aIssues;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	###########################################
	########### Element Wrapping  #############
	###########################################

	##### Wraps an element with a label
	function ScreenElements_WrapLabel($strText, $strForElement, $strClass)
	{
		try
		{
			if ($strText != "")
			{
				if ($strForElement != "")
					$strForElement = ' for="'.$strForElement.'" ';

				if ($strClass != "")
					$strClass = ' class="'.$strClass.'" ';

				return '<label '.$strForElement.' '.$strClass.'>'.$strText.'</label>';
			}
			else
				return "";
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Wraps an element in a div with a column size
	function ScreenElements_WrapColClass($strElement, $strColClass)
	{
		try
		{
			return '<div class="'.$strColClass.'">'.$strElement.'</div>';
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Formats input text
	function ScreenElements_FormatFieldHead($strElement, $bRequired)
	{
		try
		{
			if ($strElement != "")
			{
				return '<strong>'.$strElement.$this->ScreenElements_RequireLabel($bRequired).'</strong>';
			}
			else
			{
				return "";
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Appends the html for required fields
	function ScreenElements_RequireLabel($bRequired)
	{
		try
		{
			if ($bRequired)
			{
				return '<span class="form-required" aria-hidden="true">*</span><span class="sr-only">(Required)</span>';
			}
			else
			{
				return "";
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	###########################################
	######## Custom Responsive Stuff ##########
	###########################################
	function ScreenElements_RegisterAsPopup($strObjectID, $strObjectIDAdjacentToo, $strContainedWithinDivID, $strCloseFunction)
	{
		try
		{
			$strJava = '
				jQuery(document).bind("mouseup", function() { ScreenElements_CloseObjectIfClickElseWhere(\''.$strObjectID.'\', event, \''.$strCloseFunction.'\') })
				//jQuery(document).ready(function() { ScreenElements_PositionObject(\''.$strObjectID.'\', \''.$strObjectIDAdjacentToo.'\', \'bottom\') });
				//jQuery( window ).resize(function() { ScreenElements_ContainWithin(\''.$strObjectID.'\', \''.$strContainedWithinDivID.'\') })
			';

			$classJava = new ProcessJavascript();
			$classJava::ProcessJavascript_WrapOutput($strJava);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	##### Watches the width of an object and performs the less or more action accordingly
	function ScreenElements_OnResize($strObjectID, $iMinPixals, $strLessAction, $strMoreAction)
	{
		try
		{
			$strScript = "";

			if ($iMinPixals != "")
			{
				$strFunctionName = str_replace('-', '_', $strObjectID);

				$strScript = '
					function '.$strFunctionName.'_OnResize()
					{
						if (jQuery("#'.$strObjectID.'").length)
						{
							if(jQuery("#'.$strObjectID.'").width() < '.$iMinPixals.')
								{ '.$strLessAction.' }
							else
								{ '.$strMoreAction.' }
						}
					}

					if('.$iMinPixals.' > 0)
						jQuery( window ).resize(function() { '.$strFunctionName.'_OnResize(); })

					jQuery(document).ready(function() { '.$strFunctionName.'_OnResize(); });';
			}

			ProcessJavascript::ProcessJavascript_WrapOutput($strScript);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	###########################################
	######## Class Support Functions ##########
	###########################################

	private function ScreenElements_WYSIWYG_ToolTypes($strToolbarType)
	{
		try
		{
			$strToolbar = "";
			if ($strToolbarType == "" || strtolower($strToolbarType) == "std")
			{
				$strToolbar = ' toolbar: [
							[ "Source", "Print", "Cut", "Paste", "PasteText", "PasteFromWord", "RemoveFormat", "Undo", "Find", "Replace" ],
							[ "Bold", "Italic", "Underline", "Subscript", "Superscript", "NumberedList", "BulletedList", "Table", "Outdent", "Indent", "Link", "Unlink", "Anchor", "SpellChecker" ]
						]';
			}
			elseif (strtolower($strToolbarType) == "simple")
			{
				$strToolbar = ' toolbar: [ [ "Source", "Cut", "Paste", "PasteText", "PasteFromWord"  ] ]';
			}

			return $strToolbar;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Dumps all attributes of this class
	function ScreenElements_SelfDump()
	{
		try
		{
			$classDump = new Dump();
			$classDump->Display($this);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	##### PRIVATE FUNCTIONS #####
	private function ScreenElements_WrapFormGroup($strElement, $strFieldID)
	{
		try
		{
			$strFieldID = $strFieldID."-form-group";
			return '<div id="'.$strFieldID.'" class="'.$strFieldID.' form-group">'.$strElement.'</div>';
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Adds all needed formating to a field
	private function ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strFieldInfo, $strColClass, $aAttributes)
	{
		try
		{ 
			$strLabel = $this->ScreenElements_FormatFieldHead($strLabel, $aAttributes["Required"]);
			$strOutput = $this->ScreenElements_WrapLabel($strLabel, $strFieldName, "");

			if (isset($aAttributes['File']) && $aAttributes['File'] != "")
				$strOutput = $aAttributes['File'];

			$strOutput .= $strFieldInfo;
			$strOutput = $this->ScreenElements_WrapFormGroup($strOutput, $strFieldName);

			if ($strColClass != "")
				$strOutput = $this->ScreenElements_WrapColClass($strOutput, $strColClass);

			return $strOutput;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function ScreenElements_AddInputFieldCommon($strFieldName, $strLabel, $strType, $strAdditional, $strColClass, $aAttributes)
	{
		try
		{
			$aAttributes["Label"] = $strLabel;
			$strFieldName = $this->ScreenElements_PrefixAndPushElement($strFieldName, $strType, $aAttributes);

			$strValue = '';
			if (isset($_SESSION[$strFieldName]))
				$strValue = ' value="'.$this->ScreenElements_QuoteFix($_SESSION[$strFieldName]).'" ';

			$bIsDate = false;
			if ($strType == "date")
			{
				$strType = "text";
				$bIsDate = true;
			}

			$bIsTime = false;
			if ($strType == "time")
			{
				$strType = "text";
				$bIsTime = true;
			}

			if ($strType == "email")
				$strType = "text";

			if ($bIsDate)
				$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'ScreenElements_ValidateField(event)', 'onchange');
			elseif (!$bIsTime && $strType != "tel")
				$strAdditional = $this->ScreenElements_AppendItem($strAdditional, 'ScreenElements_ValidateField(event)', 'onfocusout');

			$strOutput = ' <input type="'.$strType.'" id="'.$strFieldName.'" name="'.$strFieldName.'" '.$strValue.' '.$strAdditional.' placeholder="'.$aAttributes["PlaceHolder"].'" pattern="'.$aAttributes["Pattern"].'" data-nmu-req="'.$aAttributes["Required"].'" class="form-control" >';

			if($strType == "file")
			{
				if(!isset($GLOBALS['ScreenElements_AddInputFieldUpload']) || $GLOBALS['ScreenElements_AddInputFieldUpload'] == "")
				{
					$GLOBALS['ScreenElements_AddInputFieldUpload'] = "Loaded";
					
					print '<div id="ScreenElements_AddInputFieldUpload">
					<style>
						FileUpload_ControlWrapper {
							position: relative;
						}

						.FileUpload_InputField {
							display: none;
						}

						.FileUpload_FakeFile {
							position: relative;
							text-align: center;
							z-index: 1;
						}

						.FileUpload_Icon {
							font-size: 24px;
						}

						.FileUpload_IconDiv {
							text-align: center;
							padding-top: 5px;
						}
					</style>
						
					<script>
						function FileUpload_FireUpload($strFieldname)
						{
							jQuery("#"+$strFieldname).trigger("click");
						}

						function FileUpload_RemoveUpload($strFieldname)
						{
							$strURL = jQuery("#"+$strFieldname).closest("form").attr("action")+"&ImageActions=Remove";
							jQuery.post($strURL, { Fieldname: $strFieldname }, function ($strData)
							{
								jQuery("#"+$strFieldname+"_ImageWrapper").html("");
								jQuery("#"+$strFieldname).val("");

								$strBaseFieldname = $strFieldname.replace("'.$aAttributes['FieldNameExt'].'", "");
								jQuery("#"+$strBaseFieldname).val("");

								FileUpload_SetImageActions($strFieldname);
							});
						}

						function FileUpload_SetImageActions($strFieldname)
						{
							if(jQuery("#"+$strFieldname).length)
							{
								if(jQuery("#"+$strFieldname+"_ImageWrapper").html().indexOf("<img ") > -1)
									jQuery("#"+$strFieldname+"_Actions").html("<a onclick=\"FileUpload_FireUpload(\'"+$strFieldname+"\'); return false;\" href=\"\">replace</a> | <a onclick=\"FileUpload_RemoveUpload(\'"+$strFieldname+"\'); return false;\" href=\"\">remove</a></div>")
								else
								{
									jQuery("#"+$strFieldname+"_ImageWrapper").html("<div id=\""+$strFieldname+"_ImageWrapper\" class=\"FileUpload_IconDiv\"><span id=\""+$strFieldname+"_UploadIcon\" onclick=\"FileUpload_FireUpload(\'"+$strFieldname+"\'); return false;\" class=\"glyphicon glyphicon-upload FileUpload_Icon\"></span></div>");
									jQuery("#"+$strFieldname+"_Actions").html("<a onclick=\"FileUpload_FireUpload(\'"+$strFieldname+"\'); return false;\" href=\"\">add photo</a></div>")
								}
							}
						}

						function FileUpload_ProcessUpload($strFieldname, $strDir)
						{
							$strMyFormID = jQuery("#"+$strFieldname).closest("form").attr("id")
							var $objForm = document.getElementById($strMyFormID);
							var $classFormData = new FormData($objForm);

							var $classRequest = new XMLHttpRequest();
							$classRequest.onreadystatechange = function() 
							{
								if (this.readyState == 4 && this.status == 200)
								{
									if(this.responseText.indexOf("Error") == -1)
									{
										$aParts = this.responseText.split("=====")
										jQuery("#"+$strFieldname+"_ErrorMsg").html("");
										jQuery("#"+$strFieldname+"_ImageWrapper").html("<img id=\""+$strFieldname+"_Image\" src=\""+$aParts[0]+"\">");

										$strBaseFieldname = $strFieldname.replace("'.$aAttributes['FieldNameExt'].'", "");
										jQuery("#"+$strBaseFieldname).val($aParts[1]);

										FileUpload_SetImageActions($strFieldname); 
									}
									else
										jQuery("#"+$strFieldname+"_ErrorMsg").html(this.responseText);
								}
							};

							$classRequest.open("POST", "https://'.$_SERVER["HTTP_HOST"].'/cgi-bin/Includes/2015_FunctionsCommonFunctions.php?action=ScreenElements_FileUpload_ProcessUpload&Fieldname="+$strFieldname+"&Directory="+$strDir, true);
							$classRequest.send($classFormData);
						}

						jQuery(document).ready(function() { 
							$strShared = jQuery("#ScreenElements_AddInputFieldUpload").html();
							jQuery("#ScreenElements_AddInputFieldUpload").html("");
							jQuery( "body" ).append($strShared)
						})
					</script></div>';
				}

				print '<script> 
					jQuery(document).ready(function() { 
						FileUpload_SetImageActions("'.$strFieldName.'");
						jQuery("#'.$strFieldName.'").change(function() { FileUpload_ProcessUpload("'.$strFieldName.'", "'.$aAttributes['Directory'].'") });
					})
				</script>';

				$strOutput = $strOutput.$this->ScreenElements_AddInputFieldHidden($aAttributes['BaseFieldName'], $_SESSION[$aAttributes['BaseFieldName']]);
				$strOutput = '
					<div class="FileUpload_ControlWrapper">
						<div class="FileUpload_InputField">'.$strOutput.'</div>
						<div class="FileUpload_FakeFile">
							<div id="'.$strFieldName.'_ErrorMsg"></div>
							<div id="'.$strFieldName.'_Actions"></div>
						</div>
					</div>';
			}

			if($strType == "fileold")
				$strOutput = str_replace("fileold", "file", $strOutput);

			if ($strType == "hidden" || $bIsDate)
				return $strOutput;
			else
				return $this->ScreenElements_FormatFieldOutput($strFieldName, $strLabel, $strOutput, $strColClass, $aAttributes);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Prepends/Appends an item to an input field. Example: add onclick="CallFunction();" to aninput that already has an onclick. Use this and it will add it to the existing onclick
	private function ScreenElements_PrependItem($strGroup, $strItem, $strType)
	{
		try
		{
			$strGroup = trim($strGroup);
			$strItem = trim($strItem);

			if (strstr($strGroup, $strType))
			{
				if (substr($strItem, strlen($strItem) - 2) != ';')
				{
					$strItem .= ';';
				}

				$strResult = str_replace($strType.'="', $strType.'="'.$strItem, $strGroup);
			}
			else
			{
				$strResult = $strType.'="'.$strItem.'" '.$strGroup;
			}

			return $strResult;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function ScreenElements_AppendItem($strGroup, $strItem, $strType)
	{
		try
		{
			$strItem = trim($strItem);
			if (substr($strItem, strlen($strItem) - 1, 1) != ";")
			{
				$strItem .= ';';
			}

			$strPattern = '('.$strType.'="[a-zA-Z0-9]+\([.]*\);")';
			preg_match($strPattern, $strGroup, $aMatches);

			if (count($aMatches) == 0)
			{
				$strResult = $strGroup.' '.$strType.'="'.$strItem.' "';
			}
			elseif (count($aMatches) == 1)
			{
				$strNew = trim(substr($aMatches[0], 0, strlen($aMatches[0]) - 1));
				if (substr($strNew, strlen($strNew) - 1, 1) != ";")
				{
					$strNew .= ';';
				}
				$strNew .= ' '.$strItem.'"';

				$strResult = str_replace($aMatches[0], $strNew, $strGroup);
			}
			else
			{
				throw new exception("You should not have more than one type of event attached to a single item. You have ".count($aMatches)." ".$strType." events. (".$strItem.")");
			}

			return $strResult;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	##### Display an error for various developer mistakes. A user will never see this.
	private function ScreenElements_DevError($strText)
	{
		PrintR('<span style="color:red">'.$strText.'</span>');
		die;
	}

	private function ScreenElements_QuoteFix($string)
	{
		try
		{
			return str_replace("&amp;", "&", (htmlentities(stripslashes($string), ENT_QUOTES)));
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function ScreenElements_AddSelect_AddOption($strFieldName, $strValue, $strText)
	{
		try
		{
			if ((isset($_SESSION[$strFieldName]) && is_array($_SESSION[$strFieldName]) && in_array($strValue, $_SESSION[$strFieldName])) ||
				((isset($_SESSION[$strFieldName]) && !is_array($_SESSION[$strFieldName])) && (isset($_SESSION[$strFieldName]) && strtolower($_SESSION[$strFieldName]) == strtolower($strValue))))
				$strRetVal = '<option value="'.$strValue.'" selected="selected">'.$strText.'</option>';
			else
				$strRetVal = '<option value="'.$strValue.'">'.$strText.'</option>';

			return $strRetVal;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function ScreenElements_PrefixAndPushElement($strFieldName, $strFieldType, $aAttributes)
	{
		try
		{
			$strFullFieldName = $this->strPrefix.$strFieldName;

			$aTemp = [];
			$aTemp['FieldName'] = $strFullFieldName;
			$aTemp['FieldType'] = $strFieldType;
			$aTemp['FieldGroup'] = "";
			foreach ($aAttributes as $strName => $strValue)
			{
				$aTemp[$strName] = $strValue;
			}
			$this->aElementsDetails[$strFieldName] = $aTemp;

			return $strFullFieldName;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	protected function ScreenElements_SelfLoad()
	{
		try
		{
			$aSessions = $this->classSession->SessionMgmt_Select($this->strObjName);
			foreach ($aSessions as $strName => $strValue)
				$this->{$strName} = $strValue;
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	protected function ScreenElements_StoreSelf()
	{
		try
		{
			$this->classSession->SessionMgmt_Set($this->strObjName, $this);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}
