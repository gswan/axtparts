<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-admin.php 201 2016-07-17 05:49:39Z gswan $

// Parameters passed: 
// $pg: page number (0-n-1)
// $sc: sort category
//      0=loginID
//      1=name
//      2=status
//      3=lastlogin
//      4=role

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-admin.php";
$formname = "admin";
$formtitle= "User Admin";
$rpp = 30;

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

$sc = 0;
if (isset($_GET['sc']))
	$sc = trim($_GET["sc"]);
if (!is_numeric($sc))
	$sc = 0;

// Retrieve the user table for display
$dset = array();
$q_p = "select * from user "
	. "\n left join role on role.roleid=user.roleid "
	;

// Add sorting
switch ($sc)
{
	case "0":
			$q_p .= "\n order by loginid asc ";
			break;
	case "1":
			$q_p .= "\n order by username asc ";
			break;
	case "2":
			$q_p .= "\n order by status asc ";
			break;
	case "3":
			$q_p .= "\n order by lastlogin desc ";
			break;
	case "4":
			$q_p .= "\n order by rolename asc ";
			break;
	default:
			$q_p .= "\n order by loginid asc ";
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
		$dset[$i]["lastlogin"] = $r_p["lastlogin"];
		if ($r_p["status"] == USERSTATUS_ACTIVE)
			$dset[$i]["status"] = "active";
		else
			$dset[$i]["status"] = "inactive";
		$dset[$i]["rolename"] = $r_p["rolename"];
		$dset[$i]["logincount"] = $r_p["logincount"];
		$i++;
	}
	$s_p->free();
}

// Get total number of users for page calculations
$q = "select count(*) as nu from user";
$s = $dbh->query($q);
if ($s)
{
	$r = $s->fetch_assoc();
	$nu = $r["nu"];
	$s->free();
}
else
	$nu = $i;

$np = intval($nu/$rpp);
if (($nu % $rpp) > 0)
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
        <span class="text-element text-head-pagetitle">User Admin</span>
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
	  <a class="link-button btn-context-active" role="button" href="frm-admin.php" title="User admin">Users</a>
	  <a class="link-button btn-context" role="button" href="frm-logs.php" title="Activity logs">Logs</a>
	  <a class="link-button btn-context" role="button" href="frm-roles.php" title="User roles">Roles</a>
	  <a class="link-button btn-context" role="button" href="frm-system.php" title="System check">System</a>
    </div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-pagination">
	  <span class="text-element text-pagination-label">Page:</span>
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
    <div class="container container-gridhead-user">
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=0&pg=".$pg ?>" title="Sort by login ID">Login ID</a>
      </div>
      <div class="container container-gridhead-el-B0">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=1&pg=".$pg ?>" title="Sort by name">Name</a>
      </div>
      <div class="container container-gridhead-el-B1">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=2&pg=".$pg ?>" title="Sort by status">Status</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=4&pg=".$pg ?>" title="Sort by role">Role</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <a class="link-text link-gridhead-column" href="<?php print $formfile."?sc=3&pg=".$pg ?>" title="Sort by last login">Last Login</a>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Logins</span>
      </div>
    </div>
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_USERADMIN))
{
?>
    <div class="container container-grid-addline-user">
      <div class="container container-grid-addline-el-B0">
        <a class="link-text link-grid-addline" href="javascript:popupOpener('pop-user.php','pop_user',600,600)">Add User...</a>
      </div>
      <div class="container container-grid-addline-el-B0"></div>
      <div class="container container-grid-addline-el-B1"></div>
      <div class="container container-grid-addline-el-B2"></div>
      <div class="container container-grid-addline-el-B2"></div>
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
    <div class="container container-grid-data-user">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="javascript:popupOpener('pop-user.php?uid=<?php print $dset[$i]["uid"] ?>','pop_user',600,600)"><?php print htmlentities($dset[$i]["loginid"]) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["username"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["status"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["rolename"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["lastlogin"]) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dset[$i]["logincount"]) ?></span>
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