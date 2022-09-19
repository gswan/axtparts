<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-compstatus.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new compstate
// $compstateid: ID of compstate to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-compstatus.php";
$formname = "popcompstatus";
$formtitle= "Add/Edit Component Status";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
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

$compstateid = false;
if (isset($_GET['compstateid']))
	$compstateid = trim($_GET["compstateid"]);
if (!is_numeric($compstateid))
	$compstateid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPSTATES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	{
		if (isset($_POST["statedescr"]))
			$statedescr = trim($_POST["statedescr"]);
		else 
			$statedescr = "";
			
		if ($statedescr == "")
			$myparts->AlertMeTo("Require a state description.");
		else 
		{
			if ($compstateid === false)
			{
				// new state - insert the values
				$q_p = "insert into compstates "
					. "\n set "
					. "\n statedescr='".$dbh->real_escape_string($statedescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component state created: ".$statedescr;
					$myparts->LogSave($dbh, LOGTYPE_CSTATENEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update compstates "
					. "\n set "
					. "\n statedescr='".$dbh->real_escape_string($statedescr)."' "
					. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component state updated: ".$statedescr;
					$myparts->LogSave($dbh, LOGTYPE_CSTATECHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPSTATES) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete state if still used by components
		$nt = $myparts->ReturnCountOf($dbh, "components", "compid", "compstateid", $compstateid);
		if ($nt > 0)
			$myparts->AlertMeTo("State still used by ".$nt." components.");
		else 
		{
			$q_p = "delete from compstates "
				. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Component state deleted: ".$compstateid;
				$myparts->LogSave($dbh, LOGTYPE_CSTATEDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Component state deleted.");
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($compstateid !== false)
{
	$urlargs = "?compstateid=".$compstateid;
	
	$q_p = "select compstateid, "
		. "\n statedescr "
		. "\n from compstates "
		. "\n where compstateid='".$dbh->real_escape_string($compstateid)."' "
		;
			
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_p = $s_p->fetch_assoc();
		$statedescr = $r_p["statedescr"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$statedescr = "";
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
    <span class="text-element text-poptitle"><?php print ($compstateid === false ? "Add New Component State" : "Edit Component State") ?></span>
    <form class="form-container form-pop-compstatus" name="form-compstatus" id="form-compstatus" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-compstatus" for="statedesc">Component Status</label>
		<input value="<?php print htmlentities($statedescr) ?>" name="statedesc" type="text" class="input-formelement" form="form-compstatus" maxlength="50" title="Component status">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-compstatus" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" onclick="delClear()">Save</button>
<?php
if ($compstateid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-compstatus" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
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