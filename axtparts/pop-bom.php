<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bom.php 215 2018-01-07 11:25:38Z gswan $

// Parameters passed: 
// none: create new BOM
// $assyid: ID of assembly to edit BOM
// $variantid: ID of the assembly variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-bom.php";
$formname = "popbom";
$formtitle= "Add/Edit Assembly BOM";

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
$showstock = false;
$dset = array();

if (isset($_GET['assyid']))
	$assyid = trim($_GET["assyid"]);
if (!is_numeric($assyid))
	$assyid = false;

if ($assyid !== false)
{
	$variantid = false;
	if (isset($_GET['variantid']))
		$variantid = trim($_GET["variantid"]);
	if (!is_numeric($variantid))
		$variantid = false;
		
	if ($variantid === false)
	{
		$dbh->close();
		$myparts->AlertMeTo("A variant must be specified, or bad value ".trim($_GET["variantid"]).".");
		$myparts->PopMeClose();
		die();
	}
	
	if (isset($_GET['showstock']))
		$showstock = trim($_GET["showstock"]);
	if (!is_numeric($showstock))
		$showstock = false;
}		
	
// Handle BOM Copy request
if (isset($_POST["btn_copybom"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["sel-bomcopyvid"]))
		{
			$bcvariantid = $_POST["sel-bomcopyvid"];
			if (is_numeric($bcvariantid))
			{
				// Use the bc variantid to read all bomvariant items matching and copy to the new variantid
				$q_bomsrc = "select * "
						. "\n from bomvariants "
						. "\n left join boms on boms.bomid=bomvariants.bomid "
						. "\n where variantid='".$dbh->real_escape_string($bcvariantid)."' "
						;
				$s_bomsrc = $dbh->query($q_bomsrc);
				$bcstop = false;
				if ($s_bomsrc)
				{
					while ($r_bomsrc = $s_bomsrc->fetch_assoc())
					{
						if (!$bcstop)
						{
							$dbom_partid = $r_bomsrc["partid"];
							$dbom_qty = $r_bomsrc["qty"];
							$dbom_ref = $r_bomsrc["ref"];
							$dbom_um = $r_bomsrc["um"];
							$dbom_alt = $r_bomsrc["alt"];
							
							// Insert a new bom line first
							$q_dbom = "insert into boms "
								. "\n set "
								. "\n partid='".$dbh->real_escape_string($dbom_partid)."', "
								. "\n assyid='".$dbh->real_escape_string($assyid)."', "
								. "\n qty='".$dbh->real_escape_string($dbom_qty)."', "
								. "\n um='".$dbh->real_escape_string($dbom_um)."', "
								. "\n ref='".$dbh->real_escape_string($dbom_ref)."', "
								. "\n alt='".$dbh->real_escape_string($dbom_alt)."' "
								;
							$s_dbom = $dbh->query($q_dbom);
							if ($s_dbom)
							{
								$dbom_bomid = $dbh->insert_id;
									
								// add the bomline to the variant table
								$q_v = "insert into bomvariants "
									. "\n set "
									. "\n variantid='".$dbh->real_escape_string($variantid)."', "
									. "\n bomid='".$dbh->real_escape_string($dbom_bomid)."' "
									;
								$s_v = $dbh->query($q_v);
							}
							else
							{
								$bcstop = true;
								$myparts->AlertMeTo("Error: ".(htmlentities($dbh->error, ENT_COMPAT)));
							}
						}
					}
					$s_bomsrc->free();
				}
			}
			else
				$myparts->AlertMeTo("Bad BOM variant specified for copy.");
		}
		else
			$myparts->AlertMeTo("BOM variantid for copy must be specified.");
	}
}


// Handle new BOM form submission here
if (isset($_POST["btn_newbom"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMITEMS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		// A 'new' BOM simply consists of setting the assyid and variantid
		// Nothing is saved into the database until a part is allocated to the BOM
		if (isset($_POST["sel-assy"]))
		{
			$assyid = trim($_POST["sel-assy"]);
			if ($assyid == "")
				$assyid = false;
		}
		else 
			$assyid = false;
			
		if (isset($_POST["sel-variant"]))
		{
			$variantid = trim($_POST["sel-variant"]);
			if ($variantid == "")
				$variantid = false;
		}
		else 
			$variantid = false;

		if (($assyid === false) || ($variantid === false))
			$myparts->AlertMeTo("An assembly and a variant must be specified.");
		else
		{
			$urlargs = "?assyid=".$assyid."&variantid=".$variantid."&showstock=".$showstock;
			$myparts->VectorMeTo($formfile.$urlargs);
		}
	}
}		

if ($assyid !== false)
{
	$urlargs = "?assyid=".$assyid."&variantid=".$variantid."&showstock=".$showstock;
	
	// Get the details for the assembly
	$q_a = "select * "
		. "\n from assemblies "
		. "\n left join parts on parts.partid=assemblies.partid "
		. "\n where assyid='".$dbh->real_escape_string($assyid)."' "
		;
	$s_a = $dbh->query($q_a);
	if (!$s_a)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_a = $s_a->fetch_assoc();
		$assy["partnumber"] = $r_a["partnumber"];
		$assy["assyname"] = $r_a["partdescr"];
		$assy["assydescr"] = $r_a["assydescr"];
		$assy["assyrev"] = str_pad($r_a["assyrev"], 2, "0", STR_PAD_LEFT);
		$assy["assyaw"] = $r_a["assyaw"];
		$s_a->free();
	}
	
	// Get the details for the variant
	$q_v = "select variantid, "
		. "\n variantname, "
		. "\n variantdescr, "
		. "\n variantstate "
		. "\n from variant "
		. "\n where variantid='".$dbh->real_escape_string($variantid)."' "
		;
	$s_v = $dbh->query($q_v);
	if (!$s_v)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$r_v = $s_v->fetch_assoc();
		$variant["variantname"] = $r_v["variantname"];
		$variant["variantdescr"] = $r_v["variantdescr"];
		$variant["variantstate"] = $r_v["variantstate"];
		$s_v->free();
	}
	
	// Get the BOM items for the assembly/variant
	$q_b = "select * "
			. "\n from boms "
			. "\n left join parts on parts.partid=boms.partid "
			. "\n left join assemblies on assemblies.assyid=boms.assyid "
			. "\n left join bomvariants on bomvariants.bomid=boms.bomid "
			. "\n left join variant on variant.variantid=bomvariants.variantid "
			. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
			. "\n left join footprint on footprint.fprintid=parts.footprint "
			. "\n where boms.assyid='".$dbh->real_escape_string($assyid)."' "
			. "\n and bomvariants.variantid='".$dbh->real_escape_string($variantid)."' "
			. "\n order by catdescr, partdescr "
			;
		
	$s_b = $dbh->query($q_b);
	if (!$s_b)
	{
		$dbh->close();
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_QUOTES));
		$myparts->PopMeClose();
		die();
	}
	else 
	{
		$i = 0;
		while ($r_b = $s_b->fetch_assoc())
		{
			$dset[$i]["partnumber"] = $r_b["partnumber"];
			$dset[$i]["catdescr"] = $r_b["catdescr"];
			$dset[$i]["partdescr"] = $r_b["partdescr"];
			$dset[$i]["footprint"] = $r_b["fprintdescr"];
			$dset[$i]["qty"] = $r_b["qty"];
			$dset[$i]["um"] = $r_b["um"];
			$dset[$i]["ref"] = $r_b["ref"];
			$dset[$i]["altid"] = $r_b["alt"];
			$dset[$i]["bomid"] = $r_b["bomid"];
			
			// If alt is > 0 then find the part detail for it.
			$altid = $r_b["alt"];
			$dset[$i]["altpartnumber"] = "";
			$dset[$i]["altcatdescr"] = "";
			$dset[$i]["altpartdescr"] = "";
			$dset[$i]["altfprint"] = "";
			if (($altid != null) && ($altid > 0))
			{
				$q_alt = "select * "
					. "\n from parts "
					. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
					. "\n left join footprint on footprint.fprintid=parts.footprint "
					. "\n where partid='".$dbh->real_escape_string($altid)."' "
					;
				$s_alt = $dbh->query($q_alt);
				if ($s_alt)
				{
					$r_alt = $s_alt->fetch_assoc();
					$dset[$i]["altpartnumber"] = $r_alt["partnumber"];
					$dset[$i]["altcatdescr"] = $r_alt["catdescr"];
					$dset[$i]["altpartdescr"] = $r_alt["partdescr"];
					$dset[$i]["altfprint"] = $r_alt["fprintdescr"];
					$s_alt->free();
				}
			}
			
			// If we need to show stock levels for the part
			if ($showstock !== false)
			{
				$q_p = "select partid "
					. "\n from parts "
					. "\n where partnumber='".$r_b["partnumber"]."' "
					;
				$s_p = $dbh->query($q_p);
				$r_p = array();
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
					$s_p->free();
				}
				
				$q_stk = "select sum(qty) as stockqty "
					. "\n from stock "
					. "\n where partid='".$r_p["partid"]."' "
					;
				$s_stk = $dbh->query($q_stk);
				$dset[$i]["stockqty"] = 0;
				$dset[$i]["stockloc"] = "";
				if ($s_stk)
				{
					$r_stk = $s_stk->fetch_assoc();
					if ($r_stk["stockqty"] !== null)
					{
						$dset[$i]["stockqty"] = $r_stk["stockqty"];
				
						$q_sl = "select * "
							. "\n from stock "
							. "\n left join locn on locn.locid=stock.locid "
							. "\n where partid='".$r_p["partid"]."' "
							;
						$s_sl = $dbh->query($q_sl);
						if ($s_sl)
						{
							while ($r_sl = $s_sl->fetch_assoc())
								$dset[$i]["stockloc"] .= $r_sl["locref"].", ";
							$dset[$i]["stockloc"] = substr($dset[$i]["stockloc"], 0, -2);
							$s_sl->free();
						}
					}
					$s_stk->free();
				}
			}
			$i++;
		}
		$s_b->free();
	}
	
	// Allow a copy from an existing BOM only if there are no components already assigned to this BOM.
	$allowcopy = false;
	$list_bomcopy = array();
	$b = 0;
	if (count($dset) == 0)	// no components
	{
		// Create a list of assemblies to copy a bom from
		$q_v = "select distinct bomvariants.variantid, "
			. "\n variantname, "
			. "\n variantdescr, "
			. "\n assyid "
			. "\n from bomvariants "
			. "\n left join variant on variant.variantid=bomvariants.variantid "
			. "\n left join boms on boms.bomid=bomvariants.bomid "
			. "\n order by variantdescr"
			;
		$s_v = $dbh->query($q_v);
		if ($s_v)
		{
			while ($r_v = $s_v->fetch_assoc())
			{
				$list_bomcopy[$b][0] = $r_v["variantid"];
				$list_bomcopy[$b][1] = $r_v["variantname"]." (".$r_v["variantdescr"].")";
				$b++;
			}
			$s_v->free();
		}
		if ($b > 0)
			$allowcopy = true;
	}
}
else 
{
	$urlargs="";
	// Get a list of assemblies and variants if we are adding a new BOM
	$q_assy = "select * "
			. "\n from assemblies "
			. "\n left join parts on parts.partid=assemblies.partid "
			. "\n order by partdescr, assyrev, assyaw "
			;
				
	$s_assy = $dbh->query($q_assy);
	$list_assy = array();
	$i = 0;
	if ($s_assy)
	{
		while ($r_assy = $s_assy->fetch_assoc())
		{
			$assyrev = str_pad($r_assy["assyrev"], 2, "0", STR_PAD_LEFT);
			$assyaw = $r_assy["assyaw"];
			$list_assy[$i][0] = $r_assy["assyid"];
			$list_assy[$i][1] = $r_assy["partdescr"]." - ".$assyrev.($assyaw == null ? "" : "-".$assyaw)." (".$r_assy["assydescr"].")";
			$i++;
		}
		$s_assy->free();
	}
	
	$q_var = "select variantid, "
			. "\n variantname, "
			. "\n variantdescr, "
			. "\n variantstate "
			. "\n from variant "
			. "\n order by variantname, variantdescr "
			;
				
	$s_var = $dbh->query($q_var);
	$list_var = array();
	$i = 0;
	if ($s_var)
	{
		while ($r_var = $s_var->fetch_assoc())
		{
			$list_var[$i][0] = $r_var["variantid"];
			$list_var[$i][1] = $r_var["variantname"]." (".$r_var["variantdescr"]." - ".$r_var["variantstate"].")";
			$i++;
		}
		$s_var->free();
	}
}

$myparts->UpdateParent();
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
if ($assyid === false)
{
?>
    <span class="text-element text-poptitle">Add New BOM</span>
    <form class="form-container form-pop-newbom" name="form-newbom" id="form-newbom" action="<?php print $url ?>" method="post">
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-newbom" for="sel-assy">Assembly</label>
		<select name="sel-assy" class="select sel-formitem" form="form-newbom">
          <?php $myparts->RenderOptionList($list_assy, false, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-newbom" for="sel-variant">Variant</label>
		<select name="sel-variant" class="select sel-formitem" form="form-newbom">
          <?php $myparts->RenderOptionList($list_var, false, false); ?>
        </select>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-newbom" formaction="<?php print $url ?>" value="Save" name="btn_newbom" id="btn_newbom">Save</button>
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
    <span class="text-element text-bomtitle">Bill of Materials</span>
    <div class="container container-pop-bombuttons">
      <div class="container container-pop-btn">
<?php
	if ($showstock === false)
	{
?>
	    <button type="button" class="btn-pop-button" title="Show stock levels" onclick="javascript:top.location.href='<?php print $formfile."?assyid=".$assyid."&variantid=".$variantid."&showstock=1" ?>'">Show Stock</button>
		<button type="button" class="btn-pop-button" title="View printable BOM" onclick="javascript:popupOpenerMenus('pop-bomprintview.php?assyid=<?php print $assyid."&variantid=".$variantid ?>','pop_bomprint',1200,900)">Print</button>
<?php
	}
	else
	{
?>
	    <button type="button" class="btn-pop-button" title="Hide stock levels" onclick="javascript:top.location.href='<?php print $formfile."?assyid=".$assyid."&variantid=".$variantid ?>'">Hide Stock</button>
		<button type="button" class="btn-pop-button" title="View printable BOM" onclick="javascript:popupOpenerMenus('pop-bomprintview.php?assyid=<?php print $assyid."&variantid=".$variantid."&showstock=1" ?>','pop_bomprint',1200,900)">Print</button>
<?php
	}
?>
	  </div>
    </div>
<?php 
	// If there are no components already entered into this BOM them allow a copy of an existing BOM 
	if ($allowcopy === true)
	{
?>		
	<div class="container container-pop-bom-copy">
      <span class="text-element text-pop-databold">Copy Existing BOM</span>
      <form class="form-container form-pop-bom-copy" name="form-bomcopy" id="form-bomcopy" action="<?php print $url ?>" method="post">
        <div class="container container-form-element">
		  <label class="label label-formitem" for="sel-bomcopyvid" form="form-bomcopy">Select Existing BOM</label>
		  <select name="sel-bomcopyvid" class="select sel-formitem" form="form-bomcopy">
            <?php $myparts->RenderOptionList($list_bomcopy, false, false) ?>
          </select>
		</div>
        <div class="container container-form-element">
          <span class="text-element text-pop-datalabel">&nbsp;</span>
		  <button type="submit" class="btn-pop-button" name="btn_copybom" id="btn_copybom" value="Copy" form="form-bomcopy" formaction="<?php print $url ?>">Submit</button>
        </div>
      </form>
    </div>		
<?php
	}
?>		
    <div class="container container-pop-bomhead">
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel">Assembly</span>
        <span class="text-element text-pop-databold"><?php print htmlentities($assy["assyname"])." ".htmlentities($assy["assydescr"]) ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel">Revision</span>
        <span class="text-element text-pop-databold"><?php print $assy["assyrev"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel">Artwork</span>
        <span class="text-element text-pop-databold"><?php print $assy["assyaw"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel">Variant</span>
        <span class="text-element text-pop-databold"><?php print htmlentities($variant["variantname"])." ".htmlentities($variant["variantdescr"]) ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel">Status</span>
        <span class="text-element text-pop-databold"><?php print $variant["variantstate"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-pop-datalabel"></span>
      </div>
    </div>
    <div class="rule rule-pop-bomsection">
      <hr>
    </div>
<?php
	if ($showstock === false)
	{
?>
    <div class="container container-pop-bom-alt">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Part Number</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Part</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">UM</span>
      </div>
      <div class="container container-gridhead-el-B1">
        <span class="text-element text-gridhead-column">Ref</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Alt Part</span>
      </div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-bomline.php<?php print "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid) ?>','pop_bomline',600,600)" title="Add new BOM line">New BOM Line</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B1"></div>
      <div class="container container-grid-addline-el-B2"></div>
<?php
		$nd = count($dset);
		for ($i = 0; $i < $nd; $i++)
		{
			if ($i%2 == 0)
				$stline = "evn";
			else
				$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-bomline.php<?php print "?assyid=".$assyid."&variantid=".$variantid."&bomid=".$dset[$i]["bomid"] ?>','pop_bomline',600,600)" title="View/Edit BOM line"><?php print htmlentities($dset[$i]["partnumber"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["um"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["ref"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["altcatdescr"])." ".htmlentities($dset[$i]["altpartdescr"])." ".htmlentities($dset[$i]["altfprint"]) ?></span>
      </div>
<?php
		}
?>
    </div>
    <div class="rule rule-pop-bomsection">
      <hr>
    </div>
<?php
	}
	else
	{
?>
    <div class="container container-pop-bom-stock">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Part Number</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Part</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">UM</span>
      </div>
      <div class="container container-gridhead-el-B1">
        <span class="text-element text-gridhead-column">Ref</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Stock</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Locn</span>
      </div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-bomline.php<?php print "?assyid=".urlencode($assyid)."&variantid=".urlencode($variantid) ?>','pop_bomline',600,600)" title="Add new BOM line">New BOM Line</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B1"></div>
      <div class="container container-grid-addline-el-B2"></div>
      <div class="container container-grid-addline-el-B2"></div>
<?php
		$nd = count($dset);
		for ($i = 0; $i < $nd; $i++)
		{
			if ($i%2 == 0)
				$stline = "evn";
			else
				$stline = "odd";
?>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-bomline.php<?php print "?assyid=".$assyid."&variantid=".$variantid."&bomid=".$dset[$i]["bomid"] ?>','pop_bomline',600,600)" title="View/Edit BOM line"><?php print htmlentities($dset[$i]["partnumber"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["um"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["ref"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["stockqty"]) ?><br></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["stockloc"]) ?></span>
      </div>
<?php
		}
?>
    </div>
    <div class="rule rule-pop-bomsection">
      <hr>
    </div>
<?php
	}
}
?>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>