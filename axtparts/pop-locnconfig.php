<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locnconfig.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: 0 = location
//      1 = locref

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-locnconfig.php";
$formname = "poplocnconfig";
$formtitle= "Configure Part Locations";
$rpp = 30;

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
	
$dset = array();
$q_p = "select locid, "
	. "\n locref, "
	. "\n locdescr "
	. "\n from locn "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by locdescr asc ";
			break;
	case "1":
			$q_p .= "\n order by locref asc ";
			break;
	default:
			$q_p .= "\n order by locdescr asc ";
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
		$dset[$i]["locid"] = $r_p["locid"];
		$dset[$i]["locref"] = $r_p["locref"];
		$dset[$i]["locdescr"] = $r_p["locdescr"];
		$q_n = "select partid "
			. "\n from stock "
			. "\n where locid='".$r_p["locid"]."' "
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
$q = "select count(*) as ne from locn";
$s = $dbh->query($q);
if ($s)
{
	$r = $s->fetch_assoc();
	$ne = $r["ne"];
	$s->free();
}
else
	$ne = $i;
	
$np = intval($ne/$rpp);
if (($ne % $rpp) > 0)
	$np++;

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
    <span class="text-element text-poptitle">Stock Locations</span>
    <div class="container container-pagination"><span class="text-element text-pagination-label">Page: </span>
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
    <div class="container container-pop-locnconfig">
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg ?>" title="Sort by location">Location</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=1&pg=".$pg ?>" title="Sort by location Reference">LocRef</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Using</span>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-locn.php','pop_locn',600,600)">Add...</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
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
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["locdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-locn.php?locid=<?php print $dset[$i]["locid"] ?>','pop_locn',600,600)"><?php print htmlentities($dset[$i]["locref"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
<?php
	if ($dset[$i]["numusing"] > 0)
	{
?>
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-locncontents.php?locid=<?php print $dset[$i]["locid"] ?>','pop_locncontents',600,600)"><?php print htmlentities($dset[$i]["numusing"]) ?></a>
<?php
	}
	else
	{
?>
		<span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["numusing"]) ?></span>
<?php
	}
?>
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