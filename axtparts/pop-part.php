<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-part.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// none: create new part
// $partid: ID of part to edit

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-part.php";
$formname = "poppart";
$formtitle= "Add/Edit Part";

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

$partid = false;
if (isset($_GET['partid']))
	$partid = trim($_GET["partid"]);
if (!is_numeric($partid))
	$partid = false;

$fc = false;
if (isset($_GET['fc']))
	$fc = trim($_GET["fc"]);
if (!is_numeric($fc))
	$fc = false;
	
// Handle part form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["partdescr"]))
			$partdescr = trim($_POST["partdescr"]);
		else 
			$partdescr = "";
		if (isset($_POST["sel-partcat"]))
			$partcatid = trim($_POST["sel-partcat"]);
		else 
			$partcatid = 0;
		if (isset($_POST["sel-fprint"]))
			$fprintid = trim($_POST["sel-fprint"]);
		else 	
			$fprintid = 0;
			
		if ($partid === false)
		{
			// new part - insert the values and generate the part number
			$q_p = "insert into parts "
				. "\n set "
				. "\n partdescr='".$dbh->real_escape_string($partdescr)."', "
				. "\n partcatid='".$dbh->real_escape_string($partcatid)."', "
				. "\n footprint='".$dbh->real_escape_string($fprintid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else 
			{
				$newpartid = $dbh->insert_id;
				$partnum = $myparts->CalcPartNumber(str_pad($newpartid, 6, "0", STR_PAD_LEFT), PARTPREFIX);
			
				$q_p = "update parts "
					. "\n set "
					. "\n partnumber='".$dbh->real_escape_string($partnum)."' "
					. "\n where partid='".$dbh->real_escape_string($newpartid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Part created: ".$partdescr;
					$myparts->LogSave($dbh, LOGTYPE_PARTNEW, $uid, $logmsg);
					$myparts->UpdateParent();
				}
			}
		}
		else 
		{
			// existing part - update the values
			$q_p = "update parts "
				. "\n set "
				. "\n partdescr='".$dbh->real_escape_string($partdescr)."', "
				. "\n partcatid='".$dbh->real_escape_string($partcatid)."', "
				. "\n footprint='".$dbh->real_escape_string($fprintid)."' "
				. "\n where partid='".$dbh->real_escape_string($partid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else
			{
				$uid = $myparts->SessionMeUID();
				$logmsg = "Part updated: ".$partdescr;
				$myparts->LogSave($dbh, LOGTYPE_PARTCHANGE, $uid, $logmsg);
				$myparts->UpdateParent();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// Cannot delete parts if still attached to boms or components
		$nb = $myparts->ReturnCountOf($dbh, "boms", "bomid", "partid", $partid);
		if ($nb > 0)
			$myparts->AlertMeTo("Part still used in ".$nb." BOMs.");
		else 
		{
			$nc = $myparts->ReturnCountOf($dbh, "components", "compid", "partid", $partid);
			if ($nc > 0)
				$myparts->AlertMeTo("Part still used by ".$nc." components.");
			else 
			{
				$na = $myparts->ReturnCountOf($dbh, "assemblies", "assyid", "partid", $partid);
				if ($na > 0)
					$myparts->AlertMeTo("Part still allocated to ".$na." assemblies.");
				else 
				{
					// Unattached part can be deleted
					$q_p = "delete from parts "
						. "\n where partid='".$dbh->real_escape_string($partid)."' "
						. "\n limit 1 "
						;
					$s_p = $dbh->query($q_p);
					if (!$s_p)
						$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
					else
					{
						$uid = $myparts->SessionMeUID();
						$logmsg = "Part deleted: ".$assydescr;
						$myparts->LogSave($dbh, LOGTYPE_PARTDELETE, $uid, $logmsg);
						$myparts->AlertMeTo("Part deleted.");
					}
					$dbh->close();
					$myparts->PopMeClose();
					die();
				}
			}
		}
	}
}

// Get the footprints and part categories for the lists
$q_fprint = "select fprintid, "
		. "\n fprintdescr "
		. "\n from footprint "
		. "\n order by fprintdescr "
		;
		
$s_fprint = $dbh->query($q_fprint);
$list_fprint = array();
$list_fprint[0][0] = 0;
$list_fprint[0][1] = "None";
$i = 1;
if ($s_fprint)
{
	while ($r_fprint = $s_fprint->fetch_assoc())
	{
		$list_fprint[$i][0] = $r_fprint["fprintid"];
		$list_fprint[$i][1] = $r_fprint["fprintdescr"];
		$i++;
	}
	$s_fprint->free();
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

if ($partid !== false)
{
	$urlargs = "?partid=".$partid;
	if ($fc !== false)
		$urlargs .= "&fc=".$fc;

	$q_p = "select partnumber, "
		. "\n partdescr, "
		. "\n partcatid, "
		. "\n footprint "
		. "\n from parts "
		. "\n where partid='".$dbh->real_escape_string($partid)."' "
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
		$partdescr = $r_p["partdescr"];
		$partcatid = $r_p["partcatid"];
		$partfprint = $r_p["footprint"];
		$s_p->free();
	}
}
else
{
	$urlargs = ($fc === false ? "" : "?fc=".$fc);
	$partnum = "";
	$partdescr = "";
	$partcatid = $fc;
	$partfprint = "";
}

$q_t = "select * from components "
	. "\n left join compstates on compstates.compstateid=components.compstateid "
	. "\n left join datasheets on datasheets.dataid=components.datasheet "
	. "\n left join pgroups on pgroups.partcatid=datasheets.partcatid "
	. "\n where partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by mfgname "
	;
$s_t = $dbh->query($q_t);
$cc = 0;
$dset_comps = array();
if ($s_t)
{
	while ($r_t = $s_t->fetch_assoc())
	{
		$dset_comps[$cc] = $r_t;
		if ($r_t["datadescr"] != NULL)
		{
			$dset_comps[$cc]["dsheetpath"] = DATASHEETS_DIR.$r_t["datadir"];
			if (substr($dset_comps[$cc]["dsheetpath"], -1) != "/")
				$dset_comps[$cc]["dsheetpath"] .= "/";
			$dset_comps[$cc]["dsheetpath"] .= $r_t["datasheetpath"];
		}
		else
			$dset_comps[$cc]["dsheetpath"] = false;
		$cc++;
	}
	$s_t->free();
}

$q_t = "select * from boms "
	. "\n left join assemblies on assemblies.assyid=boms.assyid "
	. "\n where boms.partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by assydescr, assyrev "
	;
$s_t = $dbh->query($q_t);
$ca = 0;
$dset_assy = array();
if ($s_t)
{
	while ($r_t = $s_t->fetch_assoc())
	{
		$q_a = "select partdescr "
			. "\n from parts "
			. "\n where partid='".$r_t["partid"]."' "
			;
		$s_a = $dbh->query($q_a);
		if ($s_a)
		{
			$r_a = $s_a->fetch_assoc();
			$dset_assy[$ca] = $r_t;
			$dset_assy[$ca]["partdescr"] = $r_a["partdescr"];
			$s_a->free();
		}
		$ca++;
	}
	$s_t->free();
}

$q_stk = "select * from stock "
	. "\n left join locn on locn.locid=stock.locid "
	. "\n where partid='".$dbh->real_escape_string($partid)."' "
	. "\n order by locref "
	;
$s_stk = $dbh->query($q_stk);
$cs = 0;
$dset_stock = array();
if ($s_stk)
{
	while ($r_stk = $s_stk->fetch_assoc())
	{
		$dset_stock[$cs] = $r_stk;
		$cs++;
	}
	$s_stk->free();
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
    <span class="text-element text-poptitle"><?php print ($partid === false ? "Add New Part" : "Edit Part") ?></span>
    <form class="form-container form-pop-part" name="form-part" id="form-part" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-login" for="userid">Part Number</label>
        <span class="text-element text-pop-dataitem"><?php print htmlentities($partnum) ?></span>
      </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-part" for="partdescr">Part Description</label>
		<input value="<?php print htmlentities($partdescr) ?>" name="partdescr" type="text" class="input-formelement" form="form-part" maxlength="100" title="Part Description">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-part" for="partcat">Part Category</label>
		<select name="sel-partcat" class="select sel-formitem" form="form-part">
          <?php $myparts->RenderOptionList($list_partcat, $partcatid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-part" for="sel-fprint">Part Footprint</label>
		<select name="sel-fprint" class="select sel-formitem" form="form-part">
          <?php $myparts->RenderOptionList($list_fprint, $partfprint, false); ?>
        </select>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-part" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" onclick="delClear()">Save</button>
<?php
if ($partid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-part" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
<?php
}
?>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
    <span class="text-element text-poptitle">Components referencing this part</span>
    <div class="container container-pop-partcompref">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Mfg Part</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Dsht</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Status</span>
      </div>
<?php
$nd = count($dset_comps);
for ($i = 0; $i < $nd; $i++)
{
	if ($i%2 == 0)
		$stline = "evn";
	else
		$stline = "odd";
	
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_comps[$i]["mfgname"])." ".htmlentities($dset_comps[$i]["mfgcode"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
	    <a href="javascript:popupOpener('<?php print $dset_comps[$i]["dsheetpath"] ?>','pop_showdatasheet',800,800)" class="responsive-picture imglink-datasheet">
          <picture>
            <img alt="Datasheet" width="16" height="16" src="./images/icon-pdf.png" loading="lazy">
          </picture>
        </a>
	  </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_comps[$i]["statedescr"]) ?></span>
      </div>
<?php
}
?>
    </div>
    <div class="rule rule-popsection">
      <hr>
    </div>
    <span class="text-element text-poptitle">Assembly BOMs using this part</span>
    <div class="container container-pop-partassybom">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Assembly</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">R-AW</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
<?php 
for ($i = 0; $i < $ca; $i++)
{
	if ($i%2 == 0)
		$stline = "evn";
	else
		$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_assy[$i]["partdescr"])." (".htmlentities($dset_assy[$i]["assydescr"]).")" ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print (str_pad($dset_assy[$i]["assyrev"], 2, "0", STR_PAD_LEFT))." - ".$dset_assy[$i]["assyaw"] ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_assy[$i]["qty"]) ?></span>
      </div>
<?php
}
?>
    </div>
    <div class="rule rule-popsection">
      <hr>
    </div>
    <span class="text-element text-poptitle">Part Stock</span>
    <div class="container container-pop-partstock">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Location</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">LocRef</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Note</span>
      </div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-locnconfig.php','pop_locnconfig',600,600)" title="Configure locations">Configure...</a>
      </div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-locn.php','pop_locn',600,600)" title="Add location detail">Add...</a>
      </div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-stock.php<?php print $urlargs ?>','pop_stock',600,600)" title="Add stock detail">Add...</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
<?php
for ($i = 0; $i < $cs; $i++)
{
	if ($i%2 == 0)
		$stline = "evn";
	else
		$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_stock[$i]["locdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-locn.php?locid=<?php print $dset_stock[$i]["locid"] ?>','pop_locn',600,600)" title="Edit location detail"><?php print htmlentities($dset_stock[$i]["locref"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-stock.php<?php print $urlargs."&stockid=".$dset_stock[$i]["stockid"] ?>','pop_stock',600,600)" title="Edit stock detail"><?php print htmlentities($dset_stock[$i]["qty"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset_stock[$i]["note"]) ?></span>
      </div>
<?php
}
?>
    </div>
    <div class="rule rule-popsection">
      <hr>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>