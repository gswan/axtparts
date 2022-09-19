<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-locncontents.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: 0 = location
//      1 = locref
// $locid: location ID

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-locncontents.php";
$formname = "poplocncontents";
$formtitle= "Show Location Contents";
$rpp = 30;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
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
	
$locid = false;
if (isset($_GET['locid']))
	$locid = trim($_GET["locid"]);
if (!is_numeric($locid))
	$locid = false;

if ($locid === false)
{
	$myparts->AlertMeTo("Require a location.");
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

$dset = array();
$q_l = "select locid, "
	. "\n locdescr, "
	. "\n locref "
	. "\n from locn "
	. "\n where locid='".$dbh->real_escape_string($locid)."' "
	;
$s_l = $dbh->query($q_l);
if ($s_l)
{
	$r_l = $s_l->fetch_assoc();
	$locref = $r_l["locref"];
	$locdescr = $r_l["locdescr"];
	$s_l->free();
}

$q_p = "select * "
	. "\n  from stock "
	. "\n left join parts on parts.partid=stock.partid "
	. "\n left join pgroups on pgroups.partcatid=parts.partcatid "
	. "\n where locid='".$dbh->real_escape_string($locid)."' "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by partnumber asc ";
			break;
	case "1":
			$q_p .= "\n order by catdescr asc, partdescr asc ";
			break;
	default:
			$q_p .= "\n order by catdescr asc, partdesc asc ";
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
		$dset[$i]["stockid"] = $r_p["stockid"];
		$dset[$i]["partid"] = $r_p["partid"];
		$dset[$i]["partnumber"] = $r_p["partnumber"];
		$dset[$i]["partdescr"] = $r_p["partdescr"];
		$dset[$i]["catdescr"] = $r_p["catdescr"];
		$dset[$i]["qty"] = $r_p["qty"];
		$i++;
	}
	$s_p->free();
}

// Get total number for page calculations
$q = "select count(*) as ne from stock where locid='".$dbh->real_escape_string($locid)."' ";
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
    <span class="text-element text-poptitle"><?php print htmlentities($locref)." (".htmlentities($locdescr).") Contents" ?></span>
    <div class="container container-pagination"><span class="text-element text-pagination-label">Page: </span>
<?php
$urlq = $formfile."?sc=".$sc."&locid=".$locid;
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
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg ?>" title="Sort by part number">Part Number</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=1&pg=".urlencode($pg) ?>" title="Sort by part descritpion">Description</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Qty</span>
      </div>
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
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-part.php?partid=<?php print $dset[$i]["partid"] ?>" title="Edit/view part detail"><?php print htmlentities($dset[$i]["partnumber"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["catdescr"])." ".htmlentities($dset[$i]["partdescr"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["qty"]) ?></span>
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