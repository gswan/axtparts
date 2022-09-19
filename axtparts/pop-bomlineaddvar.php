<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-bomlineaddvar.php 202 2016-07-17 06:08:05Z gswan $

// Parameters passed: 
// $assyid: ID of assembly
// $variantid: ID of variant
// $bomid: ID of bomline to edit, none to add a new bomline to an assy/variant

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-bomline.php";
$formname = "popbomline";
$formtitle= "Add/Edit BOM Item";

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

$bomid = false;
if (isset($_GET['bomid']))
	$bomid = trim($_GET["bomid"]);
if (!is_numeric($bomid))
	$bomid = false;
	
if (($assyid === false) || ($bomid === false))
{
	$myparts->AlertMeTo("A bom line and assembly must be specified.");
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

$urlargs = "?assyid=".$assyid."&bomid=".$bomid;

// Handle part form submission here
if (isset($_POST["btn_add"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_BOMVARIANT) === true)
	{
		if (isset($_POST["sel-variantid"]))
		{
			$variantid = trim($_POST["sel-variantid"]);
			if ($variantid == "")
				$variantid = false;
		}
		else 
			$variantid = false;

		if ($variantid === false)
			$myparts->AlertMeTo("A variant must be specified.");
		else 
		{
			$q_p = "insert into bomvariants "
				. "\n set "
				. "\n variantid='".$dbh->real_escape_string($variantid)."', "
				. "\n bomid='".$dbh->real_escape_string($bomid)."' "
				;
			$s_p = $dbh->query($q_p);
			if (!$s_p)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			else 
				$myparts->UpdateParent();
		}
	}
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
    <span class="text-element text-poptitle">Add Selected BOM Item to Variant BOM</span>
    <form class="form-container form-pop-bomaddvar" name="form-bomaddvar" id="form-bomaddvar" action="<?php print $url ?>" method="post">
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-bomaddvar" for="sel-variantid">Select Variant BOM</label>
		<select name="sel-variantid" class="select sel-formitem" form="form-bomaddvar">
          <?php $myparts->RenderOptionList($list_var, false, false); ?>
        </select>
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-bomaddvar" formaction="<?php print $url ?>" value="Add" name="btn_add" id="btn_add">Add</button>
	  </div>
    </form>
    <div class="rule rule-popsection">
      <hr>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>