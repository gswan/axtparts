<?php
// ********************************************
// Copyright 2003-2016 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-logs.php 203 2016-07-17 06:16:46Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=logdate
//      1=user
//      2=logtype

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-logs.php";
$formname = "logs";
$formtitle= "Event Logs";
$rpp = 40;

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ADMIN) !== true)
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
	
$sc = 2;
if (isset($_GET['sc']))
	$sc = trim($_GET["sc"]);
if (!is_numeric($sc))
	$sc = 2;
	
// Retrieve the log table for display
$dset = array();
$q_p = "select * from log "
	. "\n left join user on user.uid=log.uid "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by loginid asc, logdate desc ";
			break;
	case "1":
			$q_p .= "\n order by username asc, logdate desc ";
			break;
	case "2":
			$q_p .= "\n order by logdate desc, loginid asc ";
			break;
	case "3":
			$q_p .= "\n order by logmsg asc, logdate desc, loginid asc ";
			break;
	default:
			$q_p .= "\n order by logdate desc, loginid asc ";
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
		$dset[$i]["uid"] = $r_p["uid"];
		$dset[$i]["loginid"] = $r_p["loginid"];
		$dset[$i]["username"] = $r_p["username"];
		$dset[$i]["logtype"] = $r_p["logtype"];
		$dset[$i]["logmsg"] = $r_p["logmsg"];
		$dset[$i]["logdate"] = $r_p["logdate"];
		$i++;
	}
	$s_p->free();
}

// Get total number for page calculations
$q = "select count(*) as ne from log";
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

$tabparams = array();
$tabparams["tabon"] = "Admin";
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
        <span class="text-element text-head-siteheading"><?php print SYSTEMHEADING ?></span>
        <span class="text-element text-head-pagetitle">Event Logs</span>
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
    <div class="container container-contextbuttons-admin">
	  <a class="link-button btn-context" role="button" href="frm-admin.php" title="User admin">Users</a>
	  <a class="link-button btn-context-active" role="button" href="frm-logs.php" title="Activity logs">Logs</a>
	  <a class="link-button btn-context" role="button" href="frm-roles.php" title="User roles">Roles</a>
	  <a class="link-button btn-context" role="button" href="frm-system.php" title="System check">System</a>
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
    <div class="container container-gridhead-logs">
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg ?>">Login ID</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=1&pg=".$pg ?>">Name</a>
      </div>
      <div class="container container-gridhead-el-B1">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=2&pg=".$pg ?>">Log Date</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=3&pg=".$pg ?>">Log Message</a>
      </div>
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
    <div class="container container-grid-data-logs">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["loginid"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["username"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["logdate"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php  print htmlentities($dset[$i]["logmsg"]) ?></span>
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