<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class ErrorHandler extends BaseClass
{
	protected $classSqlQuery;
	private $strSessionName = 'ErrorHandler';
	public static $bNotifyAdmin = false;

	function __construct($aArgs=[])
	{
		try
		{
			parent::__construct($aArgs);
			$this->classSqlQuery = new SqlDataQueries();
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	public static function ErrorHandler_MessageDisplay()
	{
		try
		{
			$iPage = 0;
			if (isset($_REQUEST["page"]) && $_REQUEST["page"] != "")
				$iPage = $_REQUEST["page"];
			elseif(CORE_GetQueryStringVar("page") != "")
				$iPage = CORE_GetQueryStringVar("page");

			if($iPage > 0)
			{
				$classSqlQuery = new SqlDataQueries();
				$strQuery = "SELECT  Title FROM cms_admin_comp WHERE ID=".$iPage;
				$aResults = $classSqlQuery->MySQL_Queries($strQuery);

				print'<h2>'.$aResults[0]['Title'].'</h2>';
			}


			if (isset($_SESSION['cmsAdmin_PositiveOutcome']) && $_SESSION['cmsAdmin_PositiveOutcome'] != "")
				print'<div class="row"><div class="col-sm-10" style="color:#cc6633">'.$_SESSION['cmsAdmin_PositiveOutcome'].'</div></div>';
			$_SESSION['cmsAdmin_PositiveOutcome'] = "";

			if (isset($_SESSION['cmsAdmin_PositiveOutcome']) && $_SESSION['cmsAdmin_PositiveOutcome'] != "")
				print'<div class="row"><div class="col-sm-10" style="color:#cc6633">'.$_SESSION['cmsAdmin_PositiveOutcome'].'</div></div>';
			$_SESSION['cmsAdmin_NoticeOutcome'] = "";


			$classSession = new SessionMgmt();
			$aValues = $classSession->SessionMgmt_Select("ErrorHandler-Warning");
			if ($aValues["outcome"] == "Warning")
			{
				print'<div class="row"><div class="col-sm-10" style="color:#EB984E">'.$aValues["outcome-message"].'</div></div>';

				if(isset($aValues['outcome-corrections']) && count($aValues['outcome-corrections']) > 0)
				{
					print'<div class="row" style="padding:0px 0px 15px 0px;"><ul>';
					foreach ($aValues['outcome-corrections'] as $strMessage)
						print'<div class="col-sm-10"><li>'.$strMessage.'</li></div>';
					print'</ul></div>';
				}

				$classSession->SessionMgmt_DeleteValue("ErrorHandler-Warning");
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	public static function ErrorHandler_HandleCorrections($aIssues)
	{
		try
		{
			if (isset($aIssues) && count($aIssues) > 0)
			{
				$aValues = [];
				$classSession = new SessionMgmt();

				$aValues["outcome"] = "Warning";
				$aValues["outcome-message"] = "The following issues need to be corrected:";
				$aValues["outcome-corrections"] = $aIssues;
				$classSession->SessionMgmt_Set("ErrorHandler-Warning", $aValues);

				$_REQUEST[Const_Action] = $_REQUEST[Const_Action] ?? "";
				$_REQUEST[Const_Phase] = $_REQUEST[Const_Phase] ?? "";
				$_REQUEST[Const_ElementID] = $_REQUEST[Const_ElementID] ?? "";
				$_REQUEST[Const_Subaction] = $_REQUEST[Const_Subaction] ?? "";

				$strURL = CORE_GetURL(Const_ParentURL, $_REQUEST[Const_Action], $_REQUEST[Const_Phase], $_REQUEST[Const_ElementID], $_REQUEST[Const_Subaction], Const_Error);
				header("Location: ".$strURL);
				die;
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	public static function ErrorHandler_CatchError($ex, $aAdditional = [])
	{
		try
		{
			$classSqlQuery = new SqlDataQueries();
			$classSqlQuery->SpecifyDB(Const_connCharlieHost, Const_connCharlieDB, Const_connCharlieUser, Const_connCharlieUser);

			if (is_object($ex))
				$strErrorMsg = $ex->getMessage();
			else
				$strErrorMsg = $ex;

			$strErrorHost = "";
			if ((!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] == "") && (isset($_SERVER['SSH_CONNECTION']) && $_SERVER['SSH_CONNECTION'] != ""))
			{
				$aParts = explode(" ", $_SERVER["SSH_CONNECTION"]);
				exec("nsLookup ".trim($aParts[2]), $aResult1, $junk);
				$aParts = explode("name = ", $aResult1[3]);
				$strErrorHost = substr($aParts[1], 0, strlen($aParts[1]) - 1);
			}
			elseif (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != "")
				$strErrorHost = $_SERVER['HTTP_HOST'];

			$strBasicDetailsMsg = "";
			foreach($aAdditional as $strAdditional)
				$strBasicDetailsMsg .= $strAdditional;

			if(isset($_SESSION[Const_sLoginName]) && $_SESSION[Const_sLoginName] != "")
				$strBasicDetailsMsg .= "<br><br>Username: ".$_SESSION[Const_sLoginName];

			$classSessionMgmt = new SessionMgmt();
			$aValues = $classSessionMgmt->SessionMgmt_SelectAll();

			$aValues['ScreenElements'] = '';
			$_SESSION['ScreenElements'] = '';
			$aBacktrace = debug_backtrace(1, 1);

			$strQuery = "INSERT INTO www_admin.php_log SET 
						 Application='',
						 Function='',
						 Message='".addslashes($strErrorMsg)."',
						 Level='Error',
						 BasicDetails='".addslashes($strBasicDetailsMsg)."',
						 Details='".addslashes(serialize($aBacktrace))."',
						 ServerVars='".addslashes(serialize($_SERVER))."',
						 SessionMgrVars='".addslashes(serialize($aValues))."',
						 SessionVars='".addslashes(serialize($_SESSION))."',
						 Host='".addslashes($strErrorHost)."',
						 EventDateTime=".time().",
						 EventStrDate='".date("Y/m/d G:i:s", time())."'";
			$aResults = $classSqlQuery->MySQL_Queries($strQuery);

			#$classMail = new SendMail(0);
			#if (ErrorHandler::$bNotifyAdmin == true)
			#	$classMail->SendMail_Send("BaseClass_ErrorHandler", $strErrorMsg, "aquinn@nmu.edu", "aquinn@nmu.edu", "aquinn@nmu.edu");

			$strMessage = '<p>An error has occured. Please try again or for more help, contact us at <a mailto="edesign@nmu.edu">edesign@nmu.edu<a>.</p>
						   <p>ErrorLog ID: '.number_format($aResults['ID']).'</p>';

			if(SystemAdmin::SystemAdmin_Debug_GetMode())
				$strMessage .= '<p>'.$strErrorMsg.'</p><p>'.$strBasicDetailsMsg.'</p>';

			print $strMessage;

			PrintR($_SERVER);
			PrintR($_SESSION);
			PrintR($aBacktrace);
			PrintR($strErrorHost);
			die;
		}
		catch (Exception $ex)
		{
			print'A severe error has occured. Please try again or contact the NMU web team at edesign@nmu.edu';
			die;
		}
	}

}