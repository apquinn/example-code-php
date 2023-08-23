<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class MiscFunctions
{
	static function MiscFunctions_StartSession()
	{
		try
		{
			$strResult = session_id();
			if (empty($strResult))
				session_start();

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}


