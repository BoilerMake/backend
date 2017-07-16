# BoilerMake Website [backend]

[![Build Status](https://travis-ci.org/BoilerMake/backend.svg?branch=master)](https://travis-ci.org/BoilerMake/backend)
[![codecov](https://codecov.io/gh/BoilerMake/backend/branch/master/graph/badge.svg)](https://codecov.io/gh/BoilerMake/backend)
[![StyleCI](https://styleci.io/repos/48928914/shield?branch=cleaning)](https://styleci.io/repos/48928914)
[![StackShare](https://img.shields.io/badge/tech-stack-0690fa.svg?style=flat)](https://stackshare.io/boilermake/boilermake)


## Info
This API is currently being actively developed for use in BoilerMake V. If you are interested in using some/all of the components with your hackathon, please email dev@boilermake.org. [featureset + more information here!](http://2016.boilermake.org/about)

### Prerequisites
* Have PHP, MySQL, and Composer installed.
  * PHP: Install via package manager
  * MySQL: install via package manager
	* Remember to install/setup the PHP MySql driver
	  * Arch Linux: https://wiki.archlinux.org/index.php/PHP#MySQL.2FMariaDB
	  * Ubuntu/Debian Linux: `sudo apt-get install php5-mysql`
	* Also remember to start the service if you're in Linux
  * Composer: [instructions here](https://getcomposer.org/doc/00-intro.md)

### Installation
* Run `composer install`
* Copy `.env.example` to `.env` and configure your MySQL credentials (and create a new database if you haven't already)
  * Create the database. Type the following in your Terminal
     * `mysql -u root -p`
     * `create database boilermake;`
* Run `php artisan migrate`
* Run `php artisan db:seed`
* Generate a JWT secret: `php artisan jwtkey:generate`
* Generate an application key: `php artisan key:generate`
* Generate a PodPod secret (OPTIONAL): `php artisan podpod:generate`

* If you're using Apache, read this:
	* https://github.com/tymondesigns/jwt-auth/wiki/Authentication

#### Phases:
These refer to the 3 phases a system can be in

1. Interest signups
2. Applications are open
3. Decisions are out
