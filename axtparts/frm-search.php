<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-search.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=part number
//      1=category
//      2=description
//      3=footprint
// $st: search text

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-search.php";
$formname = "search";
$formtitle= "Part & Component Search";
$rpp = 30;
$var_search = "vsearch";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_SEARCH) !== true)
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
	
$st = false;
if (isset($_GET['st']))
	$st = trim(urldecode($_GET["st"]));
	
if (isset($_POST["btn_search"]))
{
	if (isset($_POST["searchtext"]))
	{
		$st = trim($_POST["searchtext"]);
		$myparts->SessionVarSave($var_search, $st);
	}
	$urlq = "?sc=".$sc."&st=".urlencode($st)."&pg=".$pg;
	print "<script type=\"text/javascript\">top.location.href='".$formfile.$urlq."'</script>\n";
}

// If false, then read out the last value saved.
// Otherwise save the selected value
if ($st === false)
{
	$f = $myparts->SessionVarRead($var_search);
	if ($f === false)
	{
		$st = "";
		$myparts->SessionVarSave($var_search, $st);
	}
	else 
		$st = $f;
}
else 
	$myparts->SessionVarSave($var_search, $st);
	
$sr = array();
$n = 0;

if ($st != "")
{
	// Look for parts
	$q_search = "select * from parts "
			. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
			. "\n left join footprint on footprint.fprintid=parts.footprint "
			. "\n where partdescr like '%".$dbh->real_escape_string($st)."%' "
			;
			
	// Add sorting
	switch ($sc)
	{
		case "0":
				$q_search .= "\n order by partnumber asc ";
				break;
		case "1":
				$q_search .= "\n order by catdescr asc ";
				break;
		case "2":
				$q_search .= "\n order by partdescr asc ";
				break;
		case "3":
				$q_search .= "\n order by fprintdescr asc ";
				break;
		default:
				$q_search .= "\n order by partdescr asc ";
				break;
	}
	
	// Add pagination
	$q_search .= "\n limit ".$rpp." offset ".($rpp * $pg);
	
	$s_search = $dbh->query($q_search);
	if ($s_search)
	{
		while ($r_search = $s_search->fetch_assoc())
		{
			$sr[$n]["partid"] = $r_search["partid"];
			$sr[$n]["partnumber"] = $r_search["partnumber"];
			$sr[$n]["catdescr"] = $r_search["catdescr"];
			$sr[$n]["partdescr"] = $r_search["partdescr"];
			$sr[$n]["fprintdescr"] = $r_search["fprintdescr"];

			// Count the stock
			$q_s = "select sum(qty) as totalqty "
				. "\n from stock "
				. "\n where partid='".$dbh->real_escape_string($r_search["partid"])."' "
				;

			$s_s = $dbh->query($q_s);	
			$sr[$n]["stockqty"] = 0;
			if ($s_s)
			{
				$r_s = $s_s->fetch_assoc();
				if (isset($r_s["totalqty"]))
					$sr[$n]["stockqty"] = $r_s["totalqty"];
				$s_s->free();
			}
					
			$n++;
		}
		$s_search->free();
	}
	
	// Create a set of partid's already found
	$pidlist = array();
	for ($i = 0; $i < $n; $i++)
		$pidlist[$i] = $sr[$i]["partid"];
	
	// Look for components which have not already been found in parts
	$q_search = "select * from components "
			. "\n left join parts on parts.partid=components.partid "
			. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
			. "\n left join footprint on footprint.fprintid=parts.footprint "
			. "\n where mfgcode like '%".$dbh->real_escape_string($st)."%' "
			;
	
	// Add sorting
	switch ($sc)
	{
		case "0":
				$q_search .= "\n order by partnumber asc ";
				break;
		case "1":
				$q_search .= "\n order by catdescr asc ";
				break;
		case "2":
				$q_search .= "\n order by partdescr asc ";
				break;
		case "3":
				$q_search .= "\n order by fprintdescr asc ";
				break;
		case "4":
				$q_search .= "\n order by mfgcode asc ";
				break;
		default:
				$q_search .= "\n order by mfgcode asc ";
				break;
	}
	
	// Add pagination
	$q_search .= "\n limit ".$rpp." offset ".($rpp * $pg);
	
	$s_search = $dbh->query($q_search);
	if ($s_search)
	{
		while ($r_search = $s_search->fetch_assoc())
		{
			if (!in_array($r_search["partid"], $pidlist))
			{
				$sr[$n]["partid"] = $r_search["partid"];
				$sr[$n]["compid"] = $r_search["compid"];
				$sr[$n]["partnumber"] = $r_search["partnumber"];
				$sr[$n]["catdescr"] = $r_search["catdescr"];
				$sr[$n]["partdescr"] = $r_search["partdescr"];
				$sr[$n]["mfgcode"] = $r_search["mfgcode"];
				$sr[$n]["fprintdescr"] = $r_search["fprintdescr"];
	
				// Count the stock
				$q_s = "select sum(qty) as totalqty "
					. "\n from stock "
					. "\n where partid='".$dbh->real_escape_string($r_search["partid"])."' "
					;
				$s_s = $dbh->query($q_s);	
				$sr[$n]["stockqty"] = 0;
				if ($s_s)
				{
					$r_s = $s_s->fetch_assoc();
					if (isset($r_s["totalqty"]))
						$sr[$n]["stockqty"] = $r_s["totalqty"];
					$s_s->free();
				}
					
				$n++;
			}
		}
		$s_search->free();
	}
}

if ($st != "")
{
	// Calculate total rows from both searches
	$nt = $n;
	$q = "select count(*) as nx from parts where partdescr like '%".$dbh->real_escape_string($st)."%' ";
	$s = $dbh->query($q);
	if ($s)
	{
		$r = $s->fetch_assoc();
		$nt = $r["nx"];
		$s->free();
	}
	
	$q = "select count(*) as ny from components where mfgcode like '%".$dbh->real_escape_string($st)."%' ";
	$s = $dbh->query($q);
	if ($s)
	{
		$r = $s->fetch_assoc();
		$nt += $r["ny"];
		$s->free();
	}
}
else
	$nt = 0;

$np = intval($nt/$rpp);
if (($nt % $rpp) > 0)
	$np++;

$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Search";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?sc=".$sc."&pg=".$pg;
if ($st != "")
	$url .= "&st=".urlencode($st);

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
        <span class="text-element text-head-pagetitle">Part &amp; Component Search</span>
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
    <span class="text-element text-formtitle">Search for Parts</span>
    <form class="form-container form-search" name="form-search" id="form-search" action="<?php print $formfile ?>" method="post">
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-search" for="searchtext">Part or Component</label>
		<input value="<?php print htmlentities($st) ?>" name="searchtext" type="text" class="input-formelement" form="form-search">
	  </div>
      <div class="container container-form-element">
	    <button type="submit" class="btn-search" form="form-search" formaction="<?php print $formfile ?>" value="Search" name="btn_search" id="btn_search">Search</button>
	  </div>
    </form>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-pagination"><span class="text-element text-pagination-label">Page:</span>
<?php
$urlq = $formfile."?sc=".$sc."&st=".urlencode($st);
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
    <div class="container container-gridhead-parts">
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg."&st=".urlencode($st) ?>">Part Number</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=2&pg=".$pg."&st=".urlencode($st) ?>">Description</a>
      </div>
      <div class="container container-gridhead-el-B1">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=1&pg=".$pg."&st=".urlencode($st) ?>">Category</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=3&pg=".$pg."&st=".urlencode($st) ?>">Footprint</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Stock</span>
      </div>
    </div>
<?php 
$nd = count($sr);
for ($i = 0; $i < $nd; $i++)
{
	if ($i%2 == 0)
		$stline = "evn";
	else
		$stline = "odd";
?>
    <div class="container container-grid-data-parts">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-part.php?partid=<?php print $sr[$i]["partid"].($st == "" ? "" : "&st=".urlencode($st)) ?>','pop_part',600,900)"><?php print htmlentities($sr[$i]["partnumber"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($sr[$i]["partdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($sr[$i]["catdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($sr[$i]["fprintdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($sr[$i]["stockqty"]) ?></span>
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