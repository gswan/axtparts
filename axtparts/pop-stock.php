<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: pop-stock.php 206 2017-01-16 07:49:49Z gswan $

// Parameters passed: 
// $partid: The part associated with this stock line
// $stockid: The stockid entry to edit. Create a new stock line if this is not present.

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "pop-stock.php";
$formname = "popstock";
$formtitle= "Add/Edit Stock Item";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->PopMeClose();
	die();
}

$partid = false;
if (isset($_GET['partid']))
	$partid = trim($_GET["partid"]);
if (!is_numeric($partid))
	$partid = false;
	
$stockid = false;
if (isset($_GET['stockid']))
	$stockid = trim($_GET["stockid"]);
if (!is_numeric($stockid))
	$stockid = false;

if ($partid === false)
{
	$myparts->AlertMeTo("A part must be specified.");
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

// Handle form submission here
if (isset($_POST["btn_save"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCKLOCN) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		if (isset($_POST["qty"]))
			$qty = trim($_POST["qty"]);
		else 
			$qty = 0;
		if (isset($_POST["note"]))
			$note = trim($_POST["note"]);
		else 
			$note = "";
		if (isset($_POST["sel-locid"]))
			$locid = trim($_POST["sel-locid"]);
		else 
			$locid = 0;
		
		if ($locid == 0)
			$myparts->AlertMeTo("Require a location.");
		else 
		{
			if ($stockid === false)
			{
				// new stock item - insert the values
				$q_p = "insert into stock "
					. "\n set "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n note='".$dbh->real_escape_string($note)."', "
					. "\n locid='".$dbh->real_escape_string($locid)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else 
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Stock location ".$locid." assigned to ".$partid;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNASSIGN, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
			else 
			{
				// existing - update the values
				$q_p = "update stock "
					. "\n set "
					. "\n qty='".$dbh->real_escape_string($qty)."', "
					. "\n note='".$dbh->real_escape_string($note)."', "
					. "\n locid='".$dbh->real_escape_string($locid)."', "
					. "\n partid='".$dbh->real_escape_string($partid)."' "
					. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
					;
				$s_p = $dbh->query($q_p);
				if (!$s_p)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				else
				{
					$uid = $myparts->SessionMeUID();
					$logmsg = "Stock location ".$locid." updated for ".$partid;
					$myparts->LogSave($dbh, LOGTYPE_PARTLOCNASSIGN, $uid, $logmsg);
					$myparts->UpdateParent();
				}
				$dbh->close();
				$myparts->PopMeClose();
				die();
			}
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_STOCK) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else
	{
		$q_p = "delete from stock "
			. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
			. "\n limit 1 "
			;
		$s_p = $dbh->query($q_p);
		if (!$s_p)
			$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		else
		{
			$uid = $myparts->SessionMeUID();
			$logmsg = "Stock deleted: ".$stockid;
			$myparts->LogSave($dbh, LOGTYPE_PARTLOCNUNASSIGN, $uid, $logmsg);
			$myparts->AlertMeTo("Stock deleted.");
			$myparts->UpdateParent();
		}
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
}

// Get the locations for the list
$q_loc = "select locid, "
		. "\n locref, "
		. "\n locdescr "
		. "\n from locn "
		. "\n order by locref "
		;
		
$s_loc = $dbh->query($q_loc);
$list_locn = array();
$i = 0;
if ($s_loc)
{
	while ($r_loc = $s_loc->fetch_assoc())
	{
		$list_locn[$i][0] = $r_loc["locid"];
		$list_locn[$i][1] = $r_loc["locref"]." (".$r_loc["locdescr"].")";
		$i++;
	}
	$s_loc->free();
}

if ($stockid !== false)
{
	$q_stk = "select stockid, "
		. "\n qty, "
		. "\n note, "
		. "\n locid, "
		. "\n partid "
		. "\n from stock "
		. "\n where stockid='".$dbh->real_escape_string($stockid)."' "
		;
	$s_stk = $dbh->query($q_stk);

	if (!$s_stk)
	{
		$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
		$dbh->close();
		$myparts->PopMeClose();
		die();
	}
	else
	{
		$r_stk = $s_stk->fetch_assoc();
		$locid = $r_stk["locid"];
		$qty = $r_stk["qty"];
		$note = $r_stk["note"];
		$s_stk->free();
	}
}
else
{
	$locid = 0;
	$qty = 0;
	$note = "";
}

$urlargs = "?partid=".$partid;

if ($stockid !== false)
	$urlargs .= "&stockid=".$stockid;

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
    <span class="text-element text-poptitle"><?php print ($stockid === false ? "Add New Stock Detail" : "Edit Stock Detail") ?></span>
    <form class="form-container form-pop-stock" name="form-stock" id="form-stock" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-stock" for="qty">Quantity</label>
		<input value="<?php print htmlentities($qty) ?>" name="qty" type="text" class="input-formelement" form="form-stock" maxlength="8" title="Stock quantity">
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-stock" for="sel-locid">Location</label>
		<select name="sel-locid" class="select sel-formitem" form="form-stock">
          <?php $myparts->RenderOptionList($list_locn, $locid, false); ?>
        </select>
	  </div>
      <div class="container container-pop-el">
	    <label class="label label-formitem" form="form-stock" for="note">Note</label>
		<input value="<?php print htmlentities($note) ?>" name="note" type="text" class="input-formelement" form="form-stock" maxlength="64" title="Notes">
	  </div>
      <div class="container container-pop-btn">
	    <button type="submit" class="btn-pop-update" form="form-stock" formaction="<?php print $url ?>" value="Save" name="btn_save" id="btn_save" onclick="delClear()">Save</button>
<?php
if ($stockid !== false)
{
?>
		<button type="submit" class="btn-pop-delete" form="form-stock" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()">Delete</button>
<?php
}
?>
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