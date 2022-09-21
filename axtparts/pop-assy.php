<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-assy.php 209 2017-04-06 21:48:59Z gswan $

// Parameters passed: 
// none: create new part
// $assyid: ID of assembly to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-assy.php";
$formname = "popassy";
$formtitle= "Add/Edit Assembly";

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

$assyid = false;
if (isset($_GET['assyid']))
	$assyid = trim($_GET["assyid"]);
if (!is_numeric($assyid))
	$assyid = false;

// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) === true)
	{
		if (isset($_POST["sel-partid"]))
		{
			$partid = trim($_POST["sel-partid"]);
			if ($partid == "")
				$partid = false;
		}
		else 
			$partid = false;

		if ($partid === false)
			$myparts->AlertMeTo("A part must be specified.");
		else 
		{
			if (isset($_POST["assyname"]))
				$assyname = trim($_POST["assyname"]);
			else 
				$assyname = "";
			if (isset($_POST["assydescr"]))
				$assydescr = trim($_POST["assydescr"]);
			else 	
				$assydescr = "";
			if (isset($_POST["assyaw"]))
				$assyaw = trim($_POST["assyaw"]);
			else 	
				$assyaw = "";
			if (isset($_POST["assyrev"]))
				$assyrev = trim($_POST["assyrev"]);
			else 	
				$assyrev = "";
			
			if ($assyid === false)
			{
				// new assy - insert the values
				$q_p = "insert into assemblies "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assydescr='".$dbh->real_escape_string($assydescr)."', "
					. "\n assyrev='".$dbh->real_escape_string($assyrev)."', "
					. "\n assyaw='".$dbh->real_escape_string($assyaw)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly created: ".$assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
			else 
			{
				// existing assy - update the values
				$q_p = "update assemblies set "
					. "\n assydescr='".$dbh->real_escape_string($assydescr)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n assyrev='".$dbh->real_escape_string($assyrev)."', "
					. "\n assyaw='".$dbh->real_escape_string($assyaw)."' "
					. "\n where partid='".$dbh->real_escape_string($partid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly updated: ".$assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYCHANGE, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) === true)
	{
		// Cannot delete assy if still attached to boms or units
		$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "assyid", $assyid);
		if ($nb > 0)
			$myparts->AlertMeTo("Assembly still referenced in ".$nb." BOMs.");
		else 
		{
			$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "assyid", $assyid);
			if ($nu > 0)
				$myparts->AlertMeTo("Assembly is used in ".$nu." manufactured units.");
			else 
			{
				// Read the assyname for the log
				$q_p = "select assydescr "
					. "\n from assemblies "
					. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
					;
				$s_p = $dbh->query($q_p);
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
					$d_assydescr = $r_p["assydescr"];
					$s_p->free();
				}
				else 
					$d_assydescr = $assyid;
					
				// Unattached assembly can be deleted
				$q_p = "delete from assemblies "
					. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
					. "\n limit 1 "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$myparts->AlertMeTo("Assembly deleted.");
					$uid = $myparts->SessionMeUID();
					$logmsg = "Assembly deleted: ".$d_assydescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYDELETE, $uid, $logmsg);
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
	else 
		$myparts->AlertMeTo("Insufficient privileges.");
}

// Get the parts (only assemblies) for the lists
$q_parts = "select partid, "
		. "\n partcatid, "
		. "\n partdescr, "
		. "\n partnumber "
		. "\n from parts "
		. "\n where partcatid='1' or partcatid='2' "
		. "\n order by partdescr "
		;
		
$s_parts = $dbh->query($q_parts);
$list_parts = array();
$i = 0;
if ($s_parts)
{
	while ($r_parts = $s_parts->fetch_assoc())
	{
		$list_parts[$i][0] = $r_parts["partid"];
		$list_parts[$i][1] = $r_parts["partdescr"]." (".$r_parts["partnumber"].")";
		$i++;
	}
	$s_parts->free();
}

if ($assyid !== false)
{
	$urlargs = "?assyid=".$assyid;
	
	$q_p = "select * from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
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
		$partnum = $r_p["partnumber"];
		$assyname = $r_p["partdescr"];
		$assyid = $r_p["assyid"];
		$partid = $r_p["partid"];
		$assydescr = $r_p["assydescr"];
		$assyrev = str_pad($r_p["assyrev"], 2, "0", STR_PAD_LEFT);
		$assyaw = $r_p["assyaw"];
		$s_p->free();
	}
	
	// Get some statistical detail about the assembly
	$nu = $myparts->ReturnCountOf($dbh, "unit", "unitid", "assyid", $assyid);
	$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "assyid", $assyid);
	
	// Engineering and Manufacturing docs
	$q_ed = "select engdocpath, "
		. "\n engdocid, "
		. "\n engdocdescr "
		. "\n from engdocs "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
		;
	$s_ed = $dbh->query($q_ed);
	$ed_set = array();
	$ned = 0;
	if ($s_ed)
	{
		while ($r_ed = $s_ed->fetch_assoc())
			$ed_set[$ned++] = $r_ed;
	}
}
else 
{
	$urlargs="";
	$partnum = "";
	$assyname = "";
	$partid = 0;
	$assydescr = "";
	$assyrev = "";
	$assyaw = "";
	$ed_set = array();
	$ned = 0;
}		

$dbh->close();

if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) !== true)
	$readonly = "readonly";
else
	$readonly = "";

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
    <span class="text-element text-poptitle"><?php print ($assyid === false ? "Add New Assembly" : "Edit Assembly") ?></span>
    <form class="form-container form-pop-assy" name="form-assy" id="form-assy" action="<?php print $url ?>" method="post">
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="partnum">Part Number</label>
        <span class="text-element text-pop-dataitem"><?php print htmlentities($partnum) ?></span>
      </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="sel-partid">Part</label>
		<select name="sel-partid" class="select sel-formitem" form="form-assy" <?php print $readonly ?>>
          <?php $myparts->RenderOptionList($list_parts, $partid, false) ?>
        </select>
	  </div>
<?php
if ($assyid !== false)
{
?>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="assyname">Assembly Part Name</label>
		<input value="<?php print htmlentities($assyname) ?>" name="assyname" type="text" readonly class="input-formelement" form="form-assy" maxlength="100" title="Assembly Part Name">
	  </div>
<?php
}
?>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="assydescr">Assembly Description</label>
		<input value="<?php print htmlentities($assydescr) ?>" name="assydescr" type="text" class="input-formelement" form="form-assy" maxlength="100" title="Assembly description" <?php print $readonly ?>>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="assyrev">Assembly Rev</label>
		<input value="<?php print htmlentities($assyrev) ?>" name="assyrev" type="text" class="input-formelement" form="form-assy" maxlength="4" title="Assembly revision" <?php print $readonly ?>>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-assy" for="assyaw">Assembly AW</label>
		<input value="<?php print htmlentities($assyaw) ?>" name="assyaw" type="text" class="input-formelement" form="form-assy" maxlength="4" title="Assembly artwork" <?php print $readonly ?>>
	  </div>
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_ASSEMBLIES) === true)
{
?>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-assy" formaction="<?php print $url ?>" value="Save" name="btn_save">Save</button>
<?php
	if ($assyid !== false)
	{
?>
		<button type="submit" class="btn-pop-delete" form="form-assy" formaction="<?php print $url ?>" value="Delete" name="btn_delete">Delete</button>
<?php
	}
?>
	  </div>
<?php
}
?>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
if ($assyid !== false)
{
?>
    <span class="text-element text-poptitle">Engineering Documents</span>
    <div class="container container-pop-assyengdocs">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Document</span>
      </div>
<?php 
	for ($i = 0; $i < $ned; $i++)
	{
		if ($i%2 == 0)
			$stline = "evn";
		else
			$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-engdocdl.php?engdocid=<?php print $ed_set[$i]["engdocid"] ?>','pop_engdocdl',800,800)" title="Download document"><?php print htmlentities($ed_set[$i]["engdocdescr"]) ?></a>
      </div>
<?php
	}
?>
    </div>
    <div class="rule rule-popsection">
      <hr>
    </div>
    <div class="container container-pop-text">
      <span class="text-element text-pop-dataitem">BOM items referencing this assembly: <?php print $nb ?></span>
      <span class="text-element text-pop-dataitem">Units referencing this assembly: <?php print $nu ?></span>
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