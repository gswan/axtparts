<?PHP
// ********************************************
// Copyright 2003-2023 AXT Systems Pty Limited.
// All rights reserved.
// Author: Geoff Swan
// ********************************************
// $Id: config-axtparts.php 217 2021-01-29 01:37:18Z gswan $

if (!defined("_AXTPARTSDEFS"))
{
	define ("_AXTPARTSDEFS", true);
	
	define ("ENGPARTSVERSION", "4.03");
	
	// the database user information
	define ("PARTSUSER", "axtpartsuser");
	define ("PARTSPASSWD", "DB_PASSWORD");
	define ("PARTSHOST", "127.0.0.1");
	define ("PARTSDBASE", "axtparts");
	
	// Company information - for reports
	define ("ENG_RPT_CNAME", "COMPANY NAME");
	define ("ENG_RPT_ADDR", "COMPANY ADDR");
	define ("ENG_RPT_CITY", "COMPANY CITY");
	define ("ENG_RPT_STATE", "COMPANY STATE");
	define ("ENG_RPT_COUNTRY", "COMPANY COUNTRY");
	define ("ENG_RPT_PCODE", "COMPANY POSTCODE");
	define ("ENG_RPT_TEL", "COMPANY PHONE");
	define ("ENG_RPT_FAX", "COMPANY FAX");
	define ("ENG_RPT_WEB", "COMPANY WEBSITE");
	
	// the prefix 2 alpha characters for automatically generated part numbers
	define ("PARTPREFIX", "AX");
	
	// datasheets - relative to parent url
	define ("DATASHEETS_DIR", "datasheets/");
	
	// These paths are specified in full. They should be outside the
	// web docroot and read/writeable by the apache/httpd user.
	// software image directory - full path required.
	define ("SWIMAGE_DIR", "/var/axtparts/swimages/");
	
	// engdocs directory - full path required
	define ("ENGDOC_DIR", "/var/axtparts/engdocs/");
	
	// mfgdocs directory - full path required
	define ("MFGDOC_DIR", "/var/axtparts/mfgdocs/");
	
	// Maximum data sheet upload size. Check php.ini to set this value as well.
	define ("MAX_DATASHEET_UPLOAD_SIZE", 20000000);
	
	// These appear at the bottom of the forms, along with the version number
	define("SYSTEMHEADING", "Engineering Parts System");
	define("SYSTEMBRANDING", "AXT Systems");
	
	// Address type
	define ("CVTYPE_SUPPLIER", 0x01);
	define ("CVTYPE_CLIENT", 0x02);
	define ("CVTYPE_EMPLOYEE", 0x04);
	define ("CVTYPE_PERSONAL", 0x08);
	define ("CVTYPE_COMPANY", 0x10);
	
	// Maximum number of logs to show on the paginated log page. Set to 0 for all logs
	define ("MAXLOGS", 1000);
	
	// Log types
	define ("LOGTYPE_LOGIN", 1);
	define ("LOGTYPE_LOGOUT", 2);
	
	define ("LOGTYPE_ASSYCHANGE", 11);
	define ("LOGTYPE_ASSYNEW", 12);
	define ("LOGTYPE_ASSYDELETE", 13);
	define ("LOGTYPE_BOMCHANGE", 14);
	define ("LOGTYPE_BOMNEW", 15);
	define ("LOGTYPE_BOMDELETE", 16);
	define ("LOGTYPE_BOMVCHANGE", 17);
	define ("LOGTYPE_BOMVNEW", 18);
	define ("LOGTYPE_BOMVDELETE", 19);
	define ("LOGTYPE_COMPCHANGE", 20);
	define ("LOGTYPE_COMPNEW", 21);
	define ("LOGTYPE_COMPDELETE", 22);
	define ("LOGTYPE_CSTATECHANGE", 23);
	define ("LOGTYPE_CSTATENEW", 24);
	define ("LOGTYPE_CSTATEDELETE", 25);
	define ("LOGTYPE_CONTACTCHANGE", 26);
	define ("LOGTYPE_CONTACTNEW", 27);
	define ("LOGTYPE_CONTACTDELETE", 28);
	define ("LOGTYPE_CVCHANGE", 29);
	define ("LOGTYPE_CVNEW", 30);
	define ("LOGTYPE_CVDELETE", 31);
	define ("LOGTYPE_DSHEETCHANGE", 32);
	define ("LOGTYPE_DSHEETNEW", 33);
	define ("LOGTYPE_DSHEETDELETE", 34);
	define ("LOGTYPE_EDOCCHANGE", 35);
	define ("LOGTYPE_EDOCNEW", 36);
	define ("LOGTYPE_EDOCDELETE", 37);
	define ("LOGTYPE_FLTCHANGE", 38);
	define ("LOGTYPE_FLTNEW", 39);
	define ("LOGTYPE_FLTDELETE", 40);
	define ("LOGTYPE_FLDRTNCHANGE", 41);
	define ("LOGTYPE_FLDRTNNEW", 42);
	define ("LOGTYPE_FLDRTNDELETE", 43);
	define ("LOGTYPE_FPRINTCHANGE", 44);
	define ("LOGTYPE_FPRINTNEW", 45);
	define ("LOGTYPE_FPRINTDELETE", 46);
	define ("LOGTYPE_MACCHANGE", 47);
	define ("LOGTYPE_MACNEW", 48);
	define ("LOGTYPE_MACDELETE", 49);
	define ("LOGTYPE_MDOCCHANGE", 50);
	define ("LOGTYPE_MDOCNEW", 51);
	define ("LOGTYPE_MDOCDELETE", 52);
	define ("LOGTYPE_PARTCHANGE", 53);
	define ("LOGTYPE_PARTNEW", 54);
	define ("LOGTYPE_PARTDELETE", 55);
	define ("LOGTYPE_PGRPCHANGE", 56);
	define ("LOGTYPE_PGRPNEW", 57);
	define ("LOGTYPE_PGRPDELETE", 58);
	define ("LOGTYPE_PRDUNITCHANGE", 59);
	define ("LOGTYPE_PRDUNITNEW", 60);
	define ("LOGTYPE_PRDUNITDELETE", 61);
	define ("LOGTYPE_SUPPLCHANGE", 62);
	define ("LOGTYPE_SUPPLNEW", 63);
	define ("LOGTYPE_SUPPLDELETE", 64);
	define ("LOGTYPE_SWBLDCHANGE", 65);
	define ("LOGTYPE_SWBLDNEW", 66);
	define ("LOGTYPE_SWBLDDELETE", 67);
	define ("LOGTYPE_SWLCHANGE", 68);
	define ("LOGTYPE_SWLNEW", 69);
	define ("LOGTYPE_SWLDELETE", 70);
	define ("LOGTYPE_UNITCHANGE", 71);
	define ("LOGTYPE_UNITNEW", 72);
	define ("LOGTYPE_UNITDELETE", 73);
	define ("LOGTYPE_USERCHANGE", 74);
	define ("LOGTYPE_USERNEW", 75);
	define ("LOGTYPE_USERDELETE", 76);
	define ("LOGTYPE_VARIANTCHANGE", 77);
	define ("LOGTYPE_VARIANTNEW", 78);
	define ("LOGTYPE_VARIANTDELETE", 79);
	define ("LOGTYPE_WNTYCHANGE", 80);
	define ("LOGTYPE_WNTYNEW", 81);
	define ("LOGTYPE_WNTYDELETE", 82);
	define ("LOGTYPE_PARTLOCNNEW", 83);
	define ("LOGTYPE_PARTLOCNCHANGE", 84);
	define ("LOGTYPE_PARTLOCNDELETE", 85);
	define ("LOGTYPE_PARTLOCNASSIGN", 86);
	define ("LOGTYPE_PARTLOCNUNASSIGN", 87);
	define ("LOGTYPE_ROLENEW", 88);
	define ("LOGTYPE_ROLECHANGE", 89);
	define ("LOGTYPE_ROLEDELETE", 90);
	
	// Session details
	define ("SESSNAME", "axtparts");
	define ("SESSION_TIMEOUT", 0);
	define ("FORM_TOPHEIGHT", 60);
	define ("FORM_BOTTOMHEIGHT", 20);
	
	// User status values
	define ("USERSTATUS_INACTIVE", 0);
	define ("USERSTATUS_ACTIVE", 1);
	
	$_ustat_text = array (
	USERSTATUS_INACTIVE => "inactive",
	USERSTATUS_ACTIVE => "active",
	);
	
	// Page presets
	define ("PAGE_LOGIN", "frm-parts.php");
	define ("PAGE_LOGOUT", "index.php");
	
	// User privilege bits
	define ("UPRIV_NOTUSED",		0x80000000);
	define ("UPRIV_PARTS",			0x40000000);
	define ("UPRIV_FOOTPRINTS",		0x20000000);
	define ("UPRIV_COMPSTATES",		0x10000000);
	define ("UPRIV_PARTCATS",		0x08000000);
	define ("UPRIV_STOCKLOCN",		0x04000000);
	define ("UPRIV_COMPONENTS",		0x02000000);
	define ("UPRIV_DATASHEEETS",	0x01000000);
	define ("UPRIV_STOCK",			0x00800000);
	define ("UPRIV_ASSEMBLIES",		0x00400000);
	define ("UPRIV_BOMVARIANT",		0x00200000);
	define ("UPRIV_BOMITEMS",		0x00100000);
	define ("UPRIV_SWBUILD",		0x00080000);
	define ("UPRIV_ENGDOCS",		0x00040000);
	define ("UPRIV_MFGDOCS",		0x00020000);
	define ("UPRIV_ADDRESS",		0x00010000);
	define ("UPRIV_CONTACTS",		0x00008000);
	define ("UPRIV_UNITS",			0x00004000);
	define ("UPRIV_FIELDRTN",		0x00002000);
	define ("UPRIV_FAULTDESCR",		0x00001000);
	define ("UPRIV_WARRANTY",		0x00000800);
	define ("UPRIV_MACADDRESS",		0x00000400);
	define ("UPRIV_SWLICENCE",		0x00000200);
	define ("UPRIV_VIEWPARTS",		0x00000100);
	define ("UPRIV_VIEWADDRESS",	0x00000080);
	define ("UPRIV_VIEWASSY",		0x00000040);
	define ("UPRIV_VIEWUNIT",		0x00000020);
	define ("UPRIV_L0010",			0x00000010);
	define ("UPRIV_L0008",			0x00000008);
	define ("UPRIV_USERROLES",		0x00000004);
	define ("UPRIV_USERADMIN",		0x00000002);
	define ("UPRIV_USERLOGIN",		0x00000001);
	
	// User privilege text descriptions
	$_upriv_text = array (
	UPRIV_PARTS => "Edit parts",
	UPRIV_FOOTPRINTS => "Edit part footprints",
	UPRIV_COMPSTATES => "Edit component state",
	UPRIV_PARTCATS => "Edit part categories",
	UPRIV_STOCKLOCN => "Edit stock locations",
	UPRIV_COMPONENTS => "Edit components",
	UPRIV_DATASHEEETS => "Edit datasheets",
	UPRIV_STOCK => "Edit stock items",
	UPRIV_ASSEMBLIES => "Edit assembly detail",
	UPRIV_BOMVARIANT => "Edit BOM variants",
	UPRIV_BOMITEMS => "Edit BOM items",
	UPRIV_SWBUILD => "Edit software builds",
	UPRIV_ENGDOCS => "Edit engineering documents",
	UPRIV_MFGDOCS => "Edit manufacturing documents",
	UPRIV_ADDRESS => "Edit address details",
	UPRIV_CONTACTS => "Edit contact details",
	UPRIV_UNITS => "Edit unit details",
	UPRIV_FIELDRTN => "Edit field return",
	UPRIV_FAULTDESCR => "Edit fault description",
	UPRIV_WARRANTY => "Edit warranty details",
	UPRIV_MACADDRESS => "Edit MAC addresses",
	UPRIV_SWLICENCE => "Edit software license",
	UPRIV_VIEWPARTS => "View access to parts",
	UPRIV_VIEWADDRESS => "View address book",
	UPRIV_VIEWASSY => "View assembly detail",
	UPRIV_VIEWUNIT => "View unit details",
	UPRIV_USERROLES => "User roles",
	UPRIV_USERADMIN => "User administration",
	UPRIV_USERLOGIN => "Login permitted",
	);
	
	// User privilege values
	define ("USERPRIV_USER", 
			UPRIV_USERLOGIN
			);
	
	define ("USERPRIV_ADMIN",
			UPRIV_PARTS |
			UPRIV_FOOTPRINTS |
			UPRIV_COMPSTATES |
			UPRIV_PARTCATS |
			UPRIV_STOCKLOCN |
			UPRIV_COMPONENTS |
			UPRIV_DATASHEEETS |
			UPRIV_STOCK |
			UPRIV_ASSEMBLIES |
			UPRIV_BOMVARIANT |
			UPRIV_BOMITEMS |
			UPRIV_SWBUILD |
			UPRIV_ENGDOCS |
			UPRIV_MFGDOCS |
			UPRIV_ADDRESS |
			UPRIV_CONTACTS |
			UPRIV_UNITS |
			UPRIV_FIELDRTN |
			UPRIV_FAULTDESCR |
			UPRIV_WARRANTY |
			UPRIV_MACADDRESS |
			UPRIV_SWLICENCE |
			UPRIV_USERROLES |
			UPRIV_USERADMIN
			);

	// Tabs to show based on privilege values
	define ("TABPRIV_PARTS",
			UPRIV_PARTS |
			UPRIV_FOOTPRINTS |
			UPRIV_COMPSTATES |
			UPRIV_PARTCATS |
			UPRIV_STOCKLOCN |
			UPRIV_COMPONENTS |
			UPRIV_DATASHEEETS |
			UPRIV_STOCK |
			UPRIV_VIEWPARTS |
			UPRIV_USERLOGIN
			);
	
	define ("TABPRIV_ASSY",
			UPRIV_ASSEMBLIES |
			UPRIV_BOMVARIANT |
			UPRIV_BOMITEMS |
			UPRIV_SWBUILD |
			UPRIV_ENGDOCS |
			UPRIV_MFGDOCS |
			UPRIV_VIEWASSY
		);
	
	define ("TABPRIV_ADDRESS",
			UPRIV_ADDRESS |
			UPRIV_CONTACTS |
			UPRIV_VIEWADDRESS
		);
	
	define ("TABPRIV_ADMIN",
			UPRIV_USERADMIN
		);
	
	define ("TABPRIV_SEARCH",
			UPRIV_PARTS |
			UPRIV_FOOTPRINTS |
			UPRIV_COMPSTATES |
			UPRIV_PARTCATS |
			UPRIV_STOCKLOCN |
			UPRIV_COMPONENTS |
			UPRIV_DATASHEEETS |
			UPRIV_STOCK |
			UPRIV_VIEWPARTS |
			UPRIV_VIEWASSY
		);
	
	define ("TABPRIV_UNIT",
			UPRIV_UNITS |
			UPRIV_FIELDRTN |
			UPRIV_FAULTDESCR |
			UPRIV_WARRANTY |
			UPRIV_MACADDRESS |
			UPRIV_SWLICENCE |
			UPRIV_VIEWUNIT
		);
	
	
	// The tabs. Label => (url, privilege)
	$_cfg_tabs = array(
		"Parts" => array("href" => "frm-parts.php", "tabpriv" => TABPRIV_PARTS),
		"Assembly" => array("href" => "frm-assembly.php", "tabpriv" => TABPRIV_ASSY),
		"Address" => array("href" => "frm-addressbook.php", "tabpriv" => TABPRIV_ADDRESS),
		"Admin" => array("href" => "frm-admin.php", "tabpriv" => TABPRIV_ADMIN),
		"Search" => array("href" => "frm-search.php", "tabpriv" => TABPRIV_SEARCH),
	);
	
	
	
}
		
?>