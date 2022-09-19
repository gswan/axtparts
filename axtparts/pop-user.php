<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-user.php 191 2016-07-17 02:03:13Z gswan $

// Parameters passed: 
// none: create new user
// $uid: ID of user to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-user.php";
$formname = "popuser";
$formtitle= "Add/Edit User";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

if (!$myparts->SessionMePrivilegeBit(UPRIV_USERADMIN))
{
	$myparts->AlertMeTo("Insufficient privileges.");
	$myparts->PopMeClose();
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh->connect_error)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$uid = false;
if (isset($_GET['uid']))
	$uid = trim($_GET["uid"]);
if (!is_numeric($uid))
	$uid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["loginid"]))
			$loginid = trim($_POST["loginid"]);
		else 
			$loginid = "";
			
		if ($loginid == "")
			$myparts->AlertMeTo("Require a login ID.");
		else 
		{
			if (isset($_POST["username"]))
				$username = trim($_POST["username"]);
			else 
				$username = "";
				
			if (isset($_POST["sel-roleid"]))
				$roleid = trim($_POST["sel-roleid"]);
			else 
				$roleid = 0;
				
			if (isset($_POST["sel-status"]))
				$status = trim($_POST["sel-status"]);
			else 
				$status = "";
				
			if (isset($_POST["passwd"]))
				$passwd = trim($_POST["passwd"]);
			else 
				$passwd = "";
				
			if ($passwd != "")
				$hpasswd = $myparts->Passwd_ssha1($passwd);
			else 
				$hpasswd = false;
			
			if ($uid === false)
			{
				// new user - insert the values
				$q_p = "insert into user "
					. "\n set "
					. "\n loginid='".$dbh->real_escape_string($loginid)."', "
					. "\n username='".$dbh->real_escape_string($username)."', "
					. "\n roleid='".$dbh->real_escape_string($roleid)."', "
					. "\n status='".$dbh->real_escape_string($status)."'"
					;
				if ($hpasswd !== false)
					$q_p .= ", \n passwd='".$dbh->real_escape_string($hpasswd)."' ";
					
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$muid = $myparts->SessionMeUID();
					$logmsg = "User created: ".$loginid.": ".$username;
					$myparts->LogSave($dbh, LOGTYPE_USERNEW, $muid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update user "
					. "\n set "
					. "\n loginid='".$dbh->real_escape_string($loginid)."', "
					. "\n username='".$dbh->real_escape_string($username)."', "
					. "\n roleid='".$dbh->real_escape_string($roleid)."', "
					. "\n status='".$dbh->real_escape_string($status)."'"
					;
				if ($hpasswd !== false)
					$q_p .= ", \n passwd='".$dbh->real_escape_string($hpasswd)."' ";
					
				$q_p .= "\n where uid='".$dbh->real_escape_string($uid)."' ";
				
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$muid = $myparts->SessionMeUID();
					$logmsg = "User updated: ".$loginid.": ".$username;
					$myparts->LogSave($dbh, LOGTYPE_USERCHANGE, $muid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from user "
			. "\n where uid='".$dbh->real_escape_string($uid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$muid = $myparts->SessionMeUID();
			$logmsg = "User deleted: ".$uid;
			$myparts->LogSave($dbh, LOGTYPE_USERDELETE, $muid, $logmsg);
			$myparts->AlertMeTo("User deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($uid !== false)
{
	$urlargs = "?uid=".$uid;

	$q_p = "select * "
		. "\n from user "
		. "\n left join role on role.roleid=user.roleid "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
		;
										
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		if ($r_p = $s_p->fetch_assoc())
		{
			$loginid = $r_p["loginid"];
			$username = $r_p["username"];
			$lastlogin = $r_p["lastlogin"];
			$logincount = $r_p["logincount"];
			$status = $r_p["status"];
			$roleid = $r_p["roleid"];
			$s_p->free();
		}
		else
		{
			$myparts->AlertMeTo(htmlentities("Error: User record not found", ENT_COMPAT));
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}
else
{
	$loginid = "";
	$username = "";
	$lastlogin = "";
	$logincount = 0;
	$status = 0;
	$roleid = 0;
	$urlargs = "";
}

// Get a list of roles for the selector
$q_r = "select roleid, "
	. "\n rolename "
	. "\n from role "
	. "\n order by rolename "
	;
$s_r = $dbh->query($q_r);

$list_role = array();
$nrole = 0;
if ($s_r)
{
	while ($r_r = $s_r->fetch_assoc())
	{
		$list_role[$nrole][0] = $r_r["roleid"];
		$list_role[$nrole][1] = $r_r["rolename"];
		$nrole++;
	}
	$s_r->free();
}

$list_status = array();
$nstat = 0;
foreach ($_ustat_text as $k => $v)
	$list_status[$nstat++] = array ($k, $v);

$dbh->close();

$url = $formfile.$urlargs;

?>
<!DOCTYPE html>
<html lang="en-AU">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="generator" content="RSD 5.0.3519">
  <title>AXTParts</title>
  <link rel="stylesheet" href="css/vanillacss.min.css">
  <link rel="stylesheet" href="css/wireframe-theme.min.css">
  <link rel="icon" href="./images/icon-axtparts.png" type="image/png">
  <script>document.createElement( "picture" );</script>
  <script class="picturefill" async="async" src="js/picturefill.min.js"></script>
  <link rel="stylesheet" href="css/main.css">
</head>

<body>
  <div class="container container-body">
    <span class="text-element text-poptitle"><?php print ($uid === false ? "Add New User" : "Edit User") ?></span>
    <form class="form-container form-pop-user" name="form-user" id="form-user" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="loginid">Login ID</label>
		<input value="<?php print htmlentities($loginid) ?>" name="loginid" type="text" class="input-formelement" form="form-user" maxlength="100" title="User Login ID">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="passwd">Password</label>
		<input value="" name="passwd" type="password" class="passwd-formelement" title="Login password" form="form-user">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="username">User Name</label>
		<input value="<?php print htmlentities($username) ?>" name="username" type="text" class="input-formelement" form="form-user" maxlength="100" title="User Name">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="sel-roleid">Role</label>
		<select name="sel-roleid" class="select sel-formitem" form="form-user">
          <?php $myparts->RenderOptionList($list_role, $roleid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="sel-status">Status</label>
		<select name="sel-status" class="select sel-formitem" form="form-user">
          <?php $myparts->RenderOptionList($list_status, $status, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="lastlogin">Last Login</label>
		<input value="<?php print htmlentities($lastlogin) ?>" name="lastlogin" type="text" class="input-formelement" form="form-user" maxlength="40" title="Last login time" readonly>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-user" for="logincount">Login Count</label>
		<input value="<?php print htmlentities($logincount) ?>" name="logincount" type="text" class="input-formelement" form="form-user" maxlength="40" title="Login count" readonly>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-user" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" onclick="delClear()">Save</button>
<?php
if ($uid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-user" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
<?php
}
?>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>