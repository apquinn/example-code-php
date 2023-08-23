<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class ProcessJavascript
{
	private static $strDividerStart = "##########-AsyncFunctions_Start-##########";
	private static $strDividerStop = "##########-AsyncFunctions_End-##########";

	function __construct()
	{
		try
		{
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	static function ProcessJavascript_WrapOutput($strJava)
	{
		try
		{
			$strOutput = "";
			if (ProcessJavascript::ProcessJavascript_DetermineIfAsync())
			{
				$strOutput .= ProcessJavascript::$strDividerStart;
			}
			else
			{
				/** @noinspection SyntaxError */
				$strOutput .= " \n".'<script>jQuery(document).ready(function () { '."\n"; ## Ignore
			}

			$strOutput .= $strJava;

			if (ProcessJavascript::ProcessJavascript_DetermineIfAsync())
			{
				$strOutput .= ProcessJavascript::$strDividerStop;
			}
			else
			{
				$strOutput .= " \n".'}); </script>'."\n";
			} ## Ignore

			print $strOutput;
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function ProcessJavascript_CallEvalReturnFunctions($strDivID)
	{
		try
		{
			return 'ProcessJavascript_ProcessResults($strData, "'.$strDivID.'")';
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private static function ProcessJavascript_DetermineIfAsync()
	{
		try
		{
			$bIsAync = false;
			if (substr($_SERVER['SCRIPT_URI'], -strlen("Functions.php")) == "Functions.php")
			{
				$bIsAync = true;
			}

			return $bIsAync;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}




