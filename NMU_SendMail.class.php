<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}


class SendMail
{
	private $objMailer;
	private $classSqlQuery;
	private $strPlainTextMsg = "";

	function __construct($iDebugLevel)
	{
		try
		{
			require_once '/htdocs/cmsphp/Includes/vendor/autoload.php';
			$this->objMailer = new PHPMailer\PHPMailer\PHPMailer();

			//Enable SMTP debugging: 0 = off (for production use), 1 = client messages, 2 = client and server messages, 4 includes all messages
			if ($iDebugLevel == "")
				$iDebugLevel = 0;
			$this->objMailer->SMTPDebug = $iDebugLevel;

			$this->objMailer->isSMTP();
			$this->objMailer->Host = 'mailgateway.nmu.edu';
			$this->objMailer->Port = 587;
			$this->objMailer->SMTPAuth = true;
			$this->objMailer->AuthType = 'LOGIN';
			$this->objMailer->Username = 'edesign';
			$this->objMailer->Password = 'p1ckl35';
			$this->objMailer->SMTPOptions = [
				'ssl' => [
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true
				]
			];

			$this->objMailer->SMTPSecure = 'tls';
			$this->objMailer->Debugoutput = 'html';
			$this->objMailer->CharSet = 'UTF-8';


			$this->classSqlQuery = new SqlDataQueries();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	function SendMail_SendConfirmation($strPrimaryEmail, $strContactEmail, $bIsAdmin, $bIsApproved, $strType, $strSubmissionText)
	{
		try
		{
			$strCurrentUserEmail = "";
			$strUserFullName = "";

			$aRecipients = [];
			if ($bIsAdmin)
			{
				if (isset($_SESSION[Const_sLoginName]) && $_SESSION[Const_sLoginName] != "")
				{
					$aNameResults = CORE_GetUserInfo($_SESSION[Const_sLoginName]);
					$strCurrentUserEmail = $_SESSION[Const_sLoginName]."@nmu.edu";
					$strUserFullName = $aNameResults['FirstName']." ".$aNameResults['LastName'];
				}

				if ($strCurrentUserEmail != $strPrimaryEmail && $strContactEmail != $strPrimaryEmail)
				{
					$aRecipients = $this->SendMail_SendConfirmationAddToArray($aRecipients, $strPrimaryEmail);
				}

				if ($strCurrentUserEmail != $strContactEmail && $strContactEmail != "")
				{
					$aRecipients = $this->SendMail_SendConfirmationAddToArray($aRecipients, $strContactEmail);
				}

				if (count($aRecipients) > 0)
				{
					if ($bIsApproved)
					{
						$this->SendMail_Send($strType." Submission Confirmation", 'The submission "'.$strSubmissionText.'" was made by '.$strUserFullName.'.', $aRecipients, "", "");
					}
					elseif ($strType == "Question")
					{
						$this->SendMail_Send($strType." Submission Confirmation", 'The question "'.$strSubmissionText.'" was answer by '.$strUserFullName.'.', $aRecipients, "", "");
					}
					else
					{
						$this->SendMail_Send($strType." Submission Confirmation", 'The submission "'.$strSubmissionText.'" was approved by '.$strUserFullName.'.', $aRecipients, "", "");
					}
				}
			}
			else
			{
				if ($strContactEmail != "")
				{
					$this->SendMail_Send($strType." Submission Confirmation", 'Thank you for submitting "'.$strSubmissionText.'". We\'ll let you know when we\'ve taken a look at it.', $strContactEmail, "", "");
				}
				if ($strPrimaryEmail != "")
				{
					$aRecipients = $this->SendMail_SendConfirmationAddToArray($aRecipients, $strPrimaryEmail);
					$this->SendMail_Send($strType." Submission Confirmation", 'The submission "'.$strSubmissionText.'" is awaiting you\'re response.', $aRecipients, "", "");
				}
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SendMail_SendConfirmationAddToArray($aArray, $strEmailString)
	{
		try
		{
			if (!is_array($aArray))
			{
				$aArray = [];
			}

			if (strstr($strEmailString, ","))
			{
				$aParts = explode(",", $strEmailString);
				foreach ($aParts as $strEmail)
				{
					$aArray[] = trim($strEmail);
				}
			}
			else
			{
				$aArray[] = trim($strEmailString);
			}

			return $aArray;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_QueueMessage($strSubject, $strMsg, $objRecipients, $strSender, $strSenderName)
	{
		try
		{
			if ($objRecipients == "")
			{
				throw new exception("Receipient required.");
			}
			if ($strSubject == "")
			{
				throw new exception("Subject required.");
			}

			if (!is_array($objRecipients) && strstr($objRecipients, ","))
			{
				$aTemp = explode(",", $objRecipients);
				$aAnotherTemp = [];
				foreach ($aTemp as $strRecipient)
				{
					$aAnotherTemp[] = trim($strRecipient);
				}

				$objRecipients = $aAnotherTemp;
			}

			$strQuery = "INSERT INTO www_admin.sendmail_queue SET
						 Recipients='".addslashes(serialize($objRecipients))."',
						 Carrier='',
						 Subject='".addslashes($strSubject)."',
						 Message='".addslashes($strMsg)."',
						 Sender='".addslashes($strSender)."',
						 SenderName='".addslashes($strSenderName)."',
						 DateTimeQueued=".time();

			$this->classSqlQuery->MySQL_Queries($strQuery);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_Send($strSubject, $strMsg, $objRecipients, $strSender, $strSenderName)
	{
		try
		{
			$this->SendMail_SendCommon($strSubject, $strMsg, $objRecipients, $strSender, $strSenderName, 0);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_SetPlainTextMsg($strPlainTextMsg)
	{
		try
		{
			$this->strPlainTextMsg = $strPlainTextMsg;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_ProcessQueueBegin()
	{
		try
		{
			$strQuery = "SELECT pid FROM www_admin.sendmail_queue_log WHERE pid!=0 ORDER BY ID DESC LIMIT 1";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
			if (count($aResults) == 0 || !file_exists("/proc/".$aResults[0]['pid']))
			{
				$iPID = exec(exec("which php").' /htdocs/cmsphp/Includes/2015_FunctionsCommonFunctions.php "SendMail_ProcessQueueGroup" >/dev/null 2>&1 & echo $!', $out);
				$this->SendMail_QueuedMessage_Log("Begin processing queue", false, $iPID);

				print'<p>Queue processing started successfully.</p>';
			}
			else
			{
				$this->SendMail_QueuedMessage_Log("Aborted start attempt, pid:".$aResults[0]['pid']." is still running. ", false, "");
				print'<p>Process id '.$aResults[0]['pid'].' is still running. Please stop the process and try again.</p>';
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_ProcessQueueGroup()
	{
		try
		{
			$iLimit = 50;

			$strMsg = "Beginning: ";
			$strQuery = "SELECT * FROM www_admin.sendmail_queue ORDER BY ID ASC LIMIT ".$iLimit;
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);

			$strMsg .= "processing ".count($aResults).". ";

			$iCount = 0;
			if (count($aResults) > 0)
			{
				foreach ($aResults as $aRow)
				{
					$this->SendMail_SendCommon($aRow['Subject'], $aRow['Message'], unserialize($aRow['Recipients']), $aRow['Sender'], $aRow['SenderName'], time());

					$strQuery = "DELETE FROM www_admin.sendmail_queue WHERE ID=".$aRow['ID'];
					$this->classSqlQuery->MySQL_Queries($strQuery);
					$iCount++;
				}
			}

			$strMsg .= "completed ".$iCount.". ";

			$strQuery = "SELECT * FROM www_admin.sendmail_queue LIMIT 1";
			$aResults = $this->classSqlQuery->MySQL_Queries($strQuery);
			if (count($aResults) == 0)
			{
				$this->SendMail_QueuedMessage_Log($strMsg, false, "");
				$this->SendMail_QueuedMessage_Log("I am done processing.", false, "");
				return false;
			}
			else
			{
				$this->SendMail_QueuedMessage_Log($strMsg, true, "");
				return true;
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_SendCommon($strSubject, $strMsg, $objRecipients, $strSender, $strSenderName, $iTimeQueued)
	{
		try
		{
			if ($strSubject != "" && $strMsg != "" && $objRecipients != "")
			{
				$aCleanData = $this->SendMail_CleanAddresses($objRecipients, $strSender, $strSenderName);

				if (!TestAid::TestAid_IsGhost() && $_SERVER['HTTP_HOST'] != "aqvm1.nmu.edu" && $_SERVER['HTTP_HOST'] != "aqvm2.nmu.edu")
				{
					$this->SendMail_ResetMailer();
					if($aCleanData['Sender'] == "")
						throw new Exception("PHP Mailer Error: You must provide a sender. ");
					$this->objMailer->addReplyTo($aCleanData['Sender'], $aCleanData['SenderName']);
					$this->objMailer->setFrom($aCleanData['Sender'], $aCleanData['SenderName']);

					if($strSubject == "")
						throw new Exception("PHP Mailer Error: You must provide a subject. ");
					$this->objMailer->Subject = $strSubject;

					$this->objMailer->Body = $strMsg;
					if ($this->SendMail_SendCommon != "")
						$this->objMailer->AltBody = $this->SendMail_SendCommon;
					else
						$this->objMailer->AltBody = "To view the message, please use an HTML compatible email viewer.";

					if(count($aCleanData['Recipients']) == 1)
						$this->objMailer->addAddress($aCleanData['Recipients'][0]['Email'], $aCleanData['Recipients'][0]['Name']);
					elseif(count($aCleanData['Recipients']) > 0)
					{
						foreach ($aCleanData['Recipients'] as $aRecipient)
							$this->objMailer->addBCC($aRecipient['Email'], $aRecipient['Name']);
					}
					else
						throw new Exception("PHP Mailer Error: You must provide at leasat one receipient. ");

					if (!$this->objMailer->send())
						throw new Exception($this->objMailer->ErrorInfo);
				}

				$this->SendMail_SendCommon = "";

				$this->SendMail_Log($strSubject, $strMsg, $aCleanData, $iTimeQueued);
			}
			elseif ($objRecipients == "")
			{
				$this->SendMail_QueuedMessage_Log("Attempt to send message with no recipient. Message was: ".$strMsg, true, "");
				throw new exception("Attempt to send message with no recipient.");
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_FormatEmailAddress($strEmail, $strName)
	{
		try
		{
			if ($strEmail != "")
			{
				if ($strEmail == $strName || $strName == "")
				{
					$aResult = $this->SendMail_CleanAnAddress($strEmail, '');
				}
				else
				{
					$aResult = $this->SendMail_CleanAnAddress($strEmail.",".$strName, '');
				}

				$strEmail = '<a href="mailto:'.$aResult['Email'].'">'.$aResult['Name'].'</a>';

				return $strEmail;
			}

			return "";
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_CommandLineSend($strSubject, $strMsg, $strRecipients, $strFrom, $strFromName)
	{
		try
		{
			if ($strFrom == "")
			{
				$strFrom = "edesign@nmu.edu";
			}
			if ($strFromName == "")
			{
				$strFromName = "NMU e-design team";
			}

			$this->objMailer->AddReplyTo($strFrom, $strFromName);
			$this->objMailer->SetFrom($strFrom, $strFromName);
			$this->objMailer->Subject = $strSubject;

			#$this->objMailer->msgHTML(file_get_contents('contents.html'), dirname(__FILE__)); #Read an HTML message body from an external file, convert referenced images to embedded,

			$this->objMailer->Body = $strMsg;
			$this->objMailer->AltBody = "To view the message, please use an HTML compatible email viewer.";
			# $this->objMailer->addAttachment('images/phpmailer_mini.png');

			$this->objMailer->addAddress($strRecipients, '');

			if (!$this->objMailer->send())
			{
				throw new Exception("PHP Mailer Error: ".$this->objMailer->ErrorInfo);
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SendMail_SelfDump()
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

	function SendMail_GetPauseTime()
	{
		try
		{
			return 300; # 300 = 5 minutes
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}


	private function SendMail_ResetMailer()
	{
		try
		{
			$this->objMailer->ClearReplyTos();
			$this->objMailer->ClearAttachments();
			$this->objMailer->ClearCustomHeaders();
			$this->objMailer->ClearAllRecipients();
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SendMail_CleanAddresses($objRecipients, $strSender, $strSenderName)
	{
		try
		{
			$aFinal = [];
			$aCleanReceiptients = [];

			if (is_array($objRecipients))
			{
				foreach ($objRecipients as $strEmail)
				{
					$aCleanReceiptients = $this->SendMail_CleanAnAddress($strEmail, $aCleanReceiptients);
				}
			}
			else
			{
				$aCleanReceiptients = $this->SendMail_CleanAnAddress($objRecipients, $aCleanReceiptients);
			}

			if ($strSender == "")
			{
				$strSender = "edesign@nmu.edu";
			}
			if ($strSenderName == "")
			{
				$strSenderName = "NMU e-design team";
			}

			$aFinal['Sender'] = $strSender;
			$aFinal['SenderName'] = $strSenderName;
			$aFinal['Recipients'] = $aCleanReceiptients;

			return $aFinal;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SendMail_CleanAnAddress($strEmail, $aCleanReceiptients)
	{
		try
		{
			$aCleanRecipient = [];
			$aCleanRecipient['Name'] = "";
			$aCleanRecipient['Email'] = "";
			$aCleanRecipient['Combined'] = "";

			if (trim($strEmail) != "")
			{
				if (!strstr($strEmail, "@"))
				{
					$strEmail .= "@nmu.edu";
				}

				/*
    			if(strstr($strRecipient, ","))
    			{
    				$aParts = explode(",", $strRecipient);
    				$aCleanRecipient['Name'] = $aParts[1];
    				$aCleanRecipient['Email'] = $aParts[2];
    			}
                */
				if (strstr($strEmail, "@nmu.edu"))
				{
					$aParts = explode("@", $strEmail);
					$aNameResults = CORE_GetUserInfo($aParts[0]);
					$aCleanRecipient['Name'] = $aNameResults['FirstName']." ".$aNameResults['LastName'];
					$aCleanRecipient['Email'] = $strEmail;
				}
				else
				{
					$aCleanRecipient['Name'] = $strEmail;
					$aCleanRecipient['Email'] = $strEmail;
				}

				if ($aCleanRecipient['Name'] != $aCleanRecipient['Email'])
				{
					$aCleanRecipient['Combined'] = $aCleanRecipient['Email'].",".$aCleanRecipient['Name'];
				}
				else
				{
					$aCleanRecipient['Combined'] = $aCleanRecipient['Email'];
				}

				$aCleanReceiptients[] = $aCleanRecipient;
			}

			return $aCleanReceiptients;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SendMail_Log($strSubject, $strMessage, $aCleanData, $iTimeQueued)
	{
		try
		{
			foreach ($aCleanData['Recipients'] as $aRecipient)
			{
				$strQuery = "INSERT INTO www_admin.sendmail_log SET 
							 RecipientName='".addslashes($aRecipient['Name'])."', 
							 RecipientEmail='".addslashes($aRecipient['Email'])."', 
							 Subject='".addslashes($strSubject)."', 
							 Message='".addslashes($strMessage)."', 
							 Sender='".addslashes($aCleanData['Sender'])."', 
							 SenderName='".addslashes($aCleanData['SenderName'])."', 
							 DateTimeSent=".time().",
							 DateTimeQueued=".$iTimeQueued;
				$this->classSqlQuery->MySQL_Queries($strQuery);
			}
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function SendMail_QueuedMessage_Log($strMsg, $bAddExpected, $iPID)
	{
		try
		{
			if ($iPID == "")
			{
				$iPID = 0;
			}

			$strExpected = "";
			if ($bAddExpected)
			{
				$strExpected = addslashes(date("n-j-Y g:i:s a", (time() + $this->SendMail_GetPauseTime())));
			}

			$strQuery = "INSERT INTO www_admin.sendmail_queue_log SET 
						 Message='".addslashes($strMsg)."', 
						 MessageEventTime='".addslashes(date("n-j-Y g:i:s a", time()))."',
						 ExpectNextEventTime='".$strExpected."',
						 pid=".$iPID;
			$this->classSqlQuery->MySQL_Queries($strQuery);
			return true;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}
