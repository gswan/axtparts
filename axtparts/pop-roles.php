<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-roles.php 188 2016-07-16 23:16:57Z gswan $

// Parameters passed: 
// none: create new role
// $rid: roleid to add/edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-roles.php";
$formname = "poproles";
$formtitle= "Add/Edit Roles";

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

$rid = false;
if (isset($_GET['rid']))
	$rid = trim($_GET["rid"]);
if (!is_numeric($rid))
	$rid = false;
	
// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERROLES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["rolename"]))
			$rolename = trim($_POST["rolename"]);
		else
			$rolename = "";
			
		if ($rolename == "")
			$myparts->AlertMeTo("Require a role name.");
		else
		{
			if (isset($_POST["rb"]))
				$rolebits = $_POST["rb"];
			else
				$rolebits = array();
			
			$rolemask = 0;
			if (count($rolebits) > 0)
			{
				foreach ($rolebits as $mb)
					$rolemask |= $mb;
			}
				
			if ($rid === false)
			{
				// new role - insert the values
				$q_p = "insert into role "
					. "\n set "
					. "\n rolename='".$dbh->real_escape_string($rolename)."', "
					. "\n privilege='".$dbh->real_escape_string($rolemask)."' "
					;
																	
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Role created: ".$rolename;
					$myparts->LogSave($dbh, LOGTYPE_ROLENEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else
			{
				// existing - update the values
				$q_p = "update role "
						. "\n set "
						. "\n rolename='".$dbh->real_escape_string($rolename)."', "
						. "\n privilege='".$dbh->real_escape_string($rolemask)."' "
						. "\n where roleid='".$dbh->real_escape_string($rid)."' "
						;

				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Role updated: ".$rolename;
					$myparts->LogSave($dbh, LOGTYPE_ROLECHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_USERROLES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from role "
			. "\n where roleid='".$dbh->real_escape_string($rid)."' "
			. "\n limit 1 "
			;

		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Role deleted: ".$rid;
			$myparts->LogSave($dbh, LOGTYPE_ROLEDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Role deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($rid !== false)
{
	$urlargs = "?rid=".$rid;

	$q_p = "select * "
		. "\n from role "
		. "\n where roleid='".$dbh->real_escape_string($rid)."' "
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
			$rolename = $r_p["rolename"];
			$rolepriv = $r_p["privilege"];
			$s_p->free();
		}
		else
		{
			$myparts->AlertMeTo(htmlentities("Error: Role record not found", ENT_COMPAT));
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}
else
{
	$urlargs = "";
	$rolename = "";
	$rolepriv = 0;
}

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
    <span class="text-element text-poptitle"><?php print ($rid === false ? "Add New User Role" : "Edit User Role") ?></span>
    <form class="form-container form-pop-role" name="form-role" id="form-role" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-role" for="rolename">Role Name</label>
		<input value="<?php print htmlentities($rolename) ?>" name="rolename" type="text" class="input-formelement" form="form-role" maxlength="100" title="Role Name">
	  </div>
      <div class="container container-pop-el">
        <div class="container container-pop-role-chk">
<?php
// print the checkbox rows
for ($i = 0; $i < 16; $i++)
{
	$lhi = $i;
	$lhv = 1 << $lhi;
	$rhi = $i + 16;
	$rhv = 1 << $rhi;
	
	if ($rolepriv & $lhv)
		$lhchk = "checked";
	else
		$lhchk = "";
	
	if ($rolepriv & $rhv)
		$rhchk = "checked";
	else
		$rhchk = "";
		
	if (isset($_upriv_text[(1 << $lhi)]))
		$lhlabel = $_upriv_text[(1 << $lhi)];
	else
		$lhlabel = "unassigned";
		
	if (isset($_upriv_text[(1 << $rhi)]))
		$rhlabel = $_upriv_text[(1 << $rhi)];
	else
		$rhlabel = "unassigned";
?>
		<label class="checkbox chk-role" form="form-role"><input type="checkbox" name="rb[]" value="<?php print $lhv ?>" form="form-role" <?php print $lhchk ?>><span><?php print htmlentities($lhlabel) ?></span></label>
		<label class="checkbox chk-role" form="form-role"><input type="checkbox" name="rb[]" value="<?php print $rhv ?>" form="form-role" <?php print $rhchk ?>><span><?php print htmlentities($rhlabel) ?></span></label>
<?php
}
?>
	  </div>
      </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-role" formaction="<?php print $url ?>" value="Save" name="btn_save" onclick="delClear()">Save</button>
<?php 
if ($rid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-role" formaction="<?php print $url ?>" value="Delete" name="btn_delete" onclick="delSet()">Delete</button>
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