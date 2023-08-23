<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
	// tell people trying to access this file directly goodbye...
	exit('This file can not be accessed directly...');
}

class SqlDataQueries
{
	private $host = Const_connHost;
	private $dbname = Const_connDB;
	private $user = Const_connUser;
	private $password = Const_connPSW;
	private $dbConnection;
	private $dbTransEnabled = false;

	function __construct()
	{
        try
        {
    		return Const_Success;
        }
        catch (Exception $ex)
        {
			ErrorHandler::ErrorHandler_CatchError($ex);
        }
	}

	function __destruct()
	{
		try
		{
			$this->Disconnect();
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function GetConnectInfo()
	{
		try
		{
			$aInfo[] = $this->host;
			$aInfo[] = $this->dbname;
			$aInfo[] = $this->user;
			$aInfo[] = $this->password;
			$aInfo[] = $this->dbConnection;
			$aInfo[] = $this->dbTransEnabled;
			return $aInfo;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function SpecifyDB($strHost, $strDB, $strUser, $strPassword)
	{
		try
		{
			if ($strHost != "")
				$this->host = $strHost;
			if ($strDB != "")
				$this->dbname = $strDB;
			if ($strUser != "")
				$this->user = $strUser;
			if ($strPassword != "")
				$this->password = $strPassword;
			return Const_Success;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function Transaction_Start()
	{
		try
		{
			$this->Connect();
			$this->dbConnection->autocommit(FALSE);
			$this->dbTransEnabled = true;
			return Const_Success;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function Transaction_Commit()
	{
		try
		{
			if($this->dbTransEnabled == true)
			{
				$this->dbTransEnabled = false;
				$this->dbConnection->commit();
			}

			return Const_Success;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function Transaction_Rollback()
	{
		try
		{
			if ($this->dbTransEnabled == true)
			{
				$this->dbTransEnabled = false;
				$this->dbConnection->rollback();
			}
			return Const_Success;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function MySQL_Queries($strQuery)
	{
		try
		{
			if (isset($_SESSION['Counter']))
				$_SESSION['Counter'] += 1;
			else
				$_SESSION['Counter'] = 1;

			$aResults = [];

			$this->Connect();
			if (!$objResult = $this->dbConnection->query($strQuery)) 
			{
				$strErrMessage = $this->dbConnection->error;

				if ($this->dbTransEnabled)
					$this->Transaction_Rollback();
				ErrorHandler::ErrorHandler_CatchError("MySQL Error: ".$strErrMessage, [$strQuery]);
			}

			if (strpos(' SELECT ', $this->FirstWord($strQuery)))
			{
				if ($objResult->num_rows > 0)
				{
					$indx = 0;
					while ($aResults[$indx] = $objResult->fetch_assoc())
						$indx++;

					unset($aResults[$indx]);
				}

				$objResult->close();
			}

			if (strpos(' INSERT UPDATE DELETE', $this->FirstWord($strQuery)))
			{
				$aResults['rows'] = $this->dbConnection->affected_rows;
				if ($this->FirstWord($strQuery) == 'INSERT')
				{
					$aResults['insertid'] = $this->dbConnection->insert_id;
					$aResults['ID'] = $this->dbConnection->insert_id;
				}
			}

			if (!$this->dbTransEnabled)
				$this->Disconnect();

			return $aResults;
		}
		catch (Exception $ex)
		{
			if ($this->dbTransEnabled)
				$this->Transaction_Rollback();

			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	function Fetch_Fields($strTable)
	{
		try
		{
			$arrFields = [];

			$this->Connect();
			if (!$objResult = $this->dbConnection->query("SELECT * FROM `".$strTable."` LIMIT 1")) {
				if ($this->dbTransEnabled)
					$this->Transaction_Rollback();
				ErrorHandler::ErrorHandler_CatchError("MySQL Error: ".$this->dbConnection->error);
			}
			$objFields = $objResult->fetch_fields();

			foreach ($objFields as $field)
				$arrFields[$field->name] = $field->name;
			return $arrFields;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function Connect()
	{
		try
		{
			if (!($this->dbConnection))
			{
				$this->dbConnection = new mysqli($this->host, $this->user, $this->password, $this->dbname);
				if ($this->dbConnection->connect_errno) {
					if ($this->dbTransEnabled)
						$this->Transaction_Rollback();
					ErrorHandler::ErrorHandler_CatchError("Unable to connect to DB: ".$this->dbConnection->connect_error);
				}

				if (!$objResult = $this->dbConnection->query("SET NAMES utf8")) {
					if ($this->dbTransEnabled)
						$this->Transaction_Rollback();
					ErrorHandler::ErrorHandler_CatchError("MySQL Error: ".$this->dbConnection->error);
				}
			}
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function Disconnect()
	{
		try
		{
			if ($this->dbConnection)
				$this->dbConnection->close();
			$this->dbConnection = false;
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}

	private function FirstWord($strString)
	{
		try
		{
			$words = preg_split("/[\s,]+/", strtoupper($strString));
			if (count($words) == 0) {
					if ($this->dbTransEnabled)
						$this->Transaction_Rollback();
					ErrorHandler::ErrorHandler_CatchError("Query string appears corrupt or empty. ");
			}

			return $words[0];
		}
		catch (Exception $ex)
		{
			ErrorHandler::ErrorHandler_CatchError($ex);
		}
	}
}




