# axtparts
AXTParts Electronic engineering parts management system

## History
AXTParts was initially conceived and written by Geoff Swan in around 2002-2003 to manage an ever-growing electronic parts inventory in an electronics lab. It consists of a database and web application, intended for use on a Linux (LAMP) server, to allow devices to connect and use it.

It was originally used on a desktop browser and the interface was designed to accommodate this. Eventually this was released on the axtsystems.com website in 2017 as version 3 for electronic development labs and hobbyists to use. 

After gaining some popularity and in order to handle more diverse viewing devices (phones and tablets) it was updated to version 4, featuring a responsive interface design. Version 4 was also moved from an internal subversion repository to github, to allow people to contribute, report issues or fork the project into their own versions.

## What does it do?
AXTParts was designed to keep track of parts, components, BOMs and part stock within an electronics hardware development environment. Whilst it was never designed as a comprehensive inventory management solution, it does incorporate part stock locations and quantities so you can see if you have any of part XYZ and where you can find them. 

It allows you to take a completed circuit diagram and enter new parts, check existing parts and stock and enter a BOM for it. BOMs are entered manually, there is currently no provision to import from various popular CAD packages. BOMs can then be printed or viewed, stock locations and quantities on hand can be seen and the components gathered for prototyping. Automatic stock adjustments are not performed within the software, this is done manually as the system was never designed for MRP.

Datasheets for components can be uploaded into the system, allowing quick access from a single place if you are working in a lab. Engineering documents (schematics and PCB overlays etc) can be uploded so they are also available from the same interface.

## Demo Site
A demonstration site exists at https://axtsystems.com/axtparts/

Login with username 'demo' and password 'axtpartsdemo'. The database is periodically flushed and reloaded to remove any junk.


## Getting Started

These instructions are for both fresh installations and upgrades to current installations.

### Prerequisites

The software was developed for use on a LAMP (Linux-Apache-MySQL-PHP) server. It uses very little in resources so can easily be housed in a small PC on the network.

These versions are currently used/tested but it is also likely to work with other versions as well. 
Care has been taken not to unnecessarily depend on external packages or features.

* PHP: Versions 5.5, 7 and 8 with MySQLi are currently in use with the application.
* MySQL: MySQL or MariaDB current versions (MySQL-15) are in use.
* Apache: 2.4


## Installation

### Fresh Install for new systems

This assumes you have a Linux server with Apache, MySQL and PHP operational. This may be Fedora, Arch-Linux, Gentoo, Debian, Ubuntu or whatever you prefer.
So long as you understand how to manually install and configure databases and web applications.

Installation requires several simple steps.

**1. Clone the repository or get a copy of the source archive**

In this installation the source is located in /opt/axtparts4 and the site symlinked into the web docroot. 
This method is used to allow updated versions to be retrieved into separate directories and symlinked into operation to test without disruption.

**Using git to clone the repository**
```
# cd /opt
# git clone https://github.com/gswan/axtparts.git
# cd axtparts
```
**Using a source tarball**
```
# cd /opt
# $ tar -xf axtparts-4.0.1.tar.gz 
# cd axtparts
```


**2. Database.**

* Create the database using the database schema file
```
# cd /opt/axtparts/sql
# mysql -uroot -p < axtparts-schema.sql
```
* Import the initial data. 

The initial data file can be edited to set your own part categories and footprints to start with. 
It includes an admin user with default initial password 'mypassword!'. This can be changed once logged in, and additional users created.
```
# cd /opt/axtparts4/sql
# mysql -uroot -p < axtparts-initialdata.sql
```
* Create a user with privileges to work with the axtparts database.

The user information must be entered into the axtparts configuration file in a later step so make a note of the password used here.
In this case the connection is via 127.0.0.1 on a TCP socket. If you prefer to use Unix sockets for connection then use 'localhost' instead as the hostname.
```
# mysql -uroot -p mysql
mysql> grant insert,update,select,delete on axtparts.* to 'axtpartsuser'@'127.0.0.1' identified by 'PASSWORD';
mysql> flush privileges;
mysql> exit;
```
*Note: This example shows command-line installation, however a GUI database manager (like phpMyAdmin) could also be used to perform the same tasks.*


**3. Web application.**
Edit the config file in /opt/axtparts/axtparts/config/config-axtparts.php to set the correct database connection parameters. 
Other configuration parameters can also be set here (company information and partprefix).
```
define ("PARTSUSER", "axtpartsuser");
define ("PARTSPASSWD", "DB_PASSWORD");
define ("PARTSHOST", "127.0.0.1");
define ("PARTSDBASE", "axtparts");
```

Link the web application into the web docroot for your apache server. 
In this example we are using /var/www/https as the docroot for the http-ssl server.
```
# cd /opt/axtparts
# mkdir -pv datasheets
# mkdir -pv /var/axtparts/{swimages,engdocs,mfgdocs}
# chown -R apache.apache /opt/axtparts/axtparts/datasheets
# chown -R apache.apache /var/axtparts
# ln -s /opt/axtparts/axtparts /var/www/axtparts
```
Now you should be able to use your web browser to connect to the site and log in using the default admin user and password.

---
### Upgrading from Version 3
The database requires one table alteration to handle sub-assembly BOM variants.
This requires the following to be executed on your database to add a column to the boms table.
```
mysql> use axtparts;
mysql> alter table boms add column blvarid int unsigned default 0;
```

**Using git to clone the repository**
```
# cd /opt
# git clone https://github.com/gswan/axtparts.git
# cd axtparts
```
**Using a source tarball**
```
# cd /opt
# tar -xf axtparts-4.0.1.tar.gz 
# cd axtparts
```
Copy the existing datasheets directory to the new application. 
It is assumed here that the existing application is in /var/www/https/axtparts/
```
# cp -a /var/www/https/axtparts/datasheets /opt/axtparts/axtparts/datasheets
```

Edit the config file in /opt/axtparts/axtparts/config/config-axtparts.php to set the correct database connection parameters. 
These and other configuration parameters can be copied from your existing installation config file.
```
define ("PARTSUSER", "axtpartsuser");
define ("PARTSPASSWD", "DB_PASSWORD");
define ("PARTSHOST", "127.0.0.1");
define ("PARTSDBASE", "axtparts");
```

Link the web application into the web docroot for your apache server. In this example we are using /var/www/https as the docroot for the http-ssl server.
Since the existing application is located in axtparts/ we will move this to axtparts3/ and link the new installation to axtparts/
```
# cd /opt/axtparts
# mv /var/www/https/axtparts /var/www/https/axtparts3
# ln -s /opt/axtparts/axtparts /var/www/axtparts
```
Now you should be able to use your web browser to connect to the site and log in using existing user credentials.

---
## Authors

* **Geoff Swan** - *Initial releases* - [AXT Systems](https://axtsystems.com)


## License

This project is licensed under the GPL 3.0 License - see the [LICENSE](LICENSE) file for details

