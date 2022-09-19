<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locn.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $locid: The location entry to edit. Create a new location if this is not present.

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-locn.php";
$formname = "poplocn";
$formtitle= "Add/Edit Location Detail";

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

$locid = false;
if (isset($_GET['locid']))
	$locid = trim($_GET["locid"]);
if (!is_numeric($locid))
	$locid = false;
	
// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["locref"]))
			$locref = trim($_POST["locref"]);
		else 
			$locref = "";
			
		if (isset($_POST["locdescr"]))
			$locdescr = trim($_POST["locdescr"]);
		else 
			$locdescr = "";
			
		if ($locref == "")
			$myparts->AlertMeTo("Require a location reference.");
		else 
		{
			if ($locid === false)
			{
				// new location - insert the values
				$q_p = "insert into locn "
					. "\n set "
					. "\n locref='".$dbh->real_escape_string($locref)."', "
					. "\n locdescr='".$dbh->real_escape_string($locdescr)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Location created: ".$locdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update locn "
					. "\n set "
					. "\n locref='".$dbh->real_escape_string($locref)."', "
					. "\n locdescr='".$dbh->real_escape_string($locdescr)."' "
					. "\n where locid='".$dbh->real_escape_string($locid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Location updated: ".$locdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete location if still used by a stock item
		$nt = $myparts->ReturnCountOf($dbh, "stock", "stockid", "locid", $locid);
		if ($nt > 0)
			$myparts->AlertMeTo("Location still used by ".$nt." parts.");
		else 
		{
			$q_p = "delete from locn "
				. "\n where locid='".$dbh->real_escape_string($locid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Location deleted: ".$locid;
				$myparts->LogSave($dbh, LOGTYPE_PARTLOCNDELETE, $uid, $logmsg);
				$myparts->UpdateParent();
			}
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($locid !== false)
{
	$urlargs = "?locid=".$locid;

	$q_l = "select locid, "
		. "\n locref, "
		. "\n locdescr "
		. "\n from locn "
		. "\n where locid='".$dbh->real_escape_string($locid)."' "
		;
	$s_l = $dbh->query($q_l);

	if (!$s_l)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_l = $s_l->fetch_assoc();
		$locref = $r_l["locref"];
		$locdescr = $r_l["locdescr"];
		$s_l->free();
	}
}
else
{
	$urlargs = "";
	$locref = "";
	$locdescr = "";
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
    <span class="text-element text-poptitle"><?php print ($locid === false ? "Add New Location Detail" : "Edit Location Detail") ?></span>
    <form class="form-container form-pop-locn" name="form-locn" id="form-locn" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-locn" for="locref">Location Ref</label>
		<input value="<?php print htmlentities($locref) ?>" name="locref" type="text" class="input-formelement" form="form-locn" maxlength="50" title="Stock Location Reference">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-locn" for="locdescr">Location Description</label>
		<input value="<?php print htmlentities($locdescr) ?>" name="locdescr" type="text" class="input-formelement" form="form-locn" maxlength="250" title="Location description">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-locn" formaction="<?php print $url ?>" value="Save" name="btn_save" onclick="delClear()">Save</button>
<?php 
if ($locid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-locn" formaction="<?php print $url ?>" value="Delete" name="btn_delete" onclick="delSet()">Delete</button>
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