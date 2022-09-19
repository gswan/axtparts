<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-category.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new category
// $catid: ID of category to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-category.php";
$formname = "popcategory";
$formtitle= "Add/Edit Category";

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

$catid = false;
if (isset($_GET['catid']))
	$catid = trim($_GET["catid"]);
if (!is_numeric($catid))
	$catid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTCATS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["catdescr"]))
			$catdescr = trim($_POST["catdescr"]);
		else 
			$catdescr = "";
		if (isset($_POST["datadir"]))
			$datadir = trim($_POST["datadir"]);
		else 
			$datadir = "";
		
		if ($catdescr == "")
			$myparts->AlertMeTo("Require a category description.");
		else 
		{
			if ($catid === false)
			{
				// new category - insert the values
				$q_p = "insert into pgroups "
					. "\n set "
					. "\n catdescr='".$dbh->real_escape_string($catdescr)."', "
					. "\n datadir='".$dbh->real_escape_string($datadir)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Category created: ".$catdescr;
					$myparts->LogSave($dbh, LOGTYPE_PGRPNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update pgroups "
					. "\n set "
					. "\n catdescr='".$dbh->real_escape_string($catdescr)."', "
					. "\n datadir='".$dbh->real_escape_string($datadir)."' "
					. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Category updated: ".$catdescr;
					$myparts->LogSave($dbh, LOGTYPE_PGRPCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTCATS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete category is still used by a datasheet
		$nd = $myparts->ReturnCountOf($dbh, "datasheets", "dataid", "partcatid", $catid);
		if ($nd > 0)
			$myparts->AlertMeTo("Category still used by ".$nd." datasheets.");
		else 
		{
			$q_p = "delete from pgroups "
				. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				// Remove the references from parts to this category
				$q_p = "update parts "
					. "\n set "
					. "\n partcatid='0' "
					. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
					;
				$s_p = $dbh->query($q_p);
				
				$uid = $myparts->SessionMeUID();
				$logmsg = "Category deleted: ".$catid;
				$myparts->LogSave($dbh, LOGTYPE_PGRPDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("Part category deleted.");
			}
			$dbh->close();
			$myparts->UpdateParent();
			$myparts->PopMeClose();
			die();
		}
	}
}

if ($catid !== false)
{
	$urlargs = "?catid=".$catid;
	
	$q_p = "select partcatid, "
		. "\n catdescr, "
		. "\n datadir "
		. "\n from pgroups "
		. "\n where partcatid='".$dbh->real_escape_string($catid)."' "
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
		$catdescr = $r_p["catdescr"];
		$datadir = $r_p["datadir"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$catdescr = "";
	$datadir = "";
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
    <span class="text-element text-poptitle"><?php print ($catid === false ? "Add New Part Category" : "Edit Part Category") ?></span>
    <form class="form-container form-pop-category" name="form-category" id="form-category" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-category" for="catdescr">Part Category</label>
		<input value="<?php print htmlentities($catdescr) ?>" name="catdescr" type="text" class="input-formelement" form="form-category" maxlength="100" title="Part category">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-category" for="datadir">Datasheet Directory</label>
		<input value="<?php print htmlentities($datadir) ?>" name="datadir" type="text" class="input-formelement" form="form-category" maxlength="100" title="Datasheet directory">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-category" formaction="<?php print $url ?>" value="Update" name="btn_save" onclick="delClear()">Save</button>
	    <button type="submit" class="btn-pop-delete" form="form-category" formaction="<?php print $url ?>" value="Delete" name="btn_delete"  onclick="delSet()">Delete</button>
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