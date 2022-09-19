<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-stock.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=part number
//      1=category
//      2=description
//      3=location
//		4=loc ref
// $fc: filter category

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-stock.php";
$formname = "stock";
$formtitle= "Stock Sheet";
$rpp = 30;
$var_fc = "filter_fc";

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
	
$sc = 1;
if (isset($_GET['sc']))
	$sc = trim($_GET["sc"]);
if (!is_numeric($sc))
	$sc = 1;
	
$fc = false;
if (isset($_GET['fc']))
	$fc = trim($_GET["fc"]);
	
if (isset($_POST["btn_filter"]))
{
	if (isset($_POST["sel-partcat"]))
	{
		$fc = trim($_POST["sel-partcat"]);
		$myparts->SessionVarSave($var_fc, $fc);
	}
	$urlq = "?sc=".$sc."&fc=".$fc."&pg=".$pg;
	print "<script type=\"text/javascript\">top.location.href='".$formfile.$urlq."'</script>\n";
}

// If false, then read out the last value saved.
// Otherwise save the selected value
if ($fc === false)
{
	$f = $myparts->SessionVarRead($var_fc);
	if ($f === false)
	{
		$fc = "";
		$myparts->SessionVarSave($var_fc, $fc);
	}
	else 
		$fc = $f;
}
else 
	$myparts->SessionVarSave($var_fc, $fc);

// Retrieve the stock for display
$dset = array();
$q_p = "select * from stock "
	. "\n left join locn on locn.locid=stock.locid "
	. "\n left join parts on parts.partid=stock.partid "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n where qty>0 "
	;

// filter by category if selected
if (($fc !== false) && ($fc != ""))
	$q_p .= "\n and parts.partcatid='".$dbh->real_escape_string($fc)."' ";
	
// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by partnumber asc ";
			break;
	case "1":
			$q_p .= "\n order by catdescr asc ";
			break;
	case "2":
			$q_p .= "\n order by partdescr asc ";
			break;
	case "3":
			$q_p .= "\n order by locdescr asc ";
			break;
	case "4":
			$q_p .= "\n order by locref asc ";
			break;
	default:
			$q_p .= "\n order by partnumber asc ";
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
		$dset[$i]["partid"] = $r_p["partid"];
		$dset[$i]["partnumber"] = $r_p["partnumber"];
		$dset[$i]["catid"] = $r_p["partcatid"];
		$dset[$i]["category"] = $r_p["catdescr"];
		$dset[$i]["partdescr"] = $r_p["partdescr"];
		$dset[$i]["stockid"] = $r_p["stockid"];
		$dset[$i]["locdescr"] = $r_p["locdescr"];
		$dset[$i]["locref"] = $r_p["locref"];
		$dset[$i]["locid"] = $r_p["locid"];
		$dset[$i]["qty"] = $r_p["qty"];
			
		$i++;
	}
	$s_p->free();
}

// Get count for pagination
$q = "select count(*) as nx from stock "
	. "\n left join parts on parts.partid=stock.partid "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n where qty>0 "
	;
if (($fc !== false) && ($fc != ""))
	$q .= "\n and parts.partcatid='".$dbh->real_escape_string($fc)."' ";

$s = $dbh->query($q);
if ($s)
{
	$r = $s->fetch_assoc();
	$nx = $r["nx"];
	$s->free();
}
else
	$nx = $i;

$np = intval($nx/$rpp);
if (($nx % $rpp) > 0)
	$np++;

// Get a list of categories for the filter
$q_partcat = "select partcatid, "
		. "\n catdescr "
		. "\n from pgroups "
		. "\n order by catdescr "
		;
		
$s_partcat = $dbh->query($q_partcat);
$list_partcat = array();
$list_partcat[0][0] = "";
$list_partcat[0][1] = "All";
$i = 1;
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

$tabparams = array();
$tabparams["tabon"] = "Parts";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?sc=".$sc."&pg=".$pg;
if (($fc !== false) && ($fc != ""))
	$url .= "&fc=".$fc;
	
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
        <span class="text-element text-head-pagetitle">Stock Sheet</span>
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
	  <a class="link-button btn-context" role="button" href="frm-footprints.php" title="Part footprints">Footprints</a>
	  <a class="link-button btn-context-active" role="button" href="frm-stock.php" title="Part stock">Stock</a>
 	</div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-pagination"><span class="text-element text-pagination-label">Page:</span>
<?php
$urlq = $formfile."?sc=".$sc;
if (($fc !== false) && ($fc != ""))
	$urlq .= "&fc=".$fc;
	
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
    <form class="form-container form-filterline-stock" name="form-filter" id="form-filter" action="<?php print $url ?>" method="post">
      <div class="container container-filtercell">
	    <button type="submit" class="btn-filterline" form="form-filter" name="btn_filter" value="Filter" formaction="<?php print $url ?>">Apply&nbsp;Filter</button>
	  </div>
      <div class="container container-filtercell">
	    <select name="sel-partcat" class="select sel-filterline" form="form-filter">
          <?php $myparts->RenderOptionList($list_partcat, $fc, false); ?>
        </select></div>
    </form>
    <div class="container container-gridhead-stock">
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg."&fc=".$fc ?>">Part Number</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=2&pg=".$pg."&fc=".$fc ?>">Description</a>
      </div>
      <div class="container container-gridhead-el-B1">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=3&pg=".$pg."&fc=".$fc ?>">Loc Ref</a>
      </div>
      <div class="container container-gridhead-el-B1">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=4&pg=".$pg."&fc=".$fc ?>">Location</a>
      </div>
    </div>
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS) || $myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN))
{
?>
    <div class="container container-grid-addline-stock">
      <div class="container container-grid-addline-el-B0">
<?php
	if ($myparts->SessionMePrivilegeBit(UPRIV_PARTS))
	{
?>
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-part.php<?php print ($fc === false ? "" : "?fc=".$fc) ?>','pop_part',600,900)">Add Part...</a>
<?php
	}
?>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B1">
<?php
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN))
	{
?>
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-locn.php','pop_fprint',600,600)">Add Loc Ref...</a>
<?php
	}
?>
      </div>
      <div class="container container-grid-addline-el-B1"></div>
      <div class="container container-grid-addline-el-B2"></div>
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
    <div class="container container-grid-data-stock">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-part.php?partid=<?php print $dset[$i]["partid"].($fc === false ? "" : "&fc=".$fc) ?>','pop_part',600,900)"><?php print htmlentities($dset[$i]["partnumber"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["partdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-locn.php?locid=<?php print $dset[$i]["locid"] ?>','pop_locn',600,600)"><?php print htmlentities($dset[$i]["locref"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["locdescr"]) ?></span>
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