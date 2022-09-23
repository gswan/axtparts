<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-swbuild.php 216 2021-01-28 23:59:51Z gswan $

// Parameters passed: 
// none: create new swbuild
// $swbuildid: ID of sw build to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-swbuild.php";
$formname = "popswbuild";
$formtitle= "Add/Edit Software Build";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh->connect_errno)
{
	$myparts->AlertMeTo("Could not connect to database: ".$dbh->connect_erno);
	$myparts->VectorMeTo($returnformfile);
	die();
}

$swbuildid = false;
if (isset($_GET['swbuildid']))
	$swbuildid = trim($_GET["swbuildid"]);
if (!is_numeric($swbuildid))
	$swbuildid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_SWBUILD) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["swname"]))
			$swname = trim($_POST["swname"]);
		else 
			$swname = "";
		if (isset($_POST["buildhost"]))
			$buildhost = trim($_POST["buildhost"]);
		else 
			$buildhost = "";
		if (isset($_POST["buildimage"]))
			$buildimage = trim($_POST["buildimage"]);
		else 
			$buildimage = "";
		if (isset($_POST["releaserev"]))
			$releaserev = trim($_POST["releaserev"]);
		else 
			$releaserev = "";
		$rv = $myparts->GetDateFromPost("releasedate");
		if ($rv["value"] !== false)
			$releasedate = $rv["value"];
		else 
			$releasedate = "";
		
		if ($swname == "")
			$myparts->AlertMeTo("Require a software name.");
		else 
		{
			if ($swbuildid === false)
			{
				// new swbuild - insert the values
				$q_p = "insert into swbuild "
					. "\n set "
					. "\n swname='".$dbh->real_escape_string($swname)."', "
					. "\n buildhost='".$dbh->real_escape_string($buildhost)."', "
					. "\n buildimage='".$dbh->real_escape_string($buildimage)."', "
					. "\n releaserev='".$dbh->real_escape_string($releaserev)."', "
					. "\n releasedate='".$dbh->real_escape_string($releasedate)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Software build created: ".$swname.": ".$buildimage;
					$myparts->LogSave($dbh, LOGTYPE_SWBLDNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update swbuild "
					. "\n set "
					. "\n swname='".$dbh->real_escape_string($swname)."', "
					. "\n buildhost='".$dbh->real_escape_string($buildhost)."', "
					. "\n buildimage='".$dbh->real_escape_string($buildimage)."', "
					. "\n releaserev='".$dbh->real_escape_string($releaserev)."', "
					. "\n releasedate='".$dbh->real_escape_string($releasedate)."' "
					. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Software build updated: ".$swname.": ".$buildimage;
					$myparts->LogSave($dbh, LOGTYPE_SWBLDCHANGE, $uid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_SWBUILD) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete swbuild that is still in use by a Unit via a swlicence
		$ns = $myparts->ReturnCountOf($dbh, "swlicence", "swlid", "swbuildid", $swbuildid);
		if ($ns > 0)
			$myparts->AlertMeTo("Software still used by ".$ns." licenses.");
		else 
		{
			$q_p = "delete from swbuild "
				. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
				. "\n limit 1 "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Software build deleted: ".$swbuildid;
				$myparts->LogSave($dbh, LOGTYPE_SWBLDDELETE, $uid, $logmsg);
				$myparts->AlertMeTo("SW build deleted.");
			}
			$myparts->UpdateParent();
			$dbh->close();
			$myparts->PopMeClose();
			die();
		}
	}
}

$nb = 0;
if ($swbuildid !== false)
{
	$urlargs = "?swbuildid=".$swbuildid;

	$q_p = "select swbuildid, "
		. "\n swname, "
		. "\n buildhost, "
		. "\n buildimage, "
		. "\n releaserev, "
		. "\n releasedate "
		. "\n from swbuild "
		. "\n where swbuildid='".$dbh->real_escape_string($swbuildid)."' "
		;
																		
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_p = $s_p->fetch_assoc();
		$swname = $r_p["swname"];
		$buildhost = $r_p["buildhost"];
		$buildimage = $r_p["buildimage"];
		$releaserev = $r_p["releaserev"];
		$releasedate = $r_p["releasedate"];
		if (($releasedate != "") && ($releasedate != "0000-00-00"))
		{
			$releasedate_yy = substr($r_p["releasedate"], 0, 4);
			$releasedate_mm = substr($r_p["releasedate"], 5, 2);
			$releasedate_dd = substr($r_p["releasedate"], 8, 2);
		}
		else
		{
			$releasedate_dd = "";
			$releasedate_mm = "";
			$releasedate_yy = "";
		}
		$s_p->free();
	}

	// Get some stats about its usage
	$nb = $myparts->ReturnCountOf($dbh, "swlicence", "swlid", "swbuildid", $swbuildid);
}
else
{
	$urlargs="";
	$swname = "";
	$buildhost = "";
	$buildimage = "";
	$releaserev = "";
	$releasedate = "";
	$releasedate_dd = "";
	$releasedate_mm = "";
	$releasedate_yy = "";
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
    <span class="text-element text-poptitle"><?php print ($swbuildid === false ? "Add New SW Build" : "Edit SW Build") ?></span>
    <form class="form-container form-pop-swbuild" name="form-swbuild" id="form-swbuild" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-swbuild" for="swname">Software Name</label>
		<input value="<?php print htmlentities($swname) ?>" name="swname" type="text" class="input-formelement" form="form-swbuild" maxlength="100" title="Software name">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-swbuild" for="buildhost">Build Host</label>
		<input value="<?php print htmlentities($buildhost) ?>" name="buildhost" type="text" class="input-formelement" form="form-swbuild" maxlength="25" title="Software build host">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-swbuild" for="buildimage">Build Image Name</label>
		<input value="<?php print htmlentities($buildimage) ?>" name="buildimage" type="text" class="input-formelement" form="form-swbuild" maxlength="100" title="Software build image name">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-swbuild" for="releaserev">SW Release Rev</label>
		<input value="<?php print htmlentities($releaserev) ?>" name="releaserev" type="text" class="input-formelement" form="form-swbuild" maxlength="20" title="Software release revision">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-swbuild" for="releasedate">SW Release Date (YYYY-MM-DD)</label>
	    <div class="container container-pop-dateparts">
		  <input value="<?php print htmlentities($releasedate_yy) ?>" name="yy_releasedate" type="text" class="input-form-dateelement" form="form-swbuild" maxlength="4" title="Software release date YYYY">
		  <span class="text-element text-pop-date-sep">-</span>
		  <input value="<?php print htmlentities($releasedate_mm) ?>" name="mm_releasedate" type="text" class="input-form-dateelement" form="form-swbuild" maxlength="2" title="Software release date MM">
		  <span class="text-element text-pop-date-sep">-</span>
		  <input value="<?php print htmlentities($releasedate_dd) ?>" name="dd_releasedate" type="text" class="input-form-dateelement" form="form-swbuild" maxlength="2" title="Software release date DD">
		</div>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-swbuild" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" onclick="delClear()">Save</button>
<?php
if ($swbuildid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-swbuild" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
<?php
}
?>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
if ($swbuildid !== false)
{
?>
    <div class="container container-pop-text">
      <span class="text-element text-pop-dataitem">Assemblies referencing this software: <?php print $nb ?></span>
    </div>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
}
?>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>