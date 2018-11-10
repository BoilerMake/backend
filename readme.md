# BoilerMake API

[![Build Status](https://travis-ci.org/BoilerMake/backend.svg?branch=master)](https://travis-ci.org/BoilerMake/backend)
[![codecov](https://codecov.io/gh/BoilerMake/backend/branch/master/graph/badge.svg)](https://codecov.io/gh/BoilerMake/backend)
[![StyleCI](https://github.styleci.io/repos/48928914/shield)](https://styleci.io/repos/48928914)
[![StackShare](https://img.shields.io/badge/tech-stack-0690fa.svg?style=flat)](https://stackshare.io/boilermake/boilermake)


## Info
This API has been used for BoilerMake IV, V, and VI, powering the [frontend website](https://github.com/boilermake/frontend) as well as other ancillary services for internal use. 

### Server Requirements
* PHP >= 7.1.3
  * OpenSSL PHP Extension
  * PDO PHP Extension
  * Mbstring PHP Extension
  * Tokenizer PHP Extension
  * XML PHP Extension
  * Ctype PHP Extension
  * JSON PHP Extension
* [Nginx](https://www.nginx.com)
* [Composer](https://getcomposer.org)
* MySQL or PostgreSQL

### Installation
* Clone the repository
* Run `composer install`
* Copy `.env.example` to `.env` and configure your database credentials
* Run `php artisan migrate`
* Run `php artisan db:seed`
* Generate a JWT secret: `php artisan jwt:secret`
* Generate an application key: `php artisan key:generate`

* If you're using Apache, read this: https://github.com/tymondesigns/jwt-auth/wiki/Authentication

#### Phases:
These refer to the 3 phases a system can be in

1. Interest signups
2. Applications are open
3. Decisions are out
