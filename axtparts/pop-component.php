<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-component.php 209 2017-04-06 21:48:59Z gswan $

// Parameters passed: 
// none: create new component
// $componentid: ID of component to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-component.php";
$formname = "popcomponent";
$formtitle= "Add/Edit Component";

$myparts = new axtparts();
$applyfilter = true;	// Applies the category filter (fc) if present to the part dropdown
$var_fc = "filter_fc";
if ($applyfilter)
	$fc = $myparts->SessionVarRead($var_fc);
else
	$fc = false;

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

$compid = false;
if (isset($_GET['compid']))
	$compid = trim($_GET["compid"]);
if (!is_numeric($compid))
	$compid = false;

$suppid = false;
if (isset($_GET['suppid']))
	$suppid = trim($_GET["suppid"]);
if (!is_numeric($suppid))
	$suppid = false;

if (($compid !== false) && ($suppid !== false))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_s = "delete from suppliers "
			. "\n where suppid='".$dbh->real_escape_string($suppid)."' "
			. "\n and compid='".$dbh->real_escape_string($compid)."' "
			. "\n limit 1"
			;
		$s_s = $dbh->query($q_s);
		if (!$s_s)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else 
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Supplier ".$suppid." removed from: ".$compid;
			$myparts->LogSave($dbh, LOGTYPE_SUPPLDELETE, $uid, $logmsg);
		}
	}
}

// Handle form submission here
if (isset($_POST["btn_supplier"]))
{
	if ($compid !== false)
	{
		if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
			$myparts->AlertMeTo("Insufficient privileges.");
		else
		{
			// Add a supplier for the component
			if (isset($_POST["sel-suppid"]))
				$suppid = trim($_POST["sel-suppid"]);
			if (isset($_POST["suppcatnum"]))
				$suppcatnum = trim($_POST["suppcatnum"]);
			
			$q_s = "insert into suppliers "
				. "\n set "
				. "\n compid='".$dbh->real_escape_string($compid)."', "
				. "\n suppid='".$dbh->real_escape_string($suppid)."', "
				. "\n suppcatno='".$dbh->real_escape_string($suppcatnum)."' "
				;
			$s_s = $dbh->query($q_s);
			if (!$s_s)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Supplier added: ".$suppid.", catnum: ".$suppcatnum;
				$myparts->LogSave($dbh, LOGTYPE_SUPPLNEW, $uid, $logmsg);
			}
		}
	}		
}

if (isset($_POST["btn_save"]))
{
	// Save the component data - must have a part
	if (isset($_POST["sel-partid"]))
	{
		$partid = trim($_POST["sel-partid"]);
		if (!is_numeric($partid))
			$partid = false;
	}
	else 
		$partid = false;
		
	if ($partid !== false)
	{
		if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
			$myparts->AlertMeTo("Insufficient privileges.");
		else
		{
			if (isset($_POST["mfgname"]))
				$mfgname = trim($_POST["mfgname"]);
			else 
				$mfgname = "";
			if (isset($_POST["mfgcode"]))
				$mfgcode = trim($_POST["mfgcode"]);
			else 
				$mfgcode = "";
			if (isset($_POST["sel-compdsheet"]))
				$dataid = trim($_POST["sel-compdsheet"]);
			else 
				$dataid = 0;
			if (isset($_POST["sel-compstatus"]))
				$compstateid = trim($_POST["sel-compstatus"]);
			else 
				$compstateid = 0;
				
			// Upload of datasheet - place it in the directory for the selected part category
			if ($dataid == 0)
			{
				if (isset($_FILES["dsheetfile"]))
				{
					if ($_FILES["dsheetfile"]["error"] == UPLOAD_ERR_OK)
					{
						// Find the datadir for the datasheet, as specified by the part category
						$q_p = "select * "
							. "\n from parts "
							. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
							. "\n where partid='".$dbh->real_escape_string($partid)."' "
							;
						$s_p =$dbh->query($q_p);
						if (!$s_p)
							$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
						else 
						{
							$r_p = $s_p->fetch_assoc();
							$partcatid = $r_p["partcatid"];
							$dsd = $r_p["datadir"];
							$datadir = DATASHEETS_DIR.$dsd;
							if (substr($datadir, -1) != "/")
								$datadir .= "/";
							if (!file_exists($datadir))
							{
								$r = mkdir($datadir, 0755, true);
								if ($r === false)
									$myparts->AlertMeTo("Error: Could not create directory for datasheet(".htmlentities($datadir).").");
							}
							else 
								$r = true;
								
							if ($r === true)
							{
								$ftype = $_FILES["dsheetfile"]["type"];
								$fname = $_FILES["dsheetfile"]["tmp_name"];
								$frealname = $_FILES["dsheetfile"]["name"];
								$frealpath = $datadir.$frealname;
								$r = move_uploaded_file($fname, $frealpath);
								if ($r !== true)
									$myparts->AlertMeTo("Error: Could not move uploaded file to ".htmlentities($frealpath).".");
								else 
								{
									// Save the details in the database as a new datasheet
									if (isset($_POST["datadescr"]))
										$datadescr = trim($_POST["datadescr"]);
									else 
										$datadescr = "";
									
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
										$dataid = $dbh->insert_id;
										$uid = $myparts->SessionMeUID();
										$logmsg = "Datasheet created: ".$datadescr;
										$myparts->LogSave($dbh, LOGTYPE_DSHEETNEW, $uid, $logmsg);
									}
								}
							}
							$s_p->free();
						}
					}
				}
			}
				
			if ($compid === false)
			{
				// new component - insert the values
				$q_p = "insert into components "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n datasheet='".$dbh->real_escape_string($dataid)."', "
					. "\n compstateid='".$dbh->real_escape_string($compstateid)."', "
					. "\n mfgname='".$dbh->real_escape_string($mfgname)."', "
					. "\n mfgcode='".$dbh->real_escape_string($mfgcode)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
					$myparts->UpdateParent();
			}
			else 
			{
				// existing part - update the values
				$q_p = "update components "
					. "\n set "
					. "\n partid='".$dbh->real_escape_string($partid)."', "
					. "\n datasheet='".$dbh->real_escape_string($dataid)."', "
					. "\n compstateid='".$dbh->real_escape_string($compstateid)."', "
					. "\n mfgname='".$dbh->real_escape_string($mfgname)."', "
					. "\n mfgcode='".$dbh->real_escape_string($mfgcode)."' "
					. "\n where compid='".$dbh->real_escape_string($compid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Component created for ".$partid.": ".$mfgcode;
					$myparts->LogSave($dbh, LOGTYPE_COMPNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
	}
	else 
		$myparts->AlertMeTo("Part must be specified.");
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_COMPONENTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Delete the component
		$q_p = "delete from components "
			. "\n where compid='".$dbh->real_escape_string($compid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Component deleted: ".$compid;
			$myparts->LogSave($dbh, LOGTYPE_COMPDELETE, $uid, $logmsg);
			
			$myparts->AlertMeTo("Component deleted.");
			// remove the supplier links to the component
			$q_p = "delete from suppliers "
				. "\n where compid='".$dbh->real_escape_string($compid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		}
		$dbh->close();
		$myparts->UpdateParent();
		$myparts->PopMeClose();
		die();
	}
}


// Get the parts, states, datasheets and suppliers for the lists
$q_d = "select * "
	. "\n from parts "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n left join footprint on footprint.fprintid=parts.footprint "
	;
if ($fc)
	$q_d .= "\n where parts.partcatid='".$dbh->real_escape_string($fc)."' ";
	
$q_d .= "\n order by catdescr, partdescr ";
		
$s_d = $dbh->query($q_d);
$list_parts = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_parts[$i][0] = $r_d["partid"];
		$list_parts[$i][1] = $r_d["catdescr"]." ".$r_d["partdescr"]." ".$r_d["fprintdescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select compstateid, "
	. "\n statedescr "
	. "\n from compstates "
	. "\n order by statedescr "
	;
		
$s_d = $dbh->query($q_d);
$list_states = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_states[$i][0] = $r_d["compstateid"];
		$list_states[$i][1] = $r_d["statedescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select dataid, "
	. "\n datadescr "
	. "\n from datasheets "
	. "\n order by datadescr "
	;
		
$s_d = $dbh->query($q_d);
$list_datasheet = array();
$list_datasheet[0][0] = 0;
$list_datasheet[0][1] = "None or Upload";
$i = 1;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_datasheet[$i][0] = $r_d["dataid"];
		$list_datasheet[$i][1] = $r_d["datadescr"];
		$i++;
	}
	$s_d->free();
}

$q_d = "select cvid, "
	. "\n cvname "
	. "\n from custvend "
	. "\n where cvtype & ".CVTYPE_SUPPLIER
	. "\n order by cvname "
	;
		
$s_d = $dbh->query($q_d);
$list_supplier = array();
$i = 0;
if ($s_d)
{
	while ($r_d = $s_d->fetch_assoc())
	{
		$list_supplier[$i][0] = $r_d["cvid"];
		$list_supplier[$i][1] = $r_d["cvname"];
		$i++;
	}
	$s_d->free();
}

$dset_supp = array();
$ns = 0;

if ($compid !== false)
{
	$urlargs = "?compid=".$compid;
	
	$q_p = "select * "
		. "\n from components "
		. "\n left join datasheets on datasheets.dataid=components.datasheet "
		. "\n left join compstates on compstates.compstateid=components.compstateid "
		. "\n left join parts on parts.partid=components.partid "
		. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
		. "\n where compid='".$dbh->real_escape_string($compid)."' "
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
		$partid = $r_p["partid"];
		$mfgname = $r_p["mfgname"];
		$mfgcode = $r_p["mfgcode"];
		$dataid = $r_p["dataid"];
		$datadescr = $r_p["datadescr"];
		$datasheetpath = $r_p["datasheetpath"];
		$compstateid = $r_p["compstateid"];
		$statedescr = $r_p["statedescr"];
		$partdescr = $r_p["partdescr"];
		$partcat = $r_p["catdescr"];
		$partnum = $r_p["partnumber"];
		$s_p->free();
	}
	
	// Suppliers
	$q_t = "select * "
		. "\n from suppliers "
		. "\n left join custvend on custvend.cvid=suppliers.suppid "
		. "\n where compid='".$dbh->real_escape_string($compid)."' "
		;
	$s_t = $dbh->query($q_t);
	if ($s_t)
	{
		while ($r_t = $s_t->fetch_assoc())
		{
			$dset_supp[$ns]["suppid"] = $r_t["suppid"];
			$dset_supp[$ns]["cvname"] = $r_t["cvname"];
			$dset_supp[$ns]["suppcatno"] = $r_t["suppcatno"];
			$ns++;
		}
		$s_t->free();
	}
}
else 
{
	$urlargs="";
	$partid = false;
	$mfgname = "";
	$mfgcode = "";
	$dataid = false;
	$datadescr = "";
	$datasheetpath = "";
	$compstateid = false;
	$statedescr = "";
	$partdescr = "";
	$partcat = "";
	$partnum = "";
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
    <span class="text-element text-poptitle"><?php print ($compid === false ? "Add New Component" : "Edit Component") ?></span>
    <form class="form-container form-pop-component" name="form-component" id="form-component" action="<?php print $url ?>" method="post" enctype="multipart/form-data" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="sel-partid">Part</label>
		<select name="sel-partid" class="select sel-formitem" form="form-component">
          <?php $myparts->RenderOptionList($list_parts, $partid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="mfgname">Manufacturer</label>
		<input value="<?php print htmlentities($mfgname) ?>" name="mfgname" type="text" class="input-formelement" form="form-component" maxlength="100" title="Component manufacturer">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="mfgcode">Device Number</label>
		<input value="<?php print htmlentities($mfgcode) ?>" name="mfgcode" type="text" class="input-formelement" form="form-component" maxlength="100" title="Component device number">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="sel-compdsheet">Existing Datasheet</label>
		<select name="sel-compdsheet" class="select sel-formitem" form="form-component">
          <?php $myparts->RenderOptionList($list_datasheet, $dataid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="dsheetfile">Upload New Datasheet</label>
		<input name="dsheetfile" type="file" class="file-datasheet" title="New datasheet upload" form="form-component">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php print MAX_DATASHEET_UPLOAD_SIZE ?>" />
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="datadescr">New Datasheet Description</label>
		<input value="" name="datadescr" type="text" class="input-formelement" form="form-component" maxlength="100" title="New datasheet description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-component" for="sel-compstatus">Component Status</label>
		<select name="sel-compstatus" class="select sel-formitem" form="form-component">
          <?php $myparts->RenderOptionList($list_states, $compstateid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-component" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_update" formenctype="multipart/form-data" onclick="delClear()">Save</button>
<?php
if ($compid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-component" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" formenctype="multipart/form-data" onclick="delSet()">Delete</button>
<?php
}
?>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
<?php
if ($compid !== false)
{
	// Assign suppliers to this component
?>
    <span class="text-element text-poptitle">Add supplier for this component</span>
    <form class="form-container form-pop-supplier" method="post" name="form-supplier" id="form-supplier" action="<?php print $url ?>">
      <div class="container container-pop-el">
	  <label class="label label-formitem" form="form-supplier" for="sel-suppid">Supplier</label>
	  <select name="sel-suppid" class="select sel-formitem" form="form-supplier">
          <?php $myparts->RenderOptionList($list_supplier, false, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-supplier" for="suppcatnum">Supplier Cat Number</label>
		<input value="" name="suppcatnum" type="text" class="input-formelement" form="form-supplier" maxlength="40" title="Supplier's catalogue number">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-supplier" formaction="<?php print $url ?>" value="Add" name="btn_supplier" id="btn_supplier">Add</button>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
    <span class="text-element text-poptitle">Suppliers for this component</span>
    <div class="container container-pop-compsuppliers">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Remove</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Supplier</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Device No</span>
      </div>
<?php 
	for ($i = 0; $i < $ns; $i++)
	{
		if ($i%2 == 0)
			$stline = "evn";
		else
			$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="<?php print $url."&suppid=".$dset_supp[$i]["suppid"] ?>" title="Remove supplier for component">Remove</a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_supp[$i]["cvname"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_supp[$i]["suppcatno"]) ?></span>
      </div>
<?php
	}
?>
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