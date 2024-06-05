<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-engdoc.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new documents
// engdocid: ID of document to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-engdoc.php";
$formname = "popengdoc";
$formtitle= "Add/Edit Document Detail";

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

$docid = false;
$assyid = false;
if (isset($_GET['engdocid']))
	$docid = trim($_GET["engdocid"]);
if (!is_numeric($docid))
	$docid = false;

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ENGDOCS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["docdescr"]))
			$docdescr = trim($_POST["docdescr"]);
		else 
			$docdescr = "";
		
		if ($docdescr == "")
			$myparts->AlertMeTo("Require a document description.");
		else
		{
			if (isset($_POST["sel-assyid"]))
				$assyid = trim($_POST["sel-assyid"]);
			else 
				$assyid = "";
		
			if (($assyid == "") && ($docid === false))
				$myparts->AlertMeTo("Require an assembly selection.");
			else 
			{
				if ($docid === false)
				{
					// Upload of new document - place it in the directory under the selected assembly
					if (isset($_FILES["docfile"]))
					{
						if ($_FILES["docfile"]["error"] == UPLOAD_ERR_OK)
						{
							// Find the docpath for the document, as specified by the assembly part number
							$q_p = "select partnumber "
								. "\n from assemblies "
								. "\n left join parts on parts.partid=assemblies.partid "
								. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
								;
								
							$s_p =$dbh->query($q_p);
							if (!$s_p)
								$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
							else 
							{
								$r_p = $s_p->fetch_assoc();
								$partnum = $r_p["partnumber"];
								$s_p->free();
								
								$docdir = ENGDOC_DIR.$partnum."/";
								if (!file_exists($docdir))
								{
									$r = mkdir($docdir, 0755, true);
									if ($r === false)
										$myparts->AlertMeTo("Error: Could not create directory for document(".htmlentities($docdir).").");
								}
								else 
									$r = true;
									
								if ($r === true)
								{
									$ftype = $_FILES["docfile"]["type"];
									$fname = $_FILES["docfile"]["tmp_name"];
									$frealname = $_FILES["docfile"]["name"];
									$frealpath = $docdir.$frealname;
									$r = move_uploaded_file($fname, $frealpath);
									if ($r !== true)
										$myparts->AlertMeTo("Error: Could not move uploaded file to ".htmlentities($frealpath).".");
									else 
									{
										$q_d = "insert into engdocs "
											. "\n set "
											. "\n engdocpath='".$dbh->real_escape_string($frealpath)."', "
											. "\n engdocdescr='".$dbh->real_escape_string($docdescr)."', "
											. "\n assyid='".$dbh->real_escape_string($assyid)."' "
											;
											
										$s_d = $dbh->query($q_d);
										if (!$s_d)
											$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
										else 
										{
											$uid = $myparts->SessionMeUID();
											$logmsg = "Document created: ".$frealpath;
											$myparts->LogSave($dbh, LOGTYPE_EDOCNEW, $uid, $logmsg);
											$myparts->AlertMeTo("Document saved to ".htmlentities($frealpath).".");
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
					// existing - update the values only - description only, as assembly is part of the file path
					$q_p = "update engdocs "
						. "\n set "
						. "\n engdocdescr='".$dbh->real_escape_string($docdescr)."' "
						. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
						;
						
					$s_p = $dbh->query($q_p);
					if (!$s_p)
						$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
					else
					{
						$uid = $myparts->SessionMeUID();
						$logmsg = "Document metadata updated: ".$docdescr;
						$myparts->LogSave($dbh, LOGTYPE_EDOCCHANGE, $uid, $logmsg);
						$myparts->UpdateParent();
					}
				}
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ENGDOCS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Remove the reference, not the file?
		$q_p = "delete from engdocs "
			. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
			. "\n limit 1 "
			;
			
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Document removed: ".$docid;
			$myparts->LogSave($dbh, LOGTYPE_EDOCDELETE, $uid, $logmsg);
			$myparts->AlertMeTo("Document deleted.");
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

if ($docid !== false)
{
	$urlargs = "?engdocid=".$docid;
	
	$q_p = "select engdocid, "
		. "\n engdocdescr, "
		. "\n assyid, "
		. "\n engdocpath "
		. "\n from engdocs "
		. "\n where engdocid='".$dbh->real_escape_string($docid)."' "
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
		$docdescr = $r_p["engdocdescr"];
		$assyid = $r_p["assyid"];
		$docpath = $r_p["engdocpath"];
		$s_p->free();
	}
}
else 
{
	$urlargs="";
	$docdescr = "";
	$assyid = 0;
	$docpath = "";
}

$q_assy = "select assyid, "
		. "\n assydescr, "
		. "\n assyrev, "
		. "\n assyaw, "
		. "\n partdescr, "
		. "\n partnumber "
		. "\n from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n order by partdescr "
		;
		
$s_assy = $dbh->query($q_assy);
$list_assy = array();
$i = 0;
if ($s_assy)
{
	while ($r_assy = $s_assy->fetch_assoc())
	{
		$list_assy[$i][0] = $r_assy["assyid"];
		$list_assy[$i][1] = $r_assy["partdescr"]." - ".$r_assy["partnumber"].": (R".str_pad($r_assy["assyrev"], 2, "0", STR_PAD_LEFT)."/".$r_assy["assyaw"].")";
		$i++;
	}
	$s_assy->free();
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
<?php
if ($docid === false)
{
?>
    <span class="text-element text-poptitle">Add New Document</span>
    <form class="form-container form-pop-engdoc" name="form-engdoc" id="form-engdoc" action="<?php print $url ?>" method="post" enctype="multipart/form-data">
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="docdesc">Document Description</label>
		<input value="" name="docdescr" type="text" class="input-formelement" form="form-engdoc" maxlength="250" title="New document description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="sel-assyid">Assembly</label>
		<select name="sel-assyid" class="select sel-formitem" form="form-engdoc" required="required">
          <?php $myparts->RenderOptionList($list_assy, $assyid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="docfile">Upload New Document</label>
		<input name="docfile" type="file" class="file-datasheet" title="New document upload" form="form-engdoc">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-engdoc" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" formenctype="multipart/form-data" onclick="delClear()">Save</button>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
}
else
{
?>
    <span class="text-element text-poptitle">Edit Document Description</span>
    <form class="form-container form-pop-engdoc" name="form-engdoc" id="form-engdoc" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="docdescr">Document Description</label>
		<input value="<?php print htmlentities($docdescr) ?>" name="docdescr" type="text" class="input-formelement" form="form-engdoc" maxlength="250" title="Document description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="sel-assyid">Assembly</label>
		<select name="sel-assyid" class="select sel-formitem" form="form-engdoc" disabled="disabled">
          <?php $myparts->RenderOptionList($list_assy, $assyid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-engdoc" for="docpath">Path</label>
		<input value="<?php print htmlentities($docpath) ?>" name="docpath" type="text" class="input-formelement" form="form-engdoc" maxlength="120" title="Document file path" readonly>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-engdoc" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" formenctype="multipart/form-data" onclick="delClear()">Save</button>
		<button type="submit" class="btn-pop-delete" form="form-engdoc" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
  </div>
<?php
}
?>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>