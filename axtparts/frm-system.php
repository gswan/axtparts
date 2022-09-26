<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-system.php 202 2016-07-17 06:08:05Z gswan $

// Displays system parameters and settings confirmation
// Parameters passed: 
// none

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-system.php";
$formname = "system";
$formtitle= "System Settings";

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

// Test various system parameters
$dset = array();

if (file_exists(ENGDOC_DIR))
	$dset["engdoc"]["exists"] = true;
else
	$dset["engdoc"]["exists"] = false;

if (is_readable(ENGDOC_DIR) === true)
	$dset["engdoc"]["read"] = true;
else
	$dset["engdoc"]["read"] = false;

if (is_writeable(ENGDOC_DIR) === true)
	$dset["engdoc"]["write"] = true;
else
	$dset["engdoc"]["write"] = false;
	
if (file_exists(MFGDOC_DIR))
	$dset["mfgdoc"]["exists"] = true;
else
	$dset["mfgdoc"]["exists"] = false;

if (is_readable(MFGDOC_DIR) === true)
	$dset["mfgdoc"]["read"] = true;
else
	$dset["mfgdoc"]["read"] = false;

if (is_writeable(MFGDOC_DIR) === true)
	$dset["mfgdoc"]["write"] = true;
else
	$dset["mfgdoc"]["write"] = false;

if (file_exists(DATASHEETS_DIR))
	$dset["datasheet"]["exists"] = true;
else
	$dset["datasheet"]["exists"] = false;

if (is_readable(DATASHEETS_DIR) === true)
	$dset["datasheet"]["read"] = true;
else
	$dset["datasheet"]["read"] = false;

if (is_writeable(DATASHEETS_DIR) === true)
	$dset["datasheet"]["write"] = true;
else
	$dset["datasheet"]["write"] = false;

if (file_exists(SWIMAGE_DIR))
	$dset["swimage"]["exists"] = true;
else
	$dset["swimage"]["exists"] = false;

if (is_readable(SWIMAGE_DIR) === true)
	$dset["swimage"]["read"] = true;
else
	$dset["swimage"]["read"] = false;

if (is_writeable(SWIMAGE_DIR) === true)
	$dset["swimage"]["write"] = true;
else
	$dset["swimage"]["write"] = false;

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if (!$dbh->connect_error)
	$dset["db"]["access"] = true;
else
	$dset["db"]["access"] = false;
$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Admin";
$tabparams["tabs"] = $_cfg_tabs;


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
        <span class="text-element text-head-pagetitle">System Settings</span>
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
	  <a class="link-button btn-context" role="button" href="frm-logs.php" title="Activity logs">Logs</a>
	  <a class="link-button btn-context" role="button" href="frm-roles.php" title="User roles">Roles</a>
	  <a class="link-button btn-context-active" role="button" href="frm-system.php" title="System check">System</a>
	</div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-gridhead-sys">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Parameter</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Tests</span>
      </div>
    </div>
    <div class="container container-grid-data-sys">
      <div class="container container-grid-dataitem-B0-odd">
        <span class="text-element text-grid-dataitem"><?php print "Directory: ".ENGDOC_DIR ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-odd">
<?php 
if ($dset["engdoc"]["exists"] === true)
	print "<span class=\"text-element text-grid-testpass\">Exists [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Exists [FAIL]</span>";
		
if ($dset["engdoc"]["read"] === true)
	print "<span class=\"text-element text-grid-testpass\">Readable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Readable [FAIL]</span>";

if ($dset["engdoc"]["write"] === true)
	print "<span class=\"text-element text-grid-testpass\">Writeable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Writeable [FAIL]</span>";
?>
      </div>
    </div>
    <div class="container container-grid-data-sys">
      <div class="container container-grid-dataitem-B0-odd">
        <span class="text-element text-grid-dataitem"><?php print "Directory: ".MFGDOC_DIR ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-odd">
<?php 
if ($dset["mfgdoc"]["exists"] === true)
	print "<span class=\"text-element text-grid-testpass\">Exists [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Exists [FAIL]</span>";
		
if ($dset["mfgdoc"]["read"] === true)
	print "<span class=\"text-element text-grid-testpass\">Readable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Readable [FAIL]</span>";

if ($dset["mfgdoc"]["write"] === true)
	print "<span class=\"text-element text-grid-testpass\">Writeable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Writeable [FAIL]</span>";
?>
      </div>
    </div>
    <div class="container container-grid-data-sys">
      <div class="container container-grid-dataitem-B0-evn">
        <span class="text-element text-grid-dataitem"><?php print "Directory: ".DATASHEETS_DIR ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-evn">
<?php 
if ($dset["datasheet"]["exists"] === true)
	print "<span class=\"text-element text-grid-testpass\">Exists [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Exists [FAIL]</span>";
		
if ($dset["datasheet"]["read"] === true)
	print "<span class=\"text-element text-grid-testpass\">Readable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Readable [FAIL]</span>";

if ($dset["datasheet"]["write"] === true)
	print "<span class=\"text-element text-grid-testpass\">Writeable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Writeable [FAIL]</span>";
?>
      </div>
    </div>
    <div class="container container-grid-data-sys">
      <div class="container container-grid-dataitem-B0-odd">
        <span class="text-element text-grid-dataitem"><?php print "Directory: ".SWIMAGE_DIR ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-odd">
<?php 
if ($dset["swimage"]["exists"] === true)
	print "<span class=\"text-element text-grid-testpass\">Exists [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Exists [FAIL]</span>";
		
if ($dset["swimage"]["read"] === true)
	print "<span class=\"text-element text-grid-testpass\">Readable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Readable [FAIL]</span>";

if ($dset["swimage"]["write"] === true)
	print "<span class=\"text-element text-grid-testpass\">Writeable [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Writeable [FAIL]</span>";
?>
      </div>
    </div>   
    <div class="container container-grid-data-sys">
      <div class="container container-grid-dataitem-B0-evn">
        <span class="text-element text-grid-dataitem"><?php print "Database: ".PARTSDBASE ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-evn">
<?php 
if ($dset["db"]["access"] === true)
	print "<span class=\"text-element text-grid-testpass\">Access [OK]</span>";
else
	print "<span class=\"text-element text-grid-testfail\">Access [FAIL]</span>";
?>
      </div>
    </div>
    <div class="container container-footer">
      <span class="text-element text-footer-copyright"><?php print SYSTEMBRANDING.": ".ENGPARTSVERSION ?></span>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>