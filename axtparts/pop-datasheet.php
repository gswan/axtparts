<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-datasheet.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new datasheet
// $dataid: ID of datasheet to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-datasheet.php";
$formname = "popdatasheet";
$formtitle= "Add/Edit Datasheet Detail";

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

$partcatid = false;
$dataid = false;
if (isset($_GET['dataid']))
	$dataid = trim($_GET["dataid"]);
if (!is_numeric($dataid))
	$dataid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_DATASHEEETS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["datadescr"]))
			$datadescr = trim($_POST["datadescr"]);
		else 
			$datadescr = "";
		
		if ($datadescr == "")
			$myparts->AlertMeTo("Require a datasheet description.");
			
		if (isset($_POST["sel-partcatid"]))
			$partcatid = trim($_POST["sel-partcatid"]);
		else 
			$partcatid = "";
	
		if ($partcatid == "")
			$myparts->AlertMeTo("Require a part category selection.");
		else 
		{
			if ($dataid === false)
			{
				// Upload of datasheet - place it in the directory for the selected part category
				if (isset($_FILES["dsheetfile"]))
				{
					if ($_FILES["dsheetfile"]["error"] == UPLOAD_ERR_OK)
					{
						// Find the datadir for the datasheet, as specified by the part category
						$q_p = "select partcatid, "
							. "\n datadir "
							. "\n from pgroups "
							. "\n where partcatid='".$dbh->real_escape_string($partcatid)."' "
							;
						$s_p =$dbh->query($q_p);
						if (!$s_p)
							$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
						else 
						{
							$r_p = $s_p->fetch_assoc();
							$dsd = $r_p["datadir"];
							$datadir = DATASHEETS_DIR.$dsd;
							if (substr($datadir, -1) != "/")
								$datadir .= "/";
							if (!file_exists("../".$datadir))
							{
								$r = mkdir("../".$datadir, 0755, true);
								if ($r === false)
									$myparts->AlertMeTo("Error: Could not create directory for datasheet(../".htmlentities($datadir).").");
							}
							else 
								$r = true;
								
							if ($r === true)
							{
								$ftype = $_FILES["dsheetfile"]["type"];
								$fname = $_FILES["dsheetfile"]["tmp_name"];
								$frealname = $_FILES["dsheetfile"]["name"];
								$frealpath = $datadir.$frealname;
								$r = move_uploaded_file($fname, "../".$frealpath);
								if ($r !== true)
									$myparts->AlertMeTo("Error: Could not move uploaded file to ../".htmlentities($frealpath).".");
								else 
								{
									$q_d = "insert into datasheets "
										. "\n set "
										. "\n datasheetpath='".$dbh->real_escape_string($frealname)."', "
										. "\n datadescr='".$dbh->real_escape_string($datadescr)."', "
										. "\n partcatid='".$dbh->real_escape_string($partcatid)."' "
										;
									$s_d = $dbh->query($q_d);
									if (!$s_d)
										$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
									else 
									{
										$uid = $myparts->SessionMeUID();
										$logmsg = "Datasheet added: ".$datadescr;
										$myparts->LogSave($dbh, LOGTYPE_DSHEETNEW, $uid, $logmsg);
										$myparts->AlertMeTo("Datasheet saved to ".htmlentities($frealpath).".");
										$myparts->UpdateParent();
									}
								}
							}
						}
					}
				}
			}
			else 
			{
				// existing - update the values
				$q_p = "update datasheets "
					. "\n set "
					. "\n datadescr='".$dbh->real_escape_string($datadescr)."', "
					. "\n partcatid='".$dbh->real_escape_string($partcatid)."' "
					. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Datasheet metadata updated: ".$datadescr;
					$myparts->LogSave($dbh, LOGTYPE_ASSYNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_DATASHEEETS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from datasheets "
			. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			// Remove the references from components to this datasheet
			$q_p = "update components "
				. "\n set "
				. "\n dataid='0' "
				. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
				;
			$s_p = $dbh->query($q_p);
			
			$uid = $myparts->SessionMeUID();
			$logmsg = "Datasheet removed: ".$dataid;
			$myparts->LogSave($dbh, LOGTYPE_DSHEETDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Datasheet deleted.");
		}
		$myparts->UpdateParent();
		$myparts->PopMeClose();
		$dbh->close();
		die();
	}
}

if ($dataid !== false)
{
	$urlargs = "?dataid=".$dataid;
	
	$q_p = "select dataid, "
		. "\n datadescr, "
		. "\n partcatid "
		. "\n from datasheets "
		. "\n where dataid='".$dbh->real_escape_string($dataid)."' "
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
		$datadescr = $r_p["datadescr"];
		$partcatid = $r_p["partcatid"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$datadescr = "";
	$partcatid = 0;
}

$q_partcat = "select partcatid, "
		. "\n catdescr "
		. "\n from pgroups "
		. "\n order by catdescr "
		;
		
$s_partcat = $dbh->query($q_partcat);
$list_partcat = array();
$i = 0;
if ($s_partcat)
{
	while ($r_partcat = $s_partcat->fetch_assoc())
	{
		$list_partcat[$i][0] = $r_partcat["partcatid"];
		$list_partcat[$i][1] = $r_partcat["catdescr"];
		$i++;
	}
	$s_partcat->free();
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
    <span class="text-element text-poptitle"><?php print ($dataid === false ? "Add New Datasheet" : "Edit Datasheet Detail") ?></span>
    <form class="form-container form-pop-datasheet" name="form-datasheet" id="form-datasheet" action="<?php print $url ?>" method="post" enctype="multipart/form-data" onsubmit="return deleteCheck()">
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-datasheet" for="datadescr">Datasheet Description</label>
		<input value="<?php print htmlentities($datadescr) ?>" name="datadescr" type="text" class="input-formelement" form="form-datasheet" maxlength="250" title="New datasheet description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-datasheet" for="sel-partcatid">Part Category</label>
		<select name="sel-partcatid" class="select sel-formitem" form="form-datasheet" required="required">
          <?php $myparts->RenderOptionList($list_partcat, $partcatid, false); ?>
        </select>
	  </div>
<?php 
if ($dataid === false)
{
?>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-datasheet" for="dsheetfile">Upload New Datasheet</label>
		<input name="dsheetfile" type="file" class="file-datasheet" title="New datasheet upload" form="form-datasheet">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	  </div>
<?php
}
?>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-datasheet" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" formenctype="multipart/form-data" onclick="delClear()">Save</button>
<?php 
if ($dataid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-datasheet" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" formenctype="multipart/form-data" onclick="delSet()">Delete</button>
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