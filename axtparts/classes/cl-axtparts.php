<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: cl-axtparts.php 202 2016-07-17 06:08:05Z gswan $
// Class for AXTParts

interface iaxtparts
{
	public function UserSave($dbh, $uid = false, $entry);
	public function UserRead($dbh, $uid = false, $loginid = false);
	public function UserLogin($dbh, $loginid, $passwd);
	public function UserLogout($dbh);
	
	public function LogSave($dbh, $logtype, $uid, $logmsg);
	public function LogRead($dbh, $uid = false, $logtype = false);
	
	public function SessionKill();
	public function SessionTimeRead();
	public function SessionTimeRemaining();
	public function SessionCheck();
	public function SessionStamp();
	
	public function SessionMeLoad($dbh, $uid);
	public function SessionMeRead();
	public function SessionMeUID();
	public function SessionMeName();
	public function SessionMeLoginID();
	public function SessionMeAdmin();
	public function SessionMePrivilege();
	public function SessionMePrivilegeBit($priv);
	
	public function SessionUserLoad($dbh, $uid);
	public function SessionUserRead();
	public function SessionUserPrivilege();
	public function SessionUserPrivilegeBit($priv);
	
	public function SessionVarSave($var, $value);
	public function SessionVarRead($var);
	public function SessionVarClear($var);
	
	public function MACCreate($datastring);
	public function MACVerify($mac, $datastring);
	
	public function PasswdSalt($len);
	public function Passwd_ssha1($passwd);
	public function PasswordCheck($plainpw, $hashpw);
	
	public function VectorMeTo($uri);
	public function PopMeUp($uri, $popname, $parameters);
	public function PopMeClose();
	public function AlertMeTo($msg);
	public function UpdateParent();

	public function EntryToSQL($dbh, $entry);
	public function SQLCheckRowExists($dbh, $table, $column, $value);
	
	public function RenderOptionList($list, $selected = false, $default = false);
	public function CalcPartNumber($partid, $prefix);
	public function GetDateFromPost($elementname);
	
	public function ReturnCountOf($dbh, $table, $column, $keycol, $keyval);
	public function GetAddressRow($dbh, $cvid);
	public function GetContacts($dbh, $cvid);
	public function GetContact($dbh, $contid);
	public function AddContactToCV($dbh, $cvid, $contactname);
	public function AddNewCV($dbh, $cvname);
	public function getVariantsForAssemblyPart($dbh, $partid);
	public function getVariantDetails($dbh, $vid);
	
	public function FormRender_Tabs($params);

}


class axtparts implements iaxtparts
{
	// **************************************************************
	// GENERAL FUNCTIONS
	// constructor function
	public function 
	__construct()
	{
		return;
	}

	// destructor function
	public function 
	__destruct()
	{
		return;
	}
	
	/**
	* @return array [uid]=uid of user, [status]=true if success, false if error, [error]=error message
	* @param object dbh database object
	* @param string uid (optional). If specified the user record is updated, otherwise a new record is created.
	* @param array entry. [column]=value.
	* @desc Attempts to update/create the user record with the entry.
	*/
	public function 
	UserSave($dbh, $uid = false, $entry)
	{
		require_once("config/config-axtparts.php");
		$rv = array();
		
		if (count($entry) == 0)
		{
			$rv["status"] = false;
			$rv["error"] = "No entry detail.";
			$rv["uid"] = false;
					
			return $rv;
		}

		$s = $this->EntryToSQL($dbh, $entry);
				
		if ($uid === false)
		{
			$q_user = "insert into user ".$s;
			$s_user = $dbh->query($q_user);
			if ($s_user)
				$uid = $dbh->insert_id;
		}
		else 
		{
			$q_user = "update user ".$s
					. "\n where uid='".$dbh->real_escape_string($uid)."'"
					;
			$s_user = $dbh->query($q_user);
		}
		
		if (!$s_user)
		{
			$rv["status"] = false;
			$rv["error"] = $dbh->error;
			$rv["uid"] = false;
		}
		else 
		{
			$rv["status"] = true;
			$rv["error"] = false;
			$rv["uid"] = $uid;
		}
			
		return $rv;
	}
	
	
	/**
	* @return array [user][n][uid], [user][n][loginid], [user][n][passwd], [user][n][name], [user][n][lastlogin], [user][n][ou], [user][n][status], [user][n][privilege], [status]=true if success, false if error, [error]=error message
	* @param object dbh database object
	* @param string uid (optional). If specified the specific uid is read.
	* @param string loginid (optional). If specified the specific loginid is read.
	* @desc Reads either the specified uid or loginid, or all items if none specified.
	*/
	public function 
	UserRead($dbh, $uid = false, $loginid = false)
	{
		require_once("config/config-axtparts.php");
		$rv = array();
		
		$q_user = "select * "
				. "\n from user "
				. "\n left join role on role.roleid=user.roleid "
				;
		if ($uid !== false)
			$q_user .= "where uid='".$dbh->real_escape_string($uid)."' ";
		elseif ($loginid !== false)
			$q_user .= "where loginid='".$dbh->real_escape_string($loginid)."' ";
		$q_user .= "\n order by loginid";
		
		$s_user = $dbh->query($q_user);
		if (!$s_user)
		{
			$rv["user"] = false;
			$rv["status"] = false;
			$rv["error"] = $dbh->error;
		}
		else 
		{
			$n = 0;
			$rv["user"] = array();
			while ($r_user = $s_user->fetch_assoc())
				$rv["user"][$n++] = $r_user;
			$rv["status"] = true;
			$rv["error"] = false;
		}
		
		return $rv;
	}
	
	
	/**
	* @return array [uid]=uid of user if successful, [status]=true if success, false if unsuccessful, [error]=error message
	* @param object dbh database object
	* @param string loginid. The login ID to be authenticated.
	* @param string passwd. Plaintext password to authenticate.
	* @desc Attempts to authenticate the user and create a session if the user's status is active.
	* Updates the lastlogin and logincount values if successful.
	*/
	public function 
	UserLogin($dbh, $loginid, $passwd)
	{
		require_once("config/config-axtparts.php");
		$rv = array();
		
		$user = $this->UserRead($dbh, false, $loginid);

		if ($user["status"] === true)
		{
			// Authenticate the password
			if ($this->PasswordCheck($passwd, $user["user"][0]["passwd"]) === true)
			{
				if (($user["user"][0]["status"] == USERSTATUS_ACTIVE) && ($user["user"][0]["privilege"] & UPRIV_USERLOGIN))
				{
					// Only log in if the user is 'active' and allowed to login
					$updateuser["lastlogin"] = date("Y-m-d H:i:s");
					$lc = $user["user"][0]["logincount"];
					$updateuser["logincount"] = $lc + 1;

					$x = $this->UserSave($dbh, $user["user"][0]["uid"], $updateuser);
					if ($x["status"] === false)
						$this->AlertMeTo($x["error"]);
					
					// Create a session for the user
					$this->SessionMeLoad($dbh, $user["user"][0]["uid"]);
					$this->SessionStamp();
					
					// Record a log entry
					$logmsg = "Logged in.";
					$this->LogSave($dbh, LOGTYPE_LOGIN, $user["user"][0]["uid"], $logmsg);
					
					$rv["uid"] = $user["user"][0]["uid"];
					$rv["status"] = true;
					$rv["error"] = false;
				}
				else 
				{
					$rv["uid"] = false;
					$rv["status"] = false;
					$rv["error"] = "Login Denied.";
				}
			}
			else 
			{
				$rv["uid"] = false;
				$rv["status"] = false;
				$rv["error"] = "Login Denied.";
			}
		}
		else 
		{
			$rv["uid"] = false;
			$rv["status"] = false;
			$rv["error"] = "Login Denied.";
		}
		
		return $rv;
	}
	
	
	/**
	* @return void
	* @param object dbh database object
	* @desc Logs the user out, destroying the session data and recording a log entry.
	*/
	public function 
	UserLogout($dbh)
	{
		require_once("config/config-axtparts.php");
		
		$uid = $this->SessionMeUID();
		$logmsg = "Logged out.";
		$this->LogSave($dbh, LOGTYPE_LOGOUT, $uid, $logmsg);
		$this->SessionKill();
		
		$this->VectorMeTo(PAGE_LOGOUT);
	}
	
	
	/**
	* @return void
	* @param object dbh database object
	* @param string logtype. The type of log entry.
	* @param string uid. The uid creating the log entry.
	* @param string logmsg. The message to add to the log.
	* @desc Attempts to create a new log entry.
	*/
	public function 
	LogSave($dbh, $logtype, $uid, $logmsg)
	{
		require_once("config/config-axtparts.php");
		
		$q_log = "insert into log "
				. "\n set "
				. "\n logtype='".$dbh->real_escape_string($logtype)."', "
				. "\n logdate='".date("Y-m-d H:i:s")."', "
				. "\n uid='".$dbh->real_escape_string($uid)."', "
				. "\n logmsg='".$dbh->real_escape_string($logmsg)."' "
				;
		$s_log = $dbh->query($q_log);
	}
	
	
	/**
	* @return array [log][n][logid], [log][n][logtype], [log][n][logdate], [log][n][uid], [log][n][logmsg], [status]=true if success, false if error, [error]=error message
	* @param object dbh database object
	* @param string uid (optional). If specified all logs relating to the uid are returned (or filtered by logtype).
	* @param string logtype (optional). If specified all logs of specified logtype are returned (or filtered by uid).
	* @desc Reads entries from the log.
	*/
	public function 
	LogRead($dbh, $uid = false, $logtype = false)
	{
		require_once("config/config-axtparts.php");
		$rv = array();
		
		$q_log = "select * "
				. "\n from log ";
		if ($uid !== false)
		{
			$q_log .= "where uid='".$dbh->real_escape_string($uid)."' ";
			
			if ($logtype !== false)
				$q_log .= "and logtype='".$dbh->real_escape_string($logtype)."' ";
		}
		else 
			$q_log .= "where logtype='".$dbh->real_escape_string($logtype)."' ";
		$q_log .= "\n order by logdate";
		
		$s_log = $dbh->query($q_log);
		if (!$s_log)
		{
			$rv["log"] = false;
			$rv["status"] = false;
			$rv["error"] = $dbh->error;
		}
		else 
		{
			$n = 0;
			$rv["log"] = array();
			while ($r_log = $s_log->fetch_assoc())
				$rv["log"][$n++] = $r_log;
			$rv["status"] = true;
			$rv["error"] = false;
		}
		
		return $rv;
	}
	
	
	/**
	* @return void
	* @desc Erases the current session.
	*/
	public function 
	SessionKill()
	{
		require_once("config/config-axtparts.php");
		
		unset($_SESSION[SESSNAME]);
		return;
	}

	
	/**
	* @return int timestamp
	* @desc Returns the session timestamp (Unix timestamp).
	*/
	public function 
	SessionTimeRead()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]['timestamp']))
			return $_SESSION[SESSNAME]['timestamp'];
		else 
			return false;
	}
	
	/**
	* @return int timestamp
	* @desc Returns the session time remaining in seconds.
	*/
	public function 
	SessionTimeRemaining()
	{
		require_once("config/config-axtparts.php");
		
		$t = $this->SessionTimeRead();
		if ($t !== false)
		{
			$timedif = time() - $t;
			$timeleft = SESSION_TIMEOUT - $timedif;
			
			if ($timeleft < 0)
				$timeleft = 0;
				
			return $timeleft;
		}	
		else 
			return 0;
	}
	
	/**
	* @return bool true is session is OK, false if not.
	* @desc Checks the validity of the current session and returns true/false.
	*/	
	public function 
	SessionCheck()
	{
		require_once("config/config-axtparts.php");
		
		if (!isset($_SESSION[SESSNAME]))
			return false;
		else
		{
			if (SESSION_TIMEOUT == 0)
				return true;
			else 
			{
				$t = $this->SessionTimeRemaining();
				if ($t == 0)
				{
					$this->SessionKill();
					return false;
				}
				else 
				{
					$this->SessionStamp();
					return true;
				}
			}
		}
	}

	
	/**
	* @return void.
	* @desc Restamps the session timestamp with the current timestamp.
	*/
	public function 
	SessionStamp()
	{
		require_once("config/config-axtparts.php");
		
		$_SESSION[SESSNAME]['timestamp'] = time();
		return;
	}

	
	/**
	* @return void.
	* @param object dbh database object
	* @param string uid. The uid for the logged in user
	* @desc Loads the logged-in user data into the session.
	*/
	public function 
	SessionMeLoad($dbh, $uid)
	{
		require_once("config/config-axtparts.php");
		
		$user = $this->UserRead($dbh, $uid, false);
		
		if ($user["status"] !== false)
			$_SESSION[SESSNAME]["me"] = $user["user"][0];
		else 
			unset($_SESSION[SESSNAME]["me"]);
	}
	
	
	/**
	* @return array [me][uid], [me][loginid], [me][name], [me][lastlogin], [me][logincnt], [me][status], [me][privilege],  [status]=true if success, false if error, [error]=error message
	* @desc Returns the session data for the logged-in user.
	*/
	public function 
	SessionMeRead()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]))
		{
			$rv["me"] = $_SESSION[SESSNAME]["me"];
			$rv["status"] = true;
			$rv["error"] = false;
		}
		else
		{ 
			$rv["me"] = false;
			$rv["status"] = false;
			$rv["error"] = "Session not present";
		}
		
		return $rv;
	}
	
	
	/**
	* @return mixed uid of logged-in user or false on error
	* @desc Returns the user UID for the logged-in user.
	*/
	public function 
	SessionMeUID()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]["uid"]))
			return $_SESSION[SESSNAME]["me"]["uid"];
		else 
			return false;
	}
	
	
	/**
	* @return mixed name of logged-in user or false on error
	* @desc Returns the user name for the logged-in user.
	*/
	public function 
	SessionMeName()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]["username"]))
			return $_SESSION[SESSNAME]["me"]["username"];
		else 
			return false;
	}
	
	
	/**
	* @return mixed LoginID of logged-in user or false on error
	* @desc Returns the user LoginID for the logged-in user.
	*/
	public function 
	SessionMeLoginID()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]["loginid"]))
			return $_SESSION[SESSNAME]["me"]["loginid"];
		else 
			return false;
	}
	
	
	/**
	* @return boolean
	* @desc Returns a boolean indicating whether the user can admin other users.
	*/
	public function 
	SessionMeAdmin()
	{
		require_once("config/config-axtparts.php");
		
		if ($this->SessionMePrivilegeBit(UPRIV_USERADMIN))
			return true;
		else 
			return false;
	}
	
	
	/**
	* @return boolean
	* @desc Returns the user privilege setting
	*/
	public function 
	SessionMePrivilege()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]["privilege"]))
			return $_SESSION[SESSNAME]["me"]["privilege"];
		else 
			return false;
	}
	
	
	/**
	* @return boolean
	* @desc Returns a boolean indicating whether the user has the specified privilege bit set.
	*/
	public function 
	SessionMePrivilegeBit($priv)
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["me"]["privilege"]))
		{
			if ($_SESSION[SESSNAME]["me"]["privilege"] & $priv)
				return true;
			else 
				return false;
		}
		else 
			return false;
	}
	
		
	/**
	* @return void.
	* @param object dbh database object
	* @param string uid. The user ID to load
	* @desc Loads the selected user data into the session (admin use).
	*/
	public function 
	SessionUserLoad($dbh, $uid)
	{
		require_once("config/config-axtparts.php");
		
		$user = $this->UserRead($dbh, $uid, false);
		
		if ($user["status"] !== false)
			$_SESSION[SESSNAME]["user"] = $user["user"][0];
		else 
			unset($_SESSION[SESSNAME]["user"]);
	}
	
	
	/**
	* @return array [user][uid], [user][loginid], [user][name], [user][lastlogin], [user][logincnt], [user][status], [user][privilege], [status]=true if success, false if error, [error]=error message
	* @param object dbh database object
	* @desc Returns the session data for the selected user (admin use).
	*/
	public function 
	SessionUserRead()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["user"]))
		{
			$rv["user"] = $_SESSION[SESSNAME]["user"];
			$rv["status"] = true;
			$rv["error"] = false;
		}
		else
		{ 
			$rv["user"] = false;
			$rv["status"] = false;
			$rv["error"] = "Session not present";
		}
		
		return $rv;
	}
	
	
	/**
	* @return boolean
	* @desc Returns the privilege setting for the selected user.
	*/
	public function 
	SessionUserPrivilege()
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["user"]["privilege"]))
			return $_SESSION[SESSNAME]["user"]["privilege"];
		else 
			return false;
	}
	
	
	/**
	* @return boolean
	* @desc Returns a boolean indicating whether the selected user has the specified privilege.
	*/
	public function 
	SessionUserPrivilegeBit($priv)
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["user"]["privilege"]))
		{
			if ($_SESSION[SESSNAME]["user"]["privilege"] & $priv)
				return true;
			else 
				return false;
		}
		else 
			return false;
	}
	
	
	/**
	* @return void
	* @param string variable name
	* @param string variable value
	* @desc Saves the value to the named variable in session
	*/
	public function 
	SessionVarSave($var, $value)
	{
		require_once("config/config-axtparts.php");
		
		$_SESSION[SESSNAME]["vars"][$var] = $value;
	}
	
	
	/**
	* @return string or false
	* @param string variable name
	* @desc Returns the value from the named variable in session
	*/
	public function 
	SessionVarRead($var)
	{
		require_once("config/config-axtparts.php");
		
		if (isset($_SESSION[SESSNAME]["vars"][$var]))
			return $_SESSION[SESSNAME]["vars"][$var];
		else 
			return false;
	}
	
	
	/**
	* @return void
	* @param string variable name
	* @desc Clears the value from the named variable in session
	*/
	public function 
	SessionVarClear($var)
	{
		require_once("config/config-axtparts.php");
		
		unset($_SESSION[SESSNAME]["vars"][$var]);
	}
	
	
	/**
	* @return string. 32-digit hex string MAC.
	* @desc Creates a MAC using the session ID (32 digit hex string) and a hash of the data.
	*/
	public function 
	MACCreate($datastring)
	{
		// XOR the md5 hash of the session id with the md5 hash of the datastring.
		// this is not meant to be high security.
		$sid = md5(session_id());
		$arghash = md5($datastring);
		$result = "";
		for ($i = 0; $i < 32; $i+=2)
		{
			$x = substr($sid, $i, 2);
			$xd = hexdec($x);
			$y = substr($arghash, $i, 2);
			$yd = hexdec($y);
			$zd = $xd ^ $yd;
			$z = str_pad(dechex($zd), 2, "0", STR_PAD_LEFT);
			$result .= $z;
		}

		return $result;
	}
	
	
	/**
	* @return bool. true if MAC verifies, false if not.
	* @desc 
	*/
	public function 
	MACVerify($mac, $datastring)
	{
		$testmac = $this->MACCreate($datastring);
		if (strcasecmp($mac, $testmac) == 0)
			return true;
		else 
			return false;
	}

	
	/**
	* @return string. Random string of numbers of specified length.
	* @param int len. The length of the numeric string required.
	* @desc Returns a numeric random string of the specified length.
	**/
	public function 
	PasswdSalt($len)
	{
		for ($i = 0; $i < $len; $i++)
		{
			if ($i == 0)
				$rstr = chr(rand(48,57));
			else
				$rstr .= chr(rand(48,57));
		}
		return($rstr);
	}

	
	/**
	* @return string. Base-64 encoded seeded hash of the password.
	* @param string passwd. The plaintext password to create the seeded hash for.
	* @desc Returns a base-64 encoded seeded hash for the plaintext password specified.
	**/
	public function 
	Passwd_ssha1($passwd)
	{
		$salt = pack("H*", $this->PasswdSalt(16));
		$passwd = $passwd.$salt;
		$sshapasswd = pack("H*", sha1($passwd));
		$userpasswd = base64_encode($sshapasswd.$salt);

		return($userpasswd);
	}


	/**
	* @return boolean. True if the password matches, false if not.
	* @param string plaintext passwd. The plaintext password to compare.
	* @param string hash passwd. The base-64 encoded seeded hash password to compare.
	* @desc Returns true if the plain password matches the salted hashed (ssha) password, otherwise false.
	**/
	public function
	PasswordCheck($plainpw, $hashpw)
	{
		if ($plainpw == "")
			return false;
		if (strlen($hashpw) < 26)
			return false;
		$rawhash = base64_decode($hashpw);
		// everything beyond the first 20 bytes (160 bits) is salt
		$salt = substr($rawhash, 20);
		// and the first 20 bytes is the hash itself
		$oldhash = substr($rawhash, 0, 20);
		// use the salt to create a hash of the plaintext password
		$newhash = pack("H*", sha1($plainpw.$salt));
		if ($oldhash == $newhash)
			return true;
		else
			return false;
	}
	
	
	/**
	* @return: void
	* @param: string uri. The URI to vector to
	* @desc: Performs the javascript vectoring to the uri argument
	*/
	public function 
	VectorMeTo($uri)
	{
		print "<script type=\"text/javascript\">top.location.href='".$uri."'</script>\n";
	}
	
	
	/**
	* @return: void
	* @param: $uri. The URI to load into the popup.
	* @param: $uri. The name of the popup.
	* @param: $uri. The parameters to control the popup.
	* @desc: Performs the javascript popup requested.
	*/
	public function 
	PopMeUp($uri, $popname, $parameters)
	{
		print "<script type=\"text/javascript\">window.open('".htmlentities($uri)."','".htmlentities($popname)."','".htmlentities($parameters)."')</script>\n";
	}
	
	/**
	* @return: void
	* @desc: Closes the popup
	*/
	public function 
	PopMeClose()
	{
		print "<script type=\"text/javascript\">self.close()</script>\n";
	}

	
	/**
	* @return: void
	* @param: string msg. The message to provide in the alert
	* @desc: Performs the javascript alert using the msg argument
	*/
	public function 
	AlertMeTo($msg)
	{
		print "<script type=\"text/javascript\">alert('".htmlentities($msg)."')</script>\n";
	}
	
	
	/**
	* @return: void
	* @param: string msg. The message to provide in the alert
	* @desc: Performs the javascript alert using the msg argument
	*/
	public function 
	UpdateParent()
	{
		print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
	}

	
	/**
	* @return string SQL set clause (name='val',...)
	* @param object database connection object
	* @param array entry as [column]=value
	* @desc converts the entry array into an SQL set clause
	*/
	public function 
	EntryToSQL($dbh, $entry)
	{
		// Parse the entry into a name=value statement
		$s = "set ";
		$k = 0;
		foreach ($entry as $col => $val)
		{
			if ($k == 0)
				$s .= $col."='".$dbh->real_escape_string($val)."'";
			else 
				$s .= ",".$col."='".$dbh->real_escape_string($val)."'";
			$k++;
		}
		
		return $s;
	}
	
	
	/**
	* @return bool true if found, false if not
	* @param object database connection object
	* @param string table to check
	* @param string column used in check query
	* @param string value to search for in column in table
	* @desc Checks whether there is a column with value in the specified table.
	*/
	public function 
	SQLCheckRowExists($dbh, $table, $column, $value)
	{
		$q_sql = "select ".$column
				. "\n from ".$table
				. "\n where ".$column."='".$dbh->real_escape_string($value)."'"
				;
		$s_sql = $dbh->query($q_sql);
		if (!$s_sql)
			return false;
		$nr = $s_sql->num_rows;
		if ($nr > 0)
			return true;
		else 
			return false;
	}
	
	
	/**
	* @return void
	* @param array list. and array of element arrays list[n][0] = value, list[n][1] = display
	* @param string/array selected. The element(s) that will be rendered as selected (or false for none)
	* @param string default selected. The display value if there is no selected value,
	* @desc Renders a select option list from a list array.
	*/
	public function 
	RenderOptionList($list, $selected = false, $default = false)
	{
		$ne = count($list);
		if (is_array($selected))
			$ns = count($selected);
		$found = false;
		for ($i = 0; $i < $ne; $i++)
		{
			$value = $list[$i][0];
			$display = $list[$i][1];
			
			if (is_array($selected))
			{
				if ($ns > 0)
				{
					$foundentry = false;
					for ($j = 0; $j < $ns; $j++)
					{
						$sv = $selected[$j];
						if (strcasecmp($value, $sv) == 0)
						{
							print "<option selected value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
							$foundentry = true;
						}
					}
					if ($foundentry === false)
						print "<option value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
				}
				else 
					print "<option value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
			}
			else 
			{
				if ($selected !== false)
				{
					if (strcasecmp($value, $selected) == 0)
					{
						print "<option selected value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
						$found = true;
					}
					else
						print "<option value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
				}
				else 
					print "<option value=\"".htmlentities($value)."\">".htmlentities($display)."</option>\n";
			}
		}
		
		if ($found === false)
		{
			if ($default !== false)
				print "<option selected value=\"\">".htmlentities($default)."</option>\n";
		}
	}
	
	
	/**
	* @return string part number
	* @param string partid. ID value from database insertion as a 6 digit string (with leading 0's)
	* @param string prefix. The prefix to put in front of the part ID value
	* @desc Calculates the unique part number with prefix
	*/
	public function 
	CalcPartNumber($partid, $prefix)
	{
		$newpartid = $prefix.$partid;
		$chk=0;
		for ($i = 0; $i < strlen($newpartid); $i++)
			$chk += ord($newpartid[$i]) * ($i+1);
		$chk = ($chk % 26) + ord('A');
		$chk = chr($chk);

		$rv = $newpartid.$chk;
		
		return $rv;
	}
	
	
	/**
	* @return array containing baddate, deldate and value elements
	* @param elementname. The element to look for in the post array.
	* @desc processes the $_POST global looking for date matches for elementname
	* returns the indicators for baddate, deldate and the elementvalue if successful.
	*/
	public function 
	GetDateFromPost($elementname)
	{
		// we can have a good date, a bad date or a null date (to delete)
		$baddate = false;
		$deldate = false;
		$elementval = "";
		// hunt for the parts for this element
		// the year
		$partidx = "yy_".$elementname;
		if (isset($_POST[$partidx]) && $_POST[$partidx] != "")
		{
			if ((strlen(trim($_POST[$partidx])) == 4) && (is_numeric($_POST[$partidx])))
				$elementval = trim($_POST[$partidx]);
			else 
				$baddate = true;
		}
		else 
			$deldate = true;
			
		// the month
		$partidx = "mm_".$elementname;
		if ((isset($_POST[$partidx])) && $_POST[$partidx] != "")
		{
			if (is_numeric($_POST[$partidx]))
				$elementval .= str_pad($_POST[$partidx], 2, "0", STR_PAD_LEFT);
			else 
				$baddate = true;
		}
		else
			$deldate = true;
						
		// the day
		$partidx = "dd_".$elementname;
		if ((isset($_POST[$partidx])) && $_POST[$partidx] != "")
		{
			if (is_numeric($_POST[$partidx]))
				$elementval .= str_pad($_POST[$partidx], 2, "0", STR_PAD_LEFT);
			else 
				$baddate = true;
		}
		else 
			$deldate = true;
			
		if (($baddate === true) || ($deldate === true))
			$elementval = false;

		$rv["baddate"] = $baddate;
		$rv["deldate"] = $deldate;
		$rv["value"] = $elementval;
			
		return $rv;
	}
	
	public function
	ReturnCountOf($dbh, $table, $column, $keycol, $keyval)
	{
		$rv = 0;
		if ($dbh)
		{
			$q = "select ".$column
				. "\n from ".$table
				. "\n where ".$keycol."='".$dbh->real_escape_string($keyval)."' "
				;
			
			$s = $dbh->query($q);
			if ($s)
			{
				$rv = $s->num_rows;
				$s->free();
			}
		}
		return $rv;		
	}
	
	public function
	GetAddressRow($dbh, $cvid)
	{
		$rv = array();
		if ($dbh)
		{
			$q_cv = "select cvid, "
				. "\n cvname, "
				. "\n cvaddr1, "
				. "\n cvaddr2, "
				. "\n cvcity, "
				. "\n cvstate, "
				. "\n cvpcode, "
				. "\n cvcountry, "
				. "\n cvweb, "
				. "\n cvabn, "
				. "\n cvtel, "
				. "\n cvfax, "
				. "\n cvcomment, "
				. "\n cvtype "
				. "\n from custvend "
				. "\n where cvid='".$dbh->real_escape_string($cvid)."'"
				;
			
			$s_cv = $dbh->query($q_cv);
			if ($s_cv)
			{
				$rv = $s_cv->fetch_assoc();
				$s_cv->free();
			}
																																	
		}
		return $rv;
	}
	
	public function 
	GetContacts($dbh, $cvid)
	{
		$rv = array();
		if ($dbh)
		{
			$q_contacts = "select contid, "
				. "\n cvid, "
				. "\n contname, "
				. "\n contposn, "
				. "\n conttel, "
				. "\n contmob, "
				. "\n contemail, "
				. "\n contcomment "
				. "\n from contacts "
				. "\n where cvid='".$dbh->real_escape_string($cvid)."'"
				;
			
			$s_contacts = $dbh->query($q_contacts);
			$i = 0;
			if ($s_contacts)
			{
				while ($r_contact = $s_contacts->fetch_assoc())
					$rv[$i++] = $r_contact;
				$s_contacts->free();
			}
		}
		return $rv;
	}
	
	public function
	GetContact($dbh, $contid)
	{
		$rv = array();
		if ($dbh)
		{
			$q_contact = "select contid, "
				. "\n cvid, "
				. "\n contname, "
				. "\n contposn, "
				. "\n conttel, "
				. "\n contmob, "
				. "\n contemail, "
				. "\n contcomment "
				. "\n from contacts "
				. "\n where contid='".$dbh->real_escape_string($contid)."'"
				;

			$s_contact = $dbh->query($q_contact);
			$i = 0;
			if ($s_contact)
				$rv = $s_contact->fetch_assoc();
		}
		return $rv;
	}
	
	public function
	AddContactToCV($dbh, $cvid, $contactname)
	{
		$rv = array();
		if ($dbh)
		{
			$q_contact = "insert into contacts "
				. "\n set "
				. "\n contname='".$dbh->real_escape_string($contactname)."', "
				. "\n cvid='".$dbh->real_escape_string($cvid)."' "
				;
			
			$s_contact = $dbh->query($q_contact);
			if ($s_contact)
				$rv["rowid"] = $dbh->insert_id;
			else 
				$rv["error"] = $dbh->error;
		}
		else
			$rv["error"] = "No database connection.";
		return $rv;
	}
	
	public function
	AddNewCV($dbh, $cvname)
	{
		$rv = array();
		if ($dbh)
		{
			$q_addr = "insert into custvend "
				. "\n set "
				. "\n cvname='".$dbh->real_escape_string($cvname)."'"
				;
								
			$s_addr = $dbh->query($q_addr);
			
			if ($s_addr)
				$rv["rowid"] = $dbh->insert_id;
			else
				$rv["error"] = $dbh->error;
		}
		else
			$rv["error"] = "No database connection.";
		return $rv;
	}
	
	
	/**
	* @return array or variantid's for the part if it is an assembly with BOM variants, otherwise false.
	* @param resource $dbh. The databse connection.
	* @param int $partid. The part ID to check
	* @desc Checks to see if the specified partid is an assembly with at least one BOM variant. Returns a set of
	* variantid for the different variants for the part as an array, or false if none
	*/
	public function
	getVariantsForAssemblyPart($dbh, $partid)
	{
		$rv = false;
		
		if ($dbh)
		{
			// Is the part an assembly? 
			$q = "select assyid "
				. "\n from assemblies "
				. "\n where partid='".$dbh->real_escape_string($partid)."' "
				;
			$s = $dbh->query($q);
			if ($s)
			{
				$n = 0;
				while ($r = $s->fetch_assoc())
				{
					// Check if there are variants for this assembly. A BOM must exist for each variant.
					$qvar = "select distinct(bomvariants.variantid) as vid "
						. "\n from boms " 
						. "\n left join bomvariants on bomvariants.bomid=boms.bomid "
						. "\n where assyid='".$dbh->real_escape_string($r["assyid"])."' "
						;
					$svar = $dbh->query($qvar);
					if ($svar)
					{
						while ($rvar = $svar->fetch_assoc())
						{
							$rv[$n] = $rvar["vid"];
							$n++;
						}
						$svar->free();
					}
				}
				$s->free();
			}
		}
		
		return $rv;
	}


	public function
	getVariantDetails($dbh, $vid)
	{
		$rv = false;
		
		if ($dbh)
		{
			$q = "select * from variant where variantid='".$dbh->real_escape_string($vid)."' ";
			$s = $dbh->query($q);
			if ($s)
			{
				$rv = $s->fetch_assoc();
				$s->free();
			}
		}
		
		return $rv;
	}
	
	/**
	* @return void.
	 * @param array $params. Contains the various parameters required for the rendering of the tabs
	 * Includes: array["tabs"], ["tabon"] = label of tab to be rendered as 'on',
	 * @desc Outputs the tab section using the parameters given.
	 * Depends on the correct css and javascript files having been included.
	 */
	 public function
	 FormRender_Tabs($params)
	 {
		if (isset($params["tabs"]))
		{
			$tabs = $params["tabs"];
			$nt = count($tabs);
		}
		if (isset($params["tabon"]))
			$ontab = $params["tabon"];
		else
			$ontab = false;

		print "<div class=\"container container-tabs\">";
		if ($nt > 0)
		{	
			foreach ($tabs as $tablabel => $tabset)
			{
				if (isset($tabset["href"]))
					$tabhref = $tabset["href"];
				else
					$tabhref = "#";
				
				if (isset($tabset["tabpriv"]))
				{
					$tabpriv = $tabset["tabpriv"];
					if ($this->SessionMePrivilegeBit($tabpriv) === true)
					{
						if ($ontab !== false)
						{
							if (strcasecmp($ontab, $tablabel) == 0)
								print "<a class=\"link-button btn-tab-active\" role=\"button\" href=\"".$tabhref."\">".$tablabel."</a>";
							else
								print "<a class=\"link-button btn-tab\" role=\"button\" href=\"".$tabhref."\">".$tablabel."</a>";
						}
						else
							print "<a class=\"link-button btn-tab\" role=\"button\" href=\"".$tabhref."\">".$tablabel."</a>";
					}
				}
			}
		}
		print "</div>";
	}
	

	
}	
	
?>