<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-footprint.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new footprint
// $fprintid: ID of footprint to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-footprint.php";
$formname = "popfootprint";
$formtitle= "Add/Edit Part Footprint";

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

$fprintid = false;
if (isset($_GET['fprintid']))
	$fprintid = trim($_GET["fprintid"]);
if (!is_numeric($fprintid))
	$fprintid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_FOOTPRINTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["fprintdescr"]))
			$fprintdescr = trim($_POST["fprintdescr"]);
		else 
			$fprintdescr = "";
			
		if ($fprintdescr == "")
			$myparts->AlertMeTo("Require a footprint description.");
		else 
		{
			if ($fprintid === false)
			{
				// new footprint - insert the values
				$q_p = "insert into footprint "
					. "\n set "
					. "\n fprintdescr='".$dbh->real_escape_string($fprintdescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part footprint created: ".$fprintdescr;
					$myparts->LogSave($dbh, LOGTYPE_FPRINTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update footprint "
					. "\n set "
					. "\n fprintdescr='".$dbh->real_escape_string($fprintdescr)."' "
					. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part footprint updated: ".$fprintdescr;
					$myparts->LogSave($dbh, LOGTYPE_FPRINTCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_FOOTPRINTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete footprint if still used by parts
		$nt = $myparts->ReturnCountOf($dbh, "parts", "footprint", "footprint", $fprintid);
		if ($nt > 0)
			$myparts->AlertMeTo("Footprint still used by ".$nt." parts.");
		else 
		{
			$q_p = "delete from footprint "
				. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Part footprint deleted: ".$fprintid;
				$myparts->LogSave($dbh, LOGTYPE_FPRINTDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Part footprint deleted.");
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($fprintid !== false)
{
	$urlargs = "?fprintid=".$fprintid;

	$q_p = "select fprintid, "
		. "\n fprintdescr "
		. "\n from footprint "
		. "\n where fprintid='".$dbh->real_escape_string($fprintid)."' "
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
		$r_p = $s_p->fetch_assoc();
		$fprintdescr = $r_p["fprintdescr"];
		$s_p->free();
	}
}
else
{
	$urlargs="";
	$fprintdescr = "";
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
    <span class="text-element text-poptitle"><?php print ($fprintid === false ? "Add New Part Footprint" : "Edit Part Footprint") ?></span>
    <form class="form-container form-pop-footprint" name="form-fprint" id="form-fprint" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-fprint" for="fprintdescr">Part Footprint</label>
		<input value="<?php print htmlentities($fprintdescr) ?>" name="fprintdescr" type="text" class="input-formelement" form="form-fprint" maxlength="50" title="Part footprint">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-fprint" formaction="<?php print $url ?>" value="Save" name="btn_save" onclick="delClear()">Save</button>
<?php
if ($fprintid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-fprint" formaction="<?php print $url ?>" value="Delete" name="btn_delete" onclick="delSet()">Delete</button>
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