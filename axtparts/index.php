<?php
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: index.php 89 2016-07-12 11:50:11Z gswan $

session_start();
include("config/config-axtparts.php");
require_once("classes/cl-axtparts.php");
$formfile = "index.php";
$loginid = "";
$myparts = new axtparts();

// Process a login request
if (isset($_POST["btn_login"]))
{
	if (isset($_POST["loginid"]))
		$loginid = trim($_POST["loginid"]);
	else 
		$loginid = false;
	
	if (isset($_POST["passwd"]))
		$passwd = trim($_POST["passwd"]);
	else 
		$passwd = false;
	
	$dbh = new mysqli(PARTSHOST, PARTSUSER, PARTSPASSWD, PARTSDBASE);
			
	if (!$dbh->connect_error)
	{
		if (($loginid !== false) && ($passwd !== false))
		{
			$t = $myparts->UserLogin($dbh, $loginid, $passwd);
			if ($t["status"] === true)
				$myparts->VectorMeTo(PAGE_LOGIN);
			else 
			{
				$err = $t["error"];
				$myparts->AlertMeTo($err);
			}
		}
		$dbh->close();
	}
}

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
    <div class="container container-login-header">
      <div class="container container-head-left">
        <span class="text-element text-head-siteheading">Engineering Parts System</span>
        <span class="text-element text-head-pagetitle">Login</span>
      </div>
      <div class="responsive-picture img-logo">
        <picture>
          <img alt="Placeholder Picture" width="200" height="200" src="./images/logo-axtparts.jpg" loading="lazy">
        </picture>
      </div>
    </div>
    <form class="form-container form-login" name="form-login" id="form-login" action="<?php print $formfile ?>" method="post">
      <div class="container container-form-element-login">
	    <label class="label label-formitem" form="form-login" for="loginid">Login ID</label>
		<input value="<?php print $loginid ?>" name="loginid" type="text" class="input-formelement" form="form-login" maxlength="200" title="Login ID">
	  </div>
      <div class="container container-form-element-login">
	    <label class="label label-formitem" form="form-login" for="passwd">Password</label>
		<input value="" name="passwd" type="password" class="passwd-login" title="Password" form="form-login">
	  </div>
      <div class="container container-form-element">
	    <button type="submit" class="btn-login" form="form-login" formaction="<?php print $formfile ?>" value="Login" name="btn_login">Login</button>
	  </div>
    </form>
    <div class="container container-footer-login">
      <span class="text-element text-footer-copyright"><?php print SYSTEMBRANDING.": ".ENGPARTSVERSION ?></span>
    </div>
  </div>
  <script src="js/jquery.min.js"></script>
  <script src="js/outofview.js"></script>
  <script src="js/js-forms.js"></script>
</body>

</html>