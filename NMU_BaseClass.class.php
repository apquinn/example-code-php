<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class BaseClass
{
	protected $classSession;
	protected $bIsPersistant;
	protected $strObjName = "";
	protected $strStorageName = "BaseClassNameStorage";
	protected $classSqlQuery;

	protected function __construct($aArgs=[])
	{
		try
		{
			$this->classSession = new SessionMgmt();

			$this->bIsPersistant = $aArgs['bIsPersistant'] ?? false;

			$this->BaseClass_StoreNameAndType($aArgs);

			if($aArgs['bIsPersistant'] === true && (!isset($aArgs['bReset']) || $aArgs['bReset'] === false))
				$this->BaseClass_SelfLoad();

			$this->BaseClass_StoreSelf();
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	protected function BaseClass_StoreNameAndType($aArgs)
	{
		try
		{
			if (($aArgs['strObjName'] == null || $aArgs['strObjName'] == "") && $aArgs['bIsPersistant'] != false)
				throw new Exception('You must provide an object name for a persistant class. It can be anything. It is used so that it can be loaded when you request the object on another page. (failed in BaseClass_StoreName)');

			elseif ($aArgs['strObjName'] != "" && in_array($aArgs['strObjName'], $GLOBALS[$this->strStorageName]) && $aArgs['bIsPersistant'] == false)
				throw new Exception('The object name "'.$this->strObjName.'" has already been used. It must be unique for a given page. On future pages you will need to reference it using it name if you want it to be persistant. (failed in BaseClass_StoreName)');

			elseif ($aArgs['strObjName'] == null || $aArgs['strObjName'] == "") 
			{
				$iUniqueName = str_replace(" ", "", str_replace("0.", "", microtime()));
				while(in_array($iUniqueName, $GLOBALS[$this->strStorageName]))
					$iUniqueName .= 1;

				$GLOBALS[$this->strStorageName][] = $iUniqueName;
				$this->strObjName = $iUniqueName;
			}
			elseif ($this->strObjName != null ) 
			{
				$GLOBALS[$this->strStorageName][] = $aArgs['strObjName'];
				$this->strObjName = $aArgs['strObjName'];
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function BaseClass_SelfDump()
	{
		try
		{
			PrintR($this);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	protected function BaseClass_SelfLoad()
	{
		try
		{
			$aSessions = $this->classSession->SessionMgmt_Select($this->strObjName);
			foreach ($aSessions as $strName=>$strValue)
				$this->{$strName} = $strValue;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	protected function BaseClass_StoreSelf()
	{
		try
		{
			$this->classSession->SessionMgmt_Set($this->strObjName, $this);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function __destruct()
	{
#print'<pre>';
#print_r($this);
#print'</pre>';
#		if(!$this->bIsPersistant) {
#			$classSession->SessionMgmt_DeleteValue($this->strObjName);
#		}
	}
}



