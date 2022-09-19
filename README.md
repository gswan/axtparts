# axtparts
AXTParts Electronic engineering parts management system

## History
AXTParts was initially conceived and written by Geoff Swan in around 2002-2003 to manage an ever-growing electronic parts inventory in an electronics lab. It consists of a database and web application, intended for use on a Linux (LAMP) server, to allow devices to connect and use it.

It was originally used on a desktop browser and the interface was designed to accommodate this. Eventually this was released on the axtsystems.com website in 2017 as version 3 for other electronic hobbyists and development labs to use. 

After gaining some popularity and in order to handle more diverse viewing devices (phones and tablets) it was updated to version 4, featuring a responsive interface design. Version 4 was also moved from an internal subversion repository to github, to allow people to contribute, report issues or fork the project into their own versions.

## What does it do?
AXTParts was designed to keep track of parts, components, BOMs and part stock within an electronics hardware development environment. Whilst it was never designed as a comprehensive inventory management solution, it does incorporate part stock locations and quantities so you can see if you have any of part XYZ and where you can find them. 

It allows you to take a completed circuit diagram and enter new parts, check existing parts and stock and enter a BOM for it. BOMs are entered manually, there is currently no provision to import from various popular CAD packages. BOMs can then be printed or viewed, stock locations and quantities on hand can be seen and the components gathered for prototyping. Automatic stock adjustments are not performed within the software, this is done manually as the system was never designed for MRP.

Datasheets for components can be uploaded into the system, allowing quick access from a single place if you are working in a lab. Engineering documents (schematics and PCB overlays etc) can be uploded so they are also available from the same interface.


## Getting Started

These instructions are for both fresh installations and upgrades to current installations.

### Prerequisites

The software was developed for use on a LAMP (Linux-Apache-MySQL-PHP) server. It uses very little in resources so can easily be housed in a small PC on the network.

These versions are currently used/tested but it is also likely to work with other versions as well. Care has been taken to not depend on many external packages or features.

* PHP: Versions 5.5, 7 and 8 are currently in use with the application.
* MySQL: MySQL or MariaDB current versions (MySQL-15) are in use.
* Apache: 2.4


### Installation

#### Fresh Install for new systems

This assumes you have a Linux server with Apache, MySQL and PHP operational. This may be Fedora, Arch-Linux, Ubuntu or whatever.

Installation requires several simple steps.

* Create the database using the database schema file
```
$ mysql -uroot -p < axtparts-schema.sql
```
* Import the initial data. 

The initial data file can be edited to set your own part categories and footprints to start with. 
It includes an admin user with default initial password 'mypassword!'. This can be changed once logged in, and additional users created.
```
$ mysql -uroot -p < axtparts-initialdata.sql
```

Now create a user with privileges to work with the 

A step by step series of examples that tell you how to get a development env running

Say what the step will be

```
Give the example
```

#### Upgrading from Version 3

And repeat

```
until finished
```

End with an example of getting some data out of the system or using it for a little demo



## Running the tests

Explain how to run the automated tests for this system

### Break down into end to end tests

Explain what these tests test and why

```
Give an example
```

### And coding style tests

Explain what these tests test and why

```
Give an example
```

## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* [Dropwizard](http://www.dropwizard.io/1.0.2/docs/) - The web framework used
* [Maven](https://maven.apache.org/) - Dependency Management
* [ROME](https://rometools.github.io/rome/) - Used to generate RSS Feeds

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Billie Thompson** - *Initial work* - [PurpleBooth](https://github.com/PurpleBooth)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
* Inspiration
* etc
