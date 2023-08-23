<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class SessionMgmt
{
	private $strSessionID = "";
	private $classSqlQuery = "";

	function __construct()
	{
		try
		{
			$this->classSqlQuery = new SqlDataQueries();
			$this->classSqlQuery->SpecifyDB(Const_connCharlieHost, "", "", "");

			if(isset($_SESSION['SessionMgmt_SessionID']) && $_SESSION['SessionMgmt_SessionID'] != "")
				$this->strSessionID = $_SESSION['SessionMgmt_SessionID'];
			else
				$this->strSessionID = session_id();

			$this->SessionMgmt_TouchSession();
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_SetSessionID($iID)
	{
		try
		{
			if($iID != "")
			{
				$this->strSessionID = $iID;
				$_SESSION['SessionMgmt_SessionID'] = $iID;

				$this->SessionMgmt_TouchSession();
			}

			return $this->strSessionID;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_GetSessionID()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			return $this->strSessionID;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_Set($strFieldName, $objObject)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$aSessions = $this->SessionMgmt_SelectAll();
			$aSessions[$strFieldName] = $objObject;
			$strQuery = "UPDATE www_admin.sessionmgmt_session_storage SET SessionData='".addslashes(serialize($aSessions))."' WHERE SessionID='".addslashes($this->strSessionID)."'";
			$this->classSqlQuery->MySQL_Queries($strQuery);

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_Select($strFieldName)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$aResults = $this->SessionMgmt_SelectAll();

			if(isset($aResults[$strFieldName]))
				return $aResults[$strFieldName];
			else
				return [];
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_SelectAll()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$strQuery = "SELECT * FROM www_admin.sessionmgmt_session_storage WHERE SessionID='".addslashes($this->strSessionID)."'";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			if (count($aResults) > 0)
				return unserialize($aResults[0]['SessionData']);
			else
				return [];
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SessionMgmt_DeleteValue($strFieldName)
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			if (!isset($strFieldName) || $strFieldName == "")
				ErrorHandler::ErrorHandler_CatchError("FiledName is required for SessionMgmt_DeleteValue");
			else
			{
				$aSessions = $this->SessionMgmt_SelectAll();

				if (is_array($aSessions) && count($aSessions) > 0 && isset($aSessions[$strFieldName])) {
					unset($aSessions[$strFieldName]);

					$strQuery = "UPDATE www_admin.sessionmgmt_session_storage SET SessionData='".addslashes(serialize($aSessions))."', LastTouchDate='".time()."' WHERE SessionID='".addslashes($this->strSessionID)."'";
					$this->classSqlQuery->MySQL_Queries($strQuery);
				}
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SessionMgmt_DeleteAll()
	{
		try
		{
			$this->SessionMgmt_TouchSession();

			$strQuery = "UPDATE FROM www_admin.sessionmgmt_session_storage SET SessionData='' WHERE SessionID='".addslashes($this->strSessionID)."'";
			$this->classSqlQuery->MySQL_Queries($strQuery);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function SessionMgmt_CreateSession()
	{
		try
		{
			$iTime = time();
			$strTime = date("n-j-Y G:ia");

			$strQuery = "INSERT INTO www_admin.sessionmgmt_session_storage SET SessionID='".addslashes($this->strSessionID)."', SessionData='', LastTouchDate='".$iTime."', LastTouchDateStr='".addslashes($strTime)."'";
			$this->classSqlQuery->MySQL_Queries($strQuery);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SessionMgmt_TouchSession()
	{
		try
		{
			$strQuery = "DELETE FROM www_admin.sessionmgmt_session_storage WHERE LastTouchDate<".(time() - ini_get("session.gc_maxlifetime"));
			$this->classSqlQuery->MySQL_Queries($strQuery);

			$strQuery = "SELECT ID FROM www_admin.sessionmgmt_session_storage WHERE SessionID='".addslashes($this->strSessionID)."' ORDER BY ID DESC";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			if (count($aResults) == 0)
				$this->SessionMgmt_CreateSession();
			elseif(count($aResults) > 1) {
				$this->SessionMgmt_DestroySession();
				$this->SessionMgmt_CreateSession();
			}
			else {
				$iTime = time();
				$strTime = date("n-j-Y G:ia");

				$strQuery = "UPDATE www_admin.sessionmgmt_session_storage SET LastTouchDate='".$iTime."', LastTouchDateStr='".addslashes($strTime)."' WHERE SessionID='".addslashes($this->strSessionID)."'";
				$this->classSqlQuery->MySQL_Queries($strQuery);
			}

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SessionMgmt_DestroySession()
	{
		try
		{
			$strQuery = "DELETE FROM www_admin.sessionmgmt_session_storage WHERE SessionID='".addslashes($this->strSessionID)."'";
			$this->classSqlQuery->MySQL_Queries($strQuery);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}

