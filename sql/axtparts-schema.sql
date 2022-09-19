create database axtparts;
use axtparts;

create table assemblies (
	assyid int unsigned auto_increment,
	partid int unsigned,
	assydescr varchar(100),
	assyrev tinyint unsigned,
	assyaw varchar(2),
	primary key (assyid),
	index (partid)
);

create table boms (
	bomid int unsigned auto_increment,
	partid int unsigned,
	assyid int unsigned,
	qty decimal(8,2),
	ref text,
	um varchar(10),
	alt int unsigned,
	primary key(bomid),
	index (partid),
	index (assyid)
);

create table bomvariants (
	bomvid int unsigned auto_increment,
	variantid int unsigned,
	bomid int unsigned,
	primary key (bomvid),
	index (variantid),
	index (bomid)
);

create table components (
	compid int unsigned auto_increment,
	partid int unsigned,
	mfgname varchar(50),
	mfgcode varchar(25),
	datasheet int unsigned,
	compstateid int unsigned,
	primary key (compid),
	index (partid)
);

create table compstates (
	compstateid int unsigned auto_increment,
	statedescr varchar(50),
	primary key (compstateid)
);

create table contacts (
	contid int unsigned auto_increment,
	cvid int unsigned,
	contname varchar(100),
	contposn varchar(50),
	conttel varchar(30),
	contmob varchar(30),
	contemail varchar(40),
	contcomment varchar(255),
	primary key (contid),
	index (cvid)
);

create table custvend (
	cvid int unsigned auto_increment,
	cvname varchar(50),
	cvaddr1 varchar(80),
	cvaddr2 varchar(80),
	cvcity varchar(50),
	cvstate varchar(20),
	cvpcode varchar(20),
	cvcountry varchar(30),
	cvweb varchar(80),
	cvabn varchar(20),
	cvtel varchar(40),
	cvfax varchar(40),
	cvcomment varchar(255),
	cvtype tinyint,
	primary key (cvid),
	index (cvname),
	index (cvtype)
);

create table datasheets (
	dataid int unsigned auto_increment,
	datasheetpath varchar(120),
	datadescr varchar(250),
	partcatid int unsigned,
	primary key (dataid),
	index (partcatid)
);

create table engdocs (
	engdocid int unsigned auto_increment,
	assyid int unsigned,
	engdocpath varchar(120),
	engdocdescr varchar(250),
	primary key (engdocid),
	index (assyid)
);

create table fault (
	faultid int unsigned auto_increment,
	unitid int unsigned,
	faultdescr text,
	unitin date,
	unitout date,
	repairdescr text,
	repairer varchar(50),
	fldrtnid int unsigned,
	primary key (faultid),
	index (unitid),
	index (fldrtnid)
);

create table fldrtn (
	fldrtnid int unsigned auto_increment,
	unitid int unsigned,
	custdescr text,
	allocdate date,
	uranum varchar(10),
	custref varchar(40),
	primary key (fldrtnid),
	index (unitid)
);

create table footprint (
	fprintid int unsigned auto_increment,
	fprintdescr varchar(50),
	primary key (fprintid)
);

create table locn (
	locid int unsigned auto_increment,
	locref varchar(50),
	locdescr varchar(250),
	primary key (locid)
);

create table log  (
	logid int unsigned auto_increment,
	logtype int,
	logdate datetime,
	uid int unsigned,
	logmsg varchar(250),
	primary key (logid),
	index (logtype),
	index (logdate),
	index (uid)
);

create table macs (
	macid int unsigned auto_increment,
	macaddr varchar(20),
	unitid int unsigned,
	primary key (macid),
	unique (macaddr),
	index (unitid)
);

create table mfgdocs (
	mfgdocid int unsigned auto_increment,
	assyid int unsigned,
	mfgdocpath varchar(120),
	mfgdocdescr varchar(250),
	primary key (mfgdocid),
	index (assyid)
);

create table parts (
	partid int unsigned auto_increment,
	partdescr varchar(100),
	footprint int unsigned,
	partcatid int unsigned,
	partnumber varchar(10),
	primary key (partid),
	index (partcatid),
	unique (partnumber),
	index (partdescr)
);

create table pgroups (
	partcatid int unsigned auto_increment,
	catdescr varchar(50),
	datadir varchar(100),
	primary key (partcatid)
);

create table produnits (
	produnitid int unsigned auto_increment,
	unitidsub int unsigned,
	unitidmaster int unsigned,
	primary key (produnitid),
	index (unitidsub),
	index (unitidmaster)
);

create table role (
	roleid int unsigned auto_increment,
	rolename varchar(64),
	privilege int unsigned,
	primary key (roleid),
	unique (rolename)
);

create table stock (
	stockid int unsigned auto_increment,
	qty int unsigned,
	note varchar(64),
	locid int unsigned,
	partid int unsigned,
	primary key (stockid),
	index (locid)
);

create table suppliers (
	compsuppid int unsigned auto_increment,
	compid int unsigned,
	suppid int unsigned,
	suppcatno varchar(40),
	primary key (compsuppid),
	index (compid),
	index (suppid)
);

create table swbuild (
	swbuildid int unsigned auto_increment,
	swname varchar(100),
	buildhost varchar(25),
	buildimage varchar(100),
	releaserev varchar(20),
	releasedate date,
	primary key (swbuildid)
);

create table swlicence (
	swlid int unsigned auto_increment,
	unitid int unsigned,
	swbuildid int unsigned,
	licencenum varchar(20),
	primary key (swlid),
	index (unitid),
	index (swbuildid)
);

create table unit (
	unitid int unsigned auto_increment,
	serialnum varchar(20),
	assyid int unsigned,
	variantid int unsigned,
	mfgid int unsigned,
	mfgdate date,
	custid int unsigned,
	custordnum varchar(20),
	myinvnum varchar(20),
	shipdate date,
	warranty int unsigned,
	primary key (unitid),
	unique (serialnum),
	index (assyid),
	index (variantid),
	index (mfgid),
	index (mfgdate),
	index (custid)
);

create table user (
	uid int unsigned auto_increment,
	loginid varchar(16),
	passwd varchar(128),
	username varchar(128),
	lastlogin datetime,
	logincount int unsigned default 0,
	status int unsigned,
	roleid int unsigned,
	primary key (uid),
	unique(loginid),
	index (status),
	index (roleid)
);

create table variant (
	variantid int unsigned auto_increment,
	variantname varchar(25),
	variantdescr text,
	variantstate varchar(25),
	primary key (variantid)
);

create table warranty (
	wrntid int unsigned auto_increment,
	warrantydescr varchar(25),
	wtyweeks smallint unsigned,
	primary key (wrntid)
);



