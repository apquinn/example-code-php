<?php




class SecureRemoteCall extends BaseClass
{
	protected $aAllRemoteCallCurlVars = [];
	protected $strObjName = "SecureRemoteCall";


	function __construct()
	{
		try
		{
			$aArgs['strObjName'] = $this->strObjName;
			$aArgs['bIsPersistant'] = true;
			$aArgs['bReset'] = false;
			parent::__construct($aArgs);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SecureRemoteCall_Post($strURL, $strAction, $aVariables, $strSuccessFunction, $bIsRepeatable)
	{
		try
		{
			if($strURL == "" || $strAction == "")
				throw new Exception("Both url and action are required. ");

			$iUniqueID = $this->SecureRemoteCall_GetUniqueIndex();

			$aNewRemoteCallVars = [];
			$aNewRemoteCallVars['SecureRemoteCall_Post_Variables'] = $aVariables;
			$aNewRemoteCallVars['SecureRemoteCall_Post_URL'] =  $strURL;
			$aNewRemoteCallVars['SecureRemoteCall_Post_Action'] =  $strAction;
			$aNewRemoteCallVars['SecureRemoteCall_Post_Repeatable'] =  $bIsRepeatable;

			$this->aAllRemoteCallPostVars[$iUniqueID] = $aNewRemoteCallVars;
			$this->BaseClass_StoreSelf();

			IncludeJavascriptLib_2015("Form");

			print '<script>
					jQuery.post
					(
						"/cgi-bin/Includes/2015_FunctionsCommonFunctions.php", { action:"SecureRemoteCall_PostProcess", SessionMgmt_SessionID:"'.$this->classSession->SessionMgmt_GetSessionID().'", SecureRemoteCall_PostUniqueIndex:"'.$iUniqueID.'" },
						function($strData)
						{
							'.$strSuccessFunction.'($strData);
						}
					);
				</script>';
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SecureRemoteCall_PostProcess($iSecureRemoteCall_PostUniqueIndex)
	{
		try
		{
			if (isset($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]))
			{
				if(!file_exists($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_URL']))
					throw new Exception($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_URL'].' is not a file.');
				require_once($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_URL']);

				if(count($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_Variables']) > 0)
					$this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_Action']($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_Variables']);
				else
					$this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_Action']();

				if(!$this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]['SecureRemoteCall_Post_Repeatable']) 
				{
					unset($this->aAllRemoteCallPostVars[$iSecureRemoteCall_PostUniqueIndex]);
					$this->BaseClass_StoreSelf();
				}
			} 
			else 
				throw new Exception("Invalid call to secure Post. URL: ".$this->aAllRemoteCallPostVars["SecureRemoteCall_Post_URL"].", Action: ".$this->aAllRemoteCallPostVars["SecureRemoteCall_Post_Action"]);
		}
		catch (Exception $ex) {
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SecureRemoteCall_Curl($strURL, $strAction, $aVariables)
	{
		try
		{
			$this->SecureRemoteCall_ClearExpired();
			$iUniqueID = $this->SecureRemoteCall_GetUniqueIndex();

			$aNewRemoteCallVars = [];
			$aNewRemoteCallVars['SecureRemoteCall_Curl_Variables'] = $aVariables;
			$aNewRemoteCallVars['SecureRemoteCall_Curl_Action'] =  $strAction;
			$aNewRemoteCallVars['SecureRemoteCall_Curl_Time'] =  time();

			if($strURL == "") 
			{
				$strDestURL = "https://www.nmu.edu/";
				$aNewRemoteCallVars['SecureRemoteCall_Curl_URL'] = "";
			}
			else 
			{
				$aParts = explode("cgi-bin/", $strURL);
				$strDestURL = $aParts[0];
				$aNewRemoteCallVars['SecureRemoteCall_Curl_URL'] = "/htdocs/cmsphp/".$aParts[1];
			}

			$this->aAllRemoteCallCurlVars[$iUniqueID] = $aNewRemoteCallVars;
			$this->BaseClass_StoreSelf();

			$curlHandle = curl_init($strDestURL."cgi-bin/Includes/2015_FunctionsCommonFunctions.php?action=SecureRemoteCall_CurlProcess&SessionMgmt_SessionID=".$this->classSession->SessionMgmt_GetSessionID()."&SecureRemoteCall_CurlUniqueIndex=".$iUniqueID);
			curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlHandle, CURLOPT_FRESH_CONNECT, true);
			$aResultForDebugging = curl_exec($curlHandle);
			curl_close($curlHandle);
			$this->BaseClass_SelfLoad();

			if(isset($this->aAllRemoteCallCurlVars[$iUniqueID]['SecureRemoteCall_Curl_Result']) && $this->aAllRemoteCallCurlVars[$iUniqueID]['SecureRemoteCall_Curl_Result'] != "") 
				$aReturnVal = $this->aAllRemoteCallCurlVars[$iUniqueID]['SecureRemoteCall_Curl_Result'];
			else
				$aReturnVal = [];

			unset($this->aAllRemoteCallCurlVars[$iUniqueID]);
			return $aReturnVal;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SecureRemoteCall_CurlProcess($iSecureRemoteCall_CurlUniqueIndex)
	{
		try
		{
			$this->SecureRemoteCall_ClearExpired();

			if (isset($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex])) 
			{
				if($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Action'] == "Query") 
				{
					if(isset($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['QueryClass']) && $this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['QueryClass'] != "")
						$classSqlQuery = $this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['QueryClass'];
					else
						$classSqlQuery = new SqlDataQueries();

					if($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['Query'] != "")
					{
						if (is_a($classSqlQuery, 'OracleDataQueries'))
							$aResults = $classSqlQuery->Oracle_Queries($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['Query'], true);
						else
							$aResults = $classSqlQuery->MySQL_Queries($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Variables']['Query']);
					}
					else
						$aResults = "No query received";
				}
				elseif($this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Action'] == "Custom") 
				{
					require_once($this->aAllRemoteCallCurlVars["SecureRemoteCall_Curl_URL"]);
					$aResults = $this->aAllRemoteCallCurlVars["SecureRemoteCall_Curl_Action"]($this->aAllRemoteCallCurlVars["SecureRemoteCall_Curl_Variables"]);
				}

				$this->aAllRemoteCallCurlVars[$iSecureRemoteCall_CurlUniqueIndex]['SecureRemoteCall_Curl_Result'] = $aResults;
				$this->BaseClass_StoreSelf();
			} 
			else
				throw new Exception("Invalid call to secure curl. URL: ".$this->aAllRemoteCallCurlVars["SecureRemoteCall_Curl_URL"].", Action: ".$this->aAllRemoteCallCurlVars["SecureRemoteCall_Curl_Action"]);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function SecureRemoteCall_ClearExpired()
	{
		try
		{
			foreach($this->aAllRemoteCallCurlVars as $iIndex=>$strValue) {
				if(!isset($strValue['SecureRemoteCall_Curl_Time']) || $strValue['SecureRemoteCall_Curl_Time'] < time()-10) {
					unset($this->aAllRemoteCallCurlVars[$iIndex]);
				}
			}

			$this->BaseClass_StoreSelf();
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function SecureRemoteCall_GetUniqueIndex()
	{
		try
		{
			$iUnique = (int) str_replace(" ", "", str_replace("0.", "", microtime()));
			while (isset($this->aAllRemoteCallCurlVars[$iUnique]))
				$iUnique += 1;

			return $iUnique;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}



