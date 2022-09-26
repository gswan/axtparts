<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-address.php 204 2016-07-17 06:22:10Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-address.php";
$returnformfile = "frm-addressbook.php";
$formname = "address";
$formtitle= "Address Details";

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

// This form required an addrid to be sent with the URL
if (isset($_GET['cvid']))
	$cvid = trim($_GET["cvid"]);
else
{
	$myparts->AlertMeTo("No address ID specified.");
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
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($cvid != "")
		{
			if (isset($_POST['cvname']))
			{
				$cvname = trim($_POST['cvname']);
				if (isset($_POST['cvtel']))
					$cvtel = trim($_POST['cvtel']);
				if (isset($_POST['cvweb']))
					$cvweb = trim($_POST['cvweb']);
				if (isset($_POST['cvcomment']))
					$cvcomment = trim($_POST['cvcomment']);
				if (isset($_POST['cvaddr1']))
					$cvaddr1 = trim($_POST['cvaddr1']);
				if (isset($_POST['cvaddr2']))
					$cvaddr2 = trim($_POST['cvaddr2']);
				if (isset($_POST['cvcity']))
					$cvcity = trim($_POST['cvcity']);
				if (isset($_POST['cvstate']))
					$cvstate = trim($_POST['cvstate']);
				if (isset($_POST['cvpcode']))
					$cvpcode = trim($_POST['cvpcode']);
				if (isset($_POST['cvcountry']))
					$cvcountry = trim($_POST['cvcountry']);
				if (isset($_POST['cvctype']))
					$ctype = $_POST['cvctype'];
				$cvtype = 0x00;
				if ($ctype == 'c')
					$cvtype |= CVTYPE_COMPANY;
				if (isset($_POST['cvtypesupplier']))
					$cvtype |= CVTYPE_SUPPLIER;
				if (isset($_POST['cvtypeclient']))
					$cvtype |= CVTYPE_CLIENT;
				if (isset($_POST['cvtypeemployee']))
					$cvtype |= CVTYPE_EMPLOYEE;
				if (isset($_POST['cvtypepersonal']))
					$cvtype |= CVTYPE_PERSONAL;

				// now we can update the record
				$q_cv = "update custvend "
					. "\n set "
					. "\n cvname='".$dbh->real_escape_string($cvname)."', "
					. "\n cvaddr1='".$dbh->real_escape_string($cvaddr1)."', "
					. "\n cvaddr2='".$dbh->real_escape_string($cvaddr2)."', "
					. "\n cvcity='".$dbh->real_escape_string($cvcity)."', "
					. "\n cvstate='".$dbh->real_escape_string($cvstate)."', "
					. "\n cvpcode='".$dbh->real_escape_string($cvpcode)."', "
					. "\n cvcountry='".$dbh->real_escape_string($cvcountry)."', "
					. "\n cvcomment='".$dbh->real_escape_string($cvcomment)."', "
					. "\n cvtel='".$dbh->real_escape_string($cvtel)."', "
					. "\n cvweb='".$dbh->real_escape_string($cvweb)."', "
					. "\n cvtype='".$dbh->real_escape_string($cvtype)."' "
					. "\n where cvid='".$dbh->real_escape_string($cvid)."' "
					;
					
				$s_cv = $dbh->query($q_cv);
				if (!$s_cv)
					$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
			}
			else
				$myparts->AlertMeTo("Name must be specified.");
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No address ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_delete"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if ($cvid != "")
		{
			// Check to see if the address is a supplier, has contacts, is a manufacturer or customer.
			// If it does, then don't delete until all links are removed by the user
			$nc = $myparts->ReturnCountOf($dbh, "contacts", "contid", "cvid", $cvid);
			if ($nc > 0)
				$myparts->AlertMeTo("There are ".$nc." contacts still associated with this organisation.");
			else 
			{
				$ns = $myparts->ReturnCountOf($dbh, "suppliers", "suppid", "suppid", $cvid);
				if ($ns > 0)
					$myparts->AlertMeTo("This organisation is still being used as a supplier.");
				else 
				{
					$nm = $myparts->ReturnCountOf($dbh, "unit", "mfgid", "mfgid", $cvid);
					if ($nm > 0)
						$myparts->AlertMeTo("This organisation is a manufacturer of ".$nm." production units.");
					else 
					{
						$nu = $myparts->ReturnCountOf($dbh, "unit", "custid", "custid", $cvid);
						if ($nu > 0)
							$myparts->AlertMeTo("This organisation is a customer of production units.");
						else 
						{
							// delete the organisation
							$q_c = "delete from custvend "
								. "\n where cvid='".$dbh->real_escape_string($cvid)."' "
								. "\n limit 1 "
								;
								
							$s_c = $dbh->query($q_c);
							if (!$s_c)
								$myparts->AlertMeTo("Error: ".htmlentities($dbh->error, ENT_COMPAT));
				
							$dbh->close();
							$myparts->AlertMeTo("Organisation deleted.");
							$myparts->VectorMeTo($returnformfile);
							die();
						}
					}
				}
			}
		}
		else
		{
			$dbh->close();
			$myparts->AlertMeTo("No address ID specified.");
			$myparts->VectorMeTo($returnformfile);
			die();
		}
	}
}
elseif (isset($_POST["btn_newcontact"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if (isset($_POST['newcontactname']))
		{
			$editform = "frm-contact.php";
			$name = trim($_POST['newcontactname']);
			if ($name != "")
			{
				$c = $myparts->AddContactToCV($dbh, $cvid, $name);
				if (isset($c["rowid"]))
				{
					$contid = $c["rowid"];
					$dbh->close();
					$myparts->VectorMeTo($editform."?contid=".$contid);
					die();
				}
				else 
					$myparts->AlertMeTo("Error: ".htmlentities($c["error"], ENT_COMPAT));
			}
			else 
				$myparts->AlertMeTo("Name must be specified");
		}
		else
			$myparts->AlertMeTo("Name must be specified");
	}
}

$r_cv = $myparts->GetAddressRow($dbh, $cvid);
$contactlist = $myparts->GetContacts($dbh, $cvid);
$nc = count($contactlist);
$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Address";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?cvid=".$cvid;

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
        <span class="text-element text-head-pagetitle">Address Details</span>
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
    <span class="text-element text-formtitle">Address Detail</span>
    <form class="form-container form-address" name="form-address" id="form-address" action="<?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? $url : "#") ?>" method="post" onsubmit='return deleteCheck()'>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="name">Name</label>
		<input value="<?php print htmlentities($r_cv['cvname']) ?>" name="cvname" type="text" class="input-formelement" form="form-address" maxlength="50" title="Person or organisation name" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvaddr">Address Line 1</label>
		<input value="<?php print htmlentities($r_cv['cvaddr1']) ?>" name="cvaddr1" type="text" class="input-formelement" form="form-address" maxlength="80" title="Address line 1" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvaddr2">Address Line 2</label>
		<input value="<?php print htmlentities($r_cv['cvaddr2']) ?>" name="cvaddr2" type="text" class="input-formelement" form="form-address" maxlength="80" title="Address line 2" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvcity">City</label>
		<input value="<?php print htmlentities($r_cv['cvcity']) ?>" name="cvcity" type="text" class="input-formelement" form="form-address" maxlength="50" title="City" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="state">State</label>
		<input value="<?php print htmlentities($r_cv['cvstate']) ?>" name="cvstate" type="text" class="input-formelement" form="form-address" maxlength="20" title="State" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="pcode">Post Code</label>
		<input value="<?php print htmlentities($r_cv['cvpcode']) ?>" name="cvpcode" type="text" class="input-formelement" form="form-address" maxlength="20" title="Postcode/zipcode" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="country">Country</label>
	    <input value="<?php print htmlentities($r_cv['cvcountry']) ?>" name="cvcountry" type="text" class="input-formelement" form="form-address" maxlength="30" title="Country" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvtel">Phone</label>
		<input value="<?php print htmlentities($r_cv['cvtel']) ?>" name="cvtel" type="text" class="input-formelement" form="form-address" maxlength="40" title="Phone" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvweb">Website</label>
		<input value="<?php print htmlentities($r_cv['cvweb']) ?>" name="cvweb" type="text" class="input-formelement" form="form-address" maxlength="80" title="Website" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="cvcomment">Comment</label>
		<input value="<?php print htmlentities($r_cv['cvcomment']) ?>" name="cvcomment" type="text" class="input-formelement" form="form-address" maxlength="255" title="Comment" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "readonly") ?>>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-address" for="type">Type</label>
        <div class="container container-address-type">
          <div class="container container-address-subtype">
		    <label class="checkbox chk-addrtype" form="form-address">
			  <input type="checkbox" name="cvtypesupplier" value="1" <?php print ($r_cv['cvtype'] & CVTYPE_SUPPLIER ? "checked" : "" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Supplier</span>
		    </label>
			<label class="checkbox chk-addrtype" form="form-address">
			  <input type="checkbox" name="cvtypeclient" value="1" <?php print ($r_cv['cvtype'] & CVTYPE_CLIENT ? "checked" : "" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Client</span>
	  		</label>
	  		<label class="checkbox chk-addrtype" form="form-address">
			  <input type="checkbox" name="cvtypeemployee" value="1" <?php print ($r_cv['cvtype'] & CVTYPE_EMPLOYEE ? "checked" : "" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Employee</span>
			</label>
			<label class="checkbox chk-addrtype" form="form-address">
			  <input type="checkbox" name="cvtypepersonal" value="1" <?php print ($r_cv['cvtype'] & CVTYPE_PERSONAL ? "checked" : "" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Personal</span>
			</label>
		  </div>
          <div class="container container-address-subtype">
		  	<label class="radio radio-type" form="form-address">
			  <input type="radio" name="cvctype" value="c" <?php print ($r_cv['cvtype'] & CVTYPE_COMPANY ? "checked" : "" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Company</span>
			</label>
			<label class="radio radio-type" form="form-address">
			  <input type="radio" name="cvctype" value="i" <?php print ($r_cv['cvtype'] & CVTYPE_COMPANY ? "" : "checked" ) ?> form="form-address" <?php print ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) ? "" : "disabled") ?>>
			  <span>Individual</span>
	  		</label>
	  	</div>
        </div>
      </div>
      <div class="container container-form-element">
      <?php
      if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) === true)
      {
   	  ?>
        <div class="container container-form-buttons">
		  <button type="submit" class="btn-form-update" form="form-address" formaction="<?php print $url ?>" value="Update" name="btn_update" id="btn_update" onclick="delClear()" title="Update the details entered in the database">Update</button>
		  <button type="submit" class="btn-form-delete" form="form-address" formaction="<?php print $url ?>" value="Delete" name="btn_delete" id="btn_delete" onclick="delSet()" disabled="true" title="Delete this organisation from the database">Delete</button>
		  <button type="button" class="btn-form-enable" name="addreditenable" id="addreditenable" value="EN" onclick="javascript:document.querySelector('#btn_delete').disabled=false" title="Enable the delete button">EN</button>
	    </div>
   	  <?php
   	  }
   	  ?>
      </div>
    </form>
 	<div class="rule rule-formsection">
      <hr>
    </div>
    <span class="text-element text-formtitle">Contacts</span>
    <div class="container container-grid-contacts">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Name</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Phone</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Mobile</span>
      </div>
      <div class="container container-gridhead-el-B1">
        <span class="text-element text-gridhead-column">Email</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Position</span>
      </div>
      <?php
		for ($i = 0; $i < $nc; $i++)
		{
			if ($i%2 == 0)
				$stline = "evn";
			else
				$stline = "odd";
				
			$r_contact = $contactlist[$i];
			$contid = $r_contact['contid'];
			
	  ?>
	  <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="<?php print "frm-contact.php?contid=".$contid ?>"><?php print htmlentities($r_contact['contname']) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($r_contact['conttel']) ?></span>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($r_contact['contmob']) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($r_contact['contemail']) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($r_contact['contposn']) ?></span>
      </div>
	  <?php
		}
	  ?>
    </div>
	<?php
	if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
	{
	?>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <span class="text-element text-formtitle">New Contact Entry</span>
    <form class="form-container form-newcontact" name="form-newcontact" id="form-newcontact" action="<?php print $url ?>" method="post">
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-newcontact" for="newcontactname">Contact Name</label>
		<input value="" name="newcontactname" type="text" class="input-formelement" form="form-newcontact" maxlength="90" title="Enter Contact Name">
	  </div>
      <div class="container container-form-element">
	    <button type="submit" class="btn-form-add" form="form-newcontact" formaction="<?php print $url ?>" value="Add" name="btn_newcontact" id="btn_newcontact">Add</button>
	  </div>
    </form>
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