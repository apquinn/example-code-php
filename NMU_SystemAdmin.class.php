<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class SystemAdmin
{
	private $strBackFileLocation = "/htdocs/Webb/HeaderRebuildBackups/";
	protected $classSqlQuery = "";


	function __construct($aArgs=[])
	{
		try
		{
			$this->classSqlQuery = new SqlDataQueries();

		}

		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SystemAdmin_ServerBan($iIP, $iCurrentTime, $strUserAgent, $strScriptURL, $strProtectedForm, $strWhose)
	{
		try
		{
			$strQuery = 'INSERT INTO cms_form_gateway (`ip_addr`, `submit_date`, `user_agent`, `script_url`, `ban`, `form`, `whose`) VALUES (\''.$iIP.'\', \''.$iCurrentTime.'\', \''.$strUserAgent.'\', \''.$strScriptURL.'\', \'1\', \''.$strProtectedForm.'\', \''.$strWhose.'\');';
			$this->classSqlQuery->MySQL_Queries($strQuery);

			//fail2ban is watching our error logs for this message and will trigger a ban if it is seen
			error_log('ban-this-user', 0);
			echo '<h1>Banned</h1><p>Your IP address has been banned from this server.  Please contact <a href="mailto:edesign@nmu.edu">edesign@nmu.edu</a> if you believe this ban was not warranted.</p>';

			exit;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SystemAdmin_CheckDBSize($strDBName)
	{
		try
		{
			$strWhere = "";
			if ($strDBName != "")
				$strWhere = " WHERE SCHEMA_NAME='".addslashes($strDBName)."'";

			$strQuery = "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ".$strWhere." ORDER BY SCHEMA_NAME";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			$iTotal = 0;
			foreach ($aResults as $aRow)
				$iTotal += $this->SystemAdmin_ListSizes($aRow['SCHEMA_NAME'], "", false);

			if ($iTotal > 0)
				print'<div class="col col-lw col-text-right">Total Size</div><div class="col">'.round($iTotal / 1000, 2).' GB</div>';

			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SystemAdmin_CheckTableSize($strDBName, $strTableName)
	{
		try
		{
			$this->SystemAdmin_ListSizes($strDBName, $strTableName, true);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function SystemAdmin_ListSizes($strDBName, $strTableName, $bShowTables)
	{
		try
		{
			if ($strDBName == "" && $strTableName != "")
				throw new Exception("If you send a table name, you must also send a database name.");

			if (!isset($GLOBALS['SystemAdmin_ListSizes']) || $GLOBALS['SystemAdmin_ListSizes'] == "")
			{
				print'<style>
				.col {
					display:inline-block;
					padding-right:10px;
				}

				.col-w {
					min-width:475px;
				}

				.col-text-right {
					text-align:right;
				}
				
				.col-lw {
					min-width:275px;
				}
				</style>';
			}

			$strWhere = "";
			if ($strDBName != "")
				$strWhere = " WHERE TABLE_SCHEMA='".$strDBName."' ";
			if ($strTableName != "")
				$strWhere .= " AND table_name='".$strTableName."' ";

			$strQuery = "SELECT TABLE_SCHEMA, table_name AS 'Table', round(((data_length + index_length) / 1024 / 1024), 2) 'size' FROM information_schema.TABLES ".$strWhere." ORDER BY size DESC";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			$iSize = 0;
			foreach ($aResults as $aRow)
			{
				if ($bShowTables)
				{
					print'<div class="col col-w">'.$aRow['TABLE_SCHEMA'].' - '.$aRow['Table'].'</div><div class="col">'.round($aRow['size'], 2).' mb</div>';
					print'<div style="clear:both;"></div>';
				}

				$iSize += $aRow['size'];
			}

			if (!$bShowTables && $iSize > 0)
				print'<div class="col col-lw">'.$strTableName.'</div><div class="col">'.round($iSize, 2).' mb</div>';
			elseif ($bShowTables && $iSize > 0)
				print'<div class="col col-w col-text-right">Total Size</div><div class="col">'.round($iSize, 2).' mb</div>';
			print'<div style="clear:both; padding-bottom:10px;"></div>';

			return $iSize;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SystemAdmin_Print($aArgs, $strType)
	{
		try
		{
			$strPreText = "";

			$objToPrint = $aArgs[0];
			if (count($aArgs) >= 2)
				$strPreText = $aArgs[1];

			if ($strPreText == "")
			{
				if (!isset($GLOBALS['PrintR_PRETEXT']) || $GLOBALS['PrintR_PRETEXT'] == "")
					$GLOBALS['PrintR_PRETEXT'] = 1;
				else
					$GLOBALS['PrintR_PRETEXT']++;

				$strPreText = $GLOBALS['PrintR_PRETEXT'];
			}


			if (SystemAdmin::SystemAdmin_Debug_GetMode() || $strType == "Everyone")
			{
				if ($strType == "Commandline")
					$strBreak = "\n";
				else
					$strBreak = "<br>";

				if ($strType == "Strip" && (is_array($objToPrint) || is_object($objToPrint)))
					$objToPrint = json_decode(str_replace("<", "", json_encode($objToPrint)));
				elseif ($strType == "Strip" && (!is_array($objToPrint) && !is_object($objToPrint)))
					$objToPrint = str_replace("<", "", $objToPrint);

				if (is_array($objToPrint) || is_object($objToPrint))
				{
					print'<div style="color:red; font-size:larger">'.$strPreText.'</div>: '.$strBreak.'<pre>';
					print_r($objToPrint);
					print"</pre>".$strBreak.$strBreak;
				}
				else
					print '<div style="color:red; font-size:larger">'.$strPreText.'</div>: '.$objToPrint.$strBreak.$strBreak;
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	public static function SystemAdmin_Debug_GetMode()
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


	public static function SystemAdmin_Debug_SetMode($strAction)
	{
		try
		{
			if ($strAction == "set")
				setcookie("umc_debug_flag", "debug", 0, "/", "nmu.edu", false, false);
			else
				setcookie("umc_debug_flag", "", 0, "/", "nmu.edu", false, false);
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	public static function SystemAdmin_Debug_CheckDomain()
	{
		try
		{
			if(isset($_SERVER['HTTP_HOST']))
			{
				$aInfo = SystemAdmin::SystemAdmin_Debug_GetAdminInfo();

				foreach($aInfo as $aEntry)
					foreach($aEntry['Domains'] as $strEntry)
						if($_SERVER['HTTP_HOST'] == $strEntry)
							return true;
			}

			return false;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	public static function SystemAdmin_Debug_CheckSSOUserLoggedIn()
	{
		try
		{
			if(isset($_SESSION[Const_sLoginName]))
			{
				$aInfo = SystemAdmin::SystemAdmin_Debug_GetAdminInfo();

				foreach($aInfo as $aEntry)

					if($_SESSION[Const_sLoginName] == $aEntry['Username'])
						return true;
			}

			return false;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private static function SystemAdmin_Debug_GetAdminInfo()
	{
		try
		{
			$aDrewIPs = ["198.110.203.200",  "198.110.203.172",  "198.110.203.173",  "198.110.203.128",  "198.110.203.197",  "204.38.63.79"];
			$aDrewDomains = ["aqvm1.nmu.edu",  "aqvm2.nmu.edu"];

			$aEricIPs = ["198.110.203.106",  "198.110.203.107",  "198.110.203.203",  "198.110.203.73",  "204.38.63.71"];
			$aEricDomains = ["ejvm.nmu.edu",  "ejvm1.nmu.edu",  "ejvm2.nmu.edu"];

			$aMikeIPs = ["198.110.203.215",  "198.110.203.113",  "204.38.63.68"];
			$aMikeDomains = ["mkvm1.nmu.edu",  "mkvm2.nmu.edu"];

			$aCmdIPs = ["::1"];
			$aLocalIPs = ["127.0.0.1"];

			$aAllInfo =[["GroupName" => "Drew", "Username" => "aquinn", "IPGroup" => $aDrewIPs, "Domains" => $aDrewDomains, "DebugPath" => "/htdocs/Webb/DebugDump/Drew.txt"],
						["GroupName" => "Eric", "Username" => "ericjohn", "IPGroup" => $aEricIPs, "Domains" => $aEricDomains, "DebugPath" => "/htdocs/Webb/DebugDump/Eric.txt"],
						["GroupName" => "Mike", "Username" => "mkinnune", "IPGroup" => $aMikeIPs, "Domains" => $aMikeDomains, "DebugPath" => "/htdocs/Webb/DebugDump/Mike.txt"],
						["GroupName" => "CmdLine", "Username" => "", "IPGroup" => $aCmdIPs, "Domains" => [], "DebugPath" => "/htdocs/Webb/DebugDump/Commandline.txt"],
						["GroupName" => "LocalHost", "Username" => "", "IPGroup" => $aLocalIPs, "Domains" => [], "DebugPath" => "/htdocs/Webb/DebugDump/Localhost.txt"]];

			return $aAllInfo;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}