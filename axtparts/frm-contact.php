<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-contact.php 202 2016-07-17 06:08:05Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-contact.php";
$returnformfile = "frm-addressbook.php";
$formname = "contact";
$formtitle= "Contact Details";

$myparts = new axtparts();

if ($myparts->SessionCheck() === false)
{
	$myparts->AlertMeTo("Session Expired.");
	$myparts->VectorMeTo(PAGE_LOGOUT);
	die();
}

$username = $myparts->SessionMeName();

if ($myparts->SessionMePrivilegeBit(TABPRIV_ADDRESS) !== true)
{
	$myparts->AlertMeTo("Insufficient tab privileges.");
	die();
}

// This form required a contact id to be sent with the URL
if (isset($_GET['contid']))
	$contid = trim($_GET["contid"]);
else
{
	$myparts->AlertMeTo("No contact ID specified.");
	$myparts->VectorMeTo($returnformfile);
	die();
}

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh->connect_error)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

if (isset($_POST["btn_update"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($contid != "")
		{
			if (isset($_POST['contname']))
			{
				$contname = trim($_POST['contname']);
				if (isset($_POST['conttel']))
					$conttel = trim($_POST['conttel']);
				if (isset($_POST['contemail']))
					$contemail = trim($_POST['contemail']);
				if (isset($_POST['contposn']))
					$contposn = trim($_POST['contposn']);
				if (isset($_POST['contcomment']))
					$contcomment = trim($_POST['contcomment']);
				if (isset($_POST['contmob']))
					$contmob = trim($_POST['contmob']);

				// now we can update the record
				$q_c = "update contacts "
					. "\n set "
					. "\n contname='".$dbh->real_escape_string($contname)."', "
					. "\n conttel='".$dbh->real_escape_string($conttel)."', "
					. "\n contemail='".$dbh->real_escape_string($contemail)."', "
					. "\n contposn='".$dbh->real_escape_string($contposn)."', "
					. "\n contmob='".$dbh->real_escape_string($contmob)."', "
					. "\n contcomment='".$dbh->real_escape_string($contcomment)."' "
					. "\n where contid='".$dbh->real_escape_string($contid)."' "
					;
					
				$s_c = $dbh->query($q_c);
				if (!$s_c)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			}
			else
				$myparts->AlertMeTo("Name must be specified.");
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No contact ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($contid != "")
		{
			// delete the contact
			$q_c = "delete from contacts "
				. "\n where contid='".$dbh->real_escape_string($contid)."' "
				. "\n limit 1 "
				;
					
			$s_c = $dbh->query($q_c);
			if (!$s_c)
				$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
	
			$dbh->close();
			$myparts->AlertMeTo("Contact deleted.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No contact ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}

$r_c = $myparts->GetContact($dbh, $contid);
$cvid = $r_c["cvid"];
$r_cv = $myparts->GetAddressRow($dbh, $cvid);

$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Address";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?contid=".$contid;

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
        <span class="text-element text-head-pagetitle">Contact Details</span>
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
    <span class="text-element text-formtitle">Contact Detail</span>
    <form class="form-container form-contact" name="form-contact" id="form-contact" action="<?php print $url ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="orgname">Organisation</label>
        <a class="link-text link-grid-dataitem" href="<?php print "frm-address.php?cvid=".$cvid ?>"><?php print htmlentities($r_cv['cvname']) ?></a>
      </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="contname">Name</label>
		<input value="<?php print htmlentities($r_c['contname']) ?>" name="contname" type="text" class="input-formelement" form="form-contact" maxlength="100" title="Person's name" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="conttel">Office Phone Number</label>
		<input value="<?php print htmlentities($r_c['conttel']) ?>" name="conttel" type="text" class="input-formelement" form="form-contact" maxlength="30" title="Office phone number" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="contmob">Mobile Phone Number</label>
		<input value="<?php print htmlentities($r_c['contmob']) ?>" name="contmob" type="text" class="input-formelement" form="form-contact" maxlength="30" title="Mobile phone number" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="contemail">Email</label>
		<input value="<?php print htmlentities($r_c['contemail']) ?>" name="contemail" type="text" class="input-formelement" form="form-contact" maxlength="40" title="Email address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="contposn">Position</label>
		<input value="<?php print htmlentities($r_c['contposn']) ?>" name="contposn" type="text" class="input-formelement" form="form-contact" maxlength="50" title="Position" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-contact" for="contcomment">Comment</label>
		<input value="<?php print htmlentities($r_c['contcomment']) ?>" name="contcomment" type="text" class="input-formelement" form="form-contact" maxlength="250" title="Comment" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
<?php
if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
{
?>
        <div class="container container-form-buttons">
		  <button type="submit" class="btn-form-update" form="form-contact" formaction="<?php print $url ?>" value="Update" name="btn_update" id="btn_update" onclick="delClear()" title="Update the details entered in the database">Update</button>
		  <button type="submit" class="btn-form-delete" form="form-contact" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()" disabled="true" title="Delete this contact from the database">Delete</button>
		  <button type="button" class="btn-form-enable" onclick="javascript:document.querySelector('#btn_delete').disabled=false" value="EN" title="Enable the delete button"  name="btn_enable">EN</button>
		</div>
<?php
}
?>
      </div>
    </form>
    <div class="container container-footer">
      <span class="text-element text-footer-copyright"><?php print SYSTEMBRANDING.": ".ENGPARTSVERSION ?></span>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>