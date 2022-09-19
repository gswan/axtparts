<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-footprints.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=description

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-footprints.php";
$formname = "footprints";
$formtitle= "Part Footprints";
$rpp = 30;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_PARTS) !== true)
{
	$myparts->AlertMeTo("Insufficient tab privileges.");
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh->connect_error)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$pg = 0;
if (isset($_GET['pg']))
	$pg = trim($_GET["pg"]);
if (!is_numeric($pg))
	$pg = 0;
	
$sc = 0;
if (isset($_GET['sc']))
	$sc = trim($_GET["sc"]);
if (!is_numeric($sc))
	$sc = 0;
	
// Retrieve the states for display
$dset = array();
$q_p = "select fprintid, "
	. "\n fprintdescr "
	. "\n from footprint "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by fprintdescr asc ";
			break;
	default:
			$q_p .= "\n order by fprintdescr asc ";
			break;
}

// Add pagination
$q_p .= "\n limit ".$rpp." offset ".($rpp * $pg);

$s_p = $dbh->query($q_p);
$i = 0;
if ($s_p)
{
	while ($r_p = $s_p->fetch_assoc())
	{
		$dset[$i]["fprintid"] = $r_p["fprintid"];
		$dset[$i]["fprintdescr"] = $r_p["fprintdescr"];
		$q_n = "select partid "
			. "\n from parts "
			. "\n where footprint='".$r_p["fprintid"]."' "
			;
		$s_n = $dbh->query($q_n);
		$dset[$i]["numusing"] = 0;
		if ($s_n)
		{
			$dset[$i]["numusing"] = $s_n->num_rows;
			$s_n->free();
		}
		$i++;
	}
	$s_p->free();
}

// Get total number for page calculations
$q = "select count(*) as nf from footprint";
$s = $dbh->query($q);
if ($s)
{
	$r = $s->fetch_assoc();
	$nf = $r["nf"];
	$s->free();
}
else
	$nf = $i;

$np = intval($nf/$rpp);
if (($nf % $rpp) > 0)
	$np++;

$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Parts";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?sc=".$sc."&pg=".$pg;

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
    <div class="container container-header">
      <div class="container container-head-left">
        <span class="text-element text-head-siteheading">Engineering Parts System</span>
        <span class="text-element text-head-pagetitle">Part Footprints</span>
      </div>
      <div class="container container-head-right">
	    <button type="button" class="btn-logout" onclick="javascript:top.location.href='logout.php'">Logout</button>
        <span class="text-element text-head-user"><?php print $myparts->SessionMeName() ?></span>
      </div>
    </div>
	<?php $myparts->FormRender_Tabs($tabparams); ?>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-contextbuttons-parts">
	  <a class="link-button btn-context" role="button" href="frm-parts.php" title="Parts">Parts</a>
	  <a class="link-button btn-context" role="button" href="frm-components.php" title="Components">Components</a>
	  <a class="link-button btn-context" role="button" href="frm-compstates.php" title="Component states">States</a>
	  <a class="link-button btn-context" role="button" href="frm-categories.php" title="Part categories">Categories</a>
	  <a class="link-button btn-context" role="button" href="frm-datasheets.php" title="Data sheets">Data Sheets</a>
	  <a class="link-button btn-context-active" role="button" href="frm-footprints.php" title="Part footprints">Footprints</a>
	  <a class="link-button btn-context" role="button" href="frm-stock.php" title="Part stock">Stock</a>
	</div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-pagination"><span class="text-element text-pagination-label">Page:</span>
<?php
$urlq = $formfile."?sc=".$sc;
for ($i = 0; $i < $np; $i++)
{
	if ($pg == $i)
		print "<span class=\"text-element text-pagination-num\">".($i+1)."</span>";
	else
		print "<a class=\"link-text link-pagination-num\" href=\"".$urlq."&pg=".$i."\">".($i+1)."</a>";
}
?>
    </div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-gridhead-fprint">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Part Footprint</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Parts Using</span>
      </div>
    </div>
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_FOOTPRINTS))
{
?>
    <div class="container container-grid-addline-fprint">
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-footprint.php','pop_fprint',600,600)">Add Footprint...</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
    </div>
<?php
}

$nd = count($dset);
for ($i = 0; $i < $nd; $i++)
{
	if ($i%2 == 0)
		$stline = "evn";
	else
		$stline = "odd";
?>    
    <div class="container container-grid-data-fprint">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-footprint.php?fprintid=<?php print $dset[$i]["fprintid"] ?>','pop_fprint',600,600)"><?php print htmlentities($dset[$i]["fprintdescr"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["numusing"]) ?></span>
      </div>
    </div>
<?php
}
?>
    <div class="container container-footer">
      <span class="text-element text-footer-copyright"><?php print SYSTEMBRANDING.": ".ENGPARTSVERSION ?></span>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>