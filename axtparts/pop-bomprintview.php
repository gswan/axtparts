<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomprintview.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $assyid: ID of assembly to edit BOM
// $variantid: ID of the assembly variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-bomprintview.php";
$formname = "popbomprintview";
$formtitle= "Printable View of Assembly BOM";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$assyid = false;
if (isset($_GET['assyid']))
	$assyid = trim($_GET["assyid"]);
if (!is_numeric($assyid))
	$assyid = false;

if ($assyid === false)
{
		$myparts->AlertMeTo("An assembly must be specified.");
		$myparts->PopMeClose();
		die();
}

$variantid = false;
if (isset($_GET['variantid']))
	$variantid = trim($_GET["variantid"]);
if (!is_numeric($variantid))
	$variantid = false;
		
if ($variantid === false)
{
	$myparts->AlertMeTo("A variant must be specified.");
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

$showstock = false;
if (isset($_GET['showstock']))
	$showstock = trim($_GET["showstock"]);
if (!is_numeric($showstock))
	$showstock = false;
	
if ($assyid !== false)
{
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
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
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
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
				if ($s_p)
				{
					$r_p = $s_p->fetch_assoc();
				
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
					$s_p->free();
				}
			}
			$i++;
		}
		$s_b->free();
	}
}

$dbh->close();

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
    <span class="text-element text-bomprint-title">Bill of Materials</span>
    <div class="container container-print-bomhead">
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel">Assembly</span>
        <span class="text-element text-print-databold"><?php print $assy["assyname"]." ".$assy["assydescr"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel">Revision</span>
        <span class="text-element text-print-databold"><?php print $assy["assyrev"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel">Artwork</span>
        <span class="text-element text-print-databold"><?php print $assy["assyaw"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel">Variant</span>
        <span class="text-element text-print-databold"><?php print $variant["variantname"]." ".$variant["variantdescr"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel">Status</span>
        <span class="text-element text-print-databold"><?php print $variant["variantstate"] ?></span>
      </div>
      <div class="container container-pop-el">
        <span class="text-element text-print-datalabel"></span>
      </div>
    </div>
<?php
if ($showstock === false)
{
?>
    <div class="container container-print-bom-alt">
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Part Number</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Part</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Qty</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">UM</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Ref</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Alt Part</span>
      </div>
<?php
	$nd = count($dset);
	for ($i = 0; $i < $nd; $i++)
	{
?>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["partnumber"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["um"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["ref"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["altcatdescr"])." ".htmlentities($dset[$i]["altpartdescr"])." ".htmlentities($dset[$i]["altfprint"]) ?></span>
      </div>
<?php
	}
?>
    </div>
<?php
}
else
{
?>
    <div class="container container-print-bom-stock">
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Part Number</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Part</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Qty</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">UM</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Ref</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Stock</span>
      </div>
      <div class="container container-print-gridhead-el">
        <span class="text-element text-print-colhead">Locn</span>
      </div>
<?php
	$nd = count($dset);
	for ($i = 0; $i < $nd; $i++)
	{
?>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["partnumber"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"])." ".htmlentities($dset[$i]["footprint"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["um"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["ref"]) ?></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["stockqty"]) ?><br></span>
      </div>
      <div class="container container-print-griddata">
        <span class="text-element text-print-dataitem"><?php print htmlentities($dset[$i]["stockloc"]) ?></span>
      </div>
<?php
	}
?>
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