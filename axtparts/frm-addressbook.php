<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: frm-addressbook.php 201 2016-07-17 05:49:39Z gswan $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "frm-addressbook.php";
$formname = "addressbook";
$formtitle= "Address Book";

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

$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
if ($dbh->connect_error)
{
	$myparts->AlertMeTo("Could not connect to database");
	$myparts->VectorMeTo($returnformfile);
	die();
}

// if a 'show' value is sent with the URL then we need that page, otherwise
// default to 'a'
$showpage = "a";
if (isset($_GET['show']))
	$showpage = trim($_GET["show"]);

if (isset($_POST["btn_newcv"]))
{
	if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) !== true)
		$myparts->AlertMeTo("Insufficient privileges.");
	else 
	{
		if (isset($_POST['newaddrname']))
		{
			$editform = "frm-address.php";
			$name = trim($_POST['newaddrname']);
			if ($name != "")
			{
				$c = $myparts->AddNewCV($dbh, $name);
				if (isset($c["rowid"]))
				{
					$cvid = $c["rowid"];
					$dbh->close();
					$myparts->VectorMeTo($editform."?cvid=".$cvid);
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
			if (isset($_POST['sel-cvid']))
			{
				$cvid = trim($_POST['sel-cvid']);
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
				$myparts->AlertMeTo("No address ID specified");
		}
		else
			$myparts->AlertMeTo("Name must be specified");
	}
}

// Get the form data
$q_addr =  "select "
	. "\n cvid, "
	. "\n cvname, "
	. "\n cvaddr1, "
	. "\n cvaddr2, "
	. "\n cvcity, "
	. "\n cvstate, "
	. "\n cvpcode, "
	. "\n cvcountry, "
	. "\n cvtel, "
	. "\n cvfax "
	. "\n from custvend ";

if ($showpage == "other")
	$q_addr .= "\n where cvname not rlike '^[A-Z,a-z].*' ";
else
	$q_addr .= "\n where lower(substring(cvname,1,1))='".($dbh->real_escape_string($showpage))."' ";

$q_addr .= "\n order by cvname";
$s_addr = $dbh->query($q_addr);
$dataset_addr = array();
$na = 0;
if ($s_addr)
{
	while ($r_addr = $s_addr->fetch_assoc())
	{
		$dataset_addr[$na]["address"] = $r_addr;
		
		$q_contacts = "select "
				. "\n cvid, "
				. "\n contname "
				. "\n from contacts "
				. "\n where cvid='".$dbh->real_escape_string($r_addr['cvid'])."' "
				. "\n order by contname"
				;
													
		$s_contacts = $dbh->query($q_contacts);
		$dataset_addr[$na]["contacts"] = array();
		$nc = 0;
		if ($s_contacts)
		{
			while ($r_contacts = $s_contacts->fetch_assoc())
			{
				$dataset_addr[$na]["contacts"][$nc] = $r_contacts;
				$nc++;
			}
			$s_contacts->free();
		}
		$na++;
	}
	$s_addr->free();
}

// List of clients
$q_clients = "select "
		. "\n cvname, "
		. "\n cvid "
		. "\n from custvend "
		. "\n order by cvname"
		;

$s_clients = $dbh->query($q_clients);
$dataset_clients = array();
$nx = 0;
if ($s_clients)
{
	while ($r_clients = $s_clients->fetch_assoc())
	{
		$dataset_clients[$nx][0] = $r_clients['cvid'];
		$dataset_clients[$nx][1] = $r_clients['cvname'];
		$nx++;
	}
	$s_clients->free();
}

$dbh->close();

$tabparams = array();
$tabparams["tabon"] = "Address";
$tabparams["tabs"] = $_cfg_tabs;

$url = $formfile."?show=".$showpage;

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
        <span class="text-element text-head-pagetitle">Address Book</span>
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
    <div class="container container-abletters">
    <?php
    	for ($i = 0; $i < 26; $i++)
    	{
    		$letter = chr(ord('A') + $i);
    		$sp = chr(ord('a') + $i);
    		if ($showpage == $sp)
    			$ssfx = "-active";
   			else
   				$ssfx = "";
   				
			print "<a class=\"link-text link-abletter".$ssfx."\" href=\"".$formfile."?show=".$sp."\">".$letter."</a>";
    	}
    ?>
		<a class="link-text link-abletter<?php print ($showpage == 'other' ? "-active" : "") ?>" href="<?php print $formfile."?show=other" ?>">*</a>
	</div>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <div class="container container-gridhead-abook">
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Name</span>
      </div>
      <div class="container container-gridhead-el-B0">
        <span class="text-element text-gridhead-column">Address</span>
      </div>
      <div class="container container-gridhead-el-B1">
        <span class="text-element text-gridhead-column">Phone</span>
      </div>
      <div class="container container-gridhead-el-B2">
        <span class="text-element text-gridhead-column">Contacts</span>
      </div>
    </div>
    <?php
    	for ($i = 0; $i < $na; $i++)
    	{
    		if ($i%2 == 0)
				$stline = "evn";
			else
				$stline = "odd";
			
			$editurl = "frm-address.php?cvid=".$dataset_addr[$i]["address"]['cvid'];
			$address = ($dataset_addr[$i]["address"]['cvaddr1'] == "" ? "" : $dataset_addr[$i]["address"]['cvaddr1'].", ") 
					.  ($dataset_addr[$i]["address"]['cvaddr2'] == "" ? "" : $dataset_addr[$i]["address"]['cvaddr2'].", ")
					.  ($dataset_addr[$i]["address"]['cvcity'] == "" ? "" : $dataset_addr[$i]["address"]['cvcity'].", ")
					.  ($dataset_addr[$i]["address"]['cvstate'] == "" ? "" : $dataset_addr[$i]["address"]['cvstate']." ")
					.  ($dataset_addr[$i]["address"]['cvpcode'] == "" ? "" : $dataset_addr[$i]["address"]['cvpcode'].", ")
					.  ($dataset_addr[$i]["address"]['cvcountry'] == "" ? "" : $dataset_addr[$i]["address"]['cvcountry'])
					;
    ?>
    <div class="container container-grid-data-abook">
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <a class="link-text link-grid-dataitem" href="<?php print $editurl ?>"><?php print htmlentities($dataset_addr[$i]["address"]['cvname']) ?></a>
      </div>
      <div class="container container-grid-dataitem-B0-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($address) ?></span>
      </div>
      <div class="container container-grid-dataitem-B1-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem"><?php print htmlentities($dataset_addr[$i]["address"]['cvtel']) ?></span>
      </div>
      <div class="container container-grid-dataitem-B2-<?php print $stline ?>">
        <span class="text-element text-grid-dataitem">
		<?php
			$nc = count($dataset_addr[$i]["contacts"]);
			for ($j = 0; $j < $nc; $j++)
			{
				if ($j == 0)
					print ($dataset_addr[$i]["contacts"][$j]["contname"] == "" ? "" : htmlentities($dataset_addr[$i]["contacts"][$j]["contname"]));
				else
					print ($dataset_addr[$i]["contacts"][$j]["contname"] == "" ? "" : htmlentities(", ".$dataset_addr[$i]["contacts"][$j]["contname"]));
			}
		?>
		</span>
      </div>
    </div>
    <?php
    	}
   	?>
   	<?php
		if ($myparts->SessionMePrivilegeBit(UPRIV_ADDRESS) === true)
		{
	?>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <span class="text-element text-formtitle">New Address Entry</span>
    <form class="form-container form-newaddress" name="form-newaddress" id="form-newaddress" action="<?php print $url ?>" method="post">
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-newaddress" for="newaddrname">Organisation Name</label>
		<input value="" name="newaddrname" type="text" class="input-formelement" form="form-newaddress" maxlength="50" title="Enter Organisation Name">
	  </div>
      <div class="container container-form-element">
	    <button type="submit" class="btn-form-add" form="form-newaddress" formaction="<?php print $url ?>" value="Add" name="btn_newcv" id="btn_newcv">Add</button>
	  </div>
    </form>
    <?php
		}
		if ($myparts->SessionMePrivilegeBit(UPRIV_CONTACTS) === true)
		{
	?>
    <div class="rule rule-formsection">
      <hr>
    </div>
    <span class="text-element text-formtitle">New Contact Entry</span>
    <form class="form-container form-newcontact" name="form-newcontact" id="form-newcontact" action="<?php print $url ?>" method="post">
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-newcontact" for="sel-cvid">Select Organisation</label>
		<select name="sel-cvid" class="select sel-formitem" form="form-newcontact">
		<?php
			for ($i = 0; $i < $nx; $i++)
				print "<option value=".$dataset_clients[$i][0].">".htmlentities($dataset_clients[$i][1])."</option>";
		?>
        </select>
	  </div>
      <div class="container container-form-element">
	    <label class="label label-formitem" form="form-newcontact" for="newcontactname">Contact Name</label>
		<input value="" name="newcontactname" type="text" class="input-formelement" form="form-newcontact" maxlength="100" title="Enter Contact Name">
	  </div>
      <div class="container container-form-element">
	    <button type="submit" class="btn-form-add" form="form-newcontact" formaction="<?php print $url ?>" value="Add" name="btn_newcontact">Add</button>
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