<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-variant.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new variant
// $variantid: ID of variant to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-variant.php";
$formname = "popvariant";
$formtitle= "Add/Edit Variant";

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

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim($_GET["variantid"]);
if (!is_numeric($variantid))
	$variantid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["variantname"]))
			$variantname = trim($_POST["variantname"]);
		else 
			$variantname = "";
		if (isset($_POST["variantdescr"]))
			$variantdescr = trim($_POST["variantdescr"]);
		else 
			$variantdescr = "";
		if (isset($_POST["variantstate"]))
			$variantstate = trim($_POST["variantstate"]);
		else 
			$variantstate = "";
		
		if ($variantname == "")
			$myparts->AlertMeTo("Require a variant name.");
		else 
		{
			if ($variantid === false)
			{
				// new variant - insert the values
				$q_p = "insert into variant "
					. "\n set "
					. "\n variantname='".$dbh->real_escape_string($variantname)."', "
					. "\n variantdescr='".$dbh->real_escape_string($variantdescr)."', "
					. "\n variantstate='".$dbh->real_escape_string($variantstate)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant created: ".$variantname.": ".$variantdescr;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update variant "
					. "\n set "
					. "\n variantname='".$dbh->real_escape_string($variantname)."', "
					. "\n variantdescr='".$dbh->real_escape_string($variantdescr)."', "
					. "\n variantstate='".$dbh->real_escape_string($variantstate)."' "
					. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant updated: ".$variantname.": ".$variantdescr;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTCHANGE, $uid, $logmsg);
				}
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete variant that is still in use by a BOM
		$nb = $myparts->ReturnCountOf($dbh, "bomvariants", "bomvid", "variantid", $variantid);
		if ($nb > 0)
			$myparts->AlertMeTo("Variant still used by ".$nb." BOMs.");
		else 
		{
			// Cannot delete a variant that is still in use by manufactured units
			$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "variantid", $variantid);
			if ($nu > 0)
				$myparts->AlertMeTo("Variant still used by ".$nu." manufactured units.");
			else 
			{
				$q_p = "delete from variant "
					. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
					. "\n limit 1 "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Variant deleted: ".$variantid;
					$myparts->LogSave($dbh, LOGTYPE_VARIANTDELETE, $uid, $logmsg);
					$myparts->AlertMeTo("Variant deleted.");
				}
				
				$myparts->UpdateParent();
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
}

$nb = 0;
$nu = 0;
if ($variantid !== false)
{
	$urlargs = "?variantid=".$variantid;

	$q_p = "select * "
		. "\n from variant "
		. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
		;
								
	$s_p = $dbh->query($q_p);
	if (!$s_p)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_p = $s_p->fetch_assoc();
		$variantname = $r_p["variantname"];
		$variantdescr = $r_p["variantdescr"];
		$variantstate = $r_p["variantstate"];
		$s_p->free();
	}

	// Get some stats about its usage
	$nb = $myparts->ReturnCountOf($dbh, "bomvariants", "bomvid", "variantid", $variantid);
	$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "variantid", $variantid);
}
else
{
	$urlargs="";
	$variantname = "";
	$variantdescr = "";
	$variantstate = "";
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
    <span class="text-element text-poptitle"><?php print ($variantid === false ? "Add New Assembly Variant" : "Edit Assembly Variant") ?></span>
    <form class="form-container form-pop-locn" name="form-variant" id="form-variant" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-variant" for="variantname">Variant Name</label>
		<input value="<?php print htmlentities($variantname) ?>" name="variantname" type="text" class="input-formelement" form="form-variant" maxlength="40" title="Variant Name">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-variant" for="variantdescr">Variant Description</label>
		<input value="<?php print htmlentities($variantdescr) ?>" name="variantdescr" type="text" class="input-formelement" form="form-variant" maxlength="100" title="Variant description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-variant" for="variantstate">Variant Status</label>
		<input value="<?php print htmlentities($variantstate) ?>" name="variantstate" type="text" class="input-formelement" form="form-variant" maxlength="40" title="Variant status">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-variant" formaction="<?php print $url ?>" value="Save" name="btn_save" onclick="delClear()">Save</button>
<?php
if ($variantid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-variant" formaction="<?php print $url ?>" value="Delete" name="btn_delete" onclick="delSet()">Delete</button>
<?php
}
?>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
if ($variantid !== false)
{
?>
    <div class="container container-pop-text">
      <span class="text-element text-pop-dataitem">BOM items referencing this variant: <?php print $nb ?></span>
      <span class="text-element text-pop-dataitem">Units referencing this variant: <?php print $nu ?></span>
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