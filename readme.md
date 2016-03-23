#BoilerMake Website [backend]

##Info
This site is currently being actively developed for use in BoilerMake 4, ~October 2014. If you are interested in using some/all of the components with your hackathon, please email dev@boilermake.org. We are planning on releasing a complete stable beta by the begining of May. 

###Current features/ planned for the roadmap:
* Application system (including letting hackers apply as a team)
* Allowing organizers to rank applications, and then bulk accepting/denying/waitlisting hackers based on an application's aggregate score.
* Sponsor portal for easy downloading of hackers' resumes.
* QR-code based checkin system.
* SMS + e-mail updates to hackers + sponsors during the course of the event
* unified portal for generating table numbers for the Expo, as well as potentially a judging system. 



###getting up and running:
* get PHP + mySQL + composer up and running
  * php: install via package manager
  * mySQL: install via package manager
	* remember to install/setup the php mysql driver
	  * arch linux: https://wiki.archlinux.org/index.php/PHP#MySQL.2FMariaDB
	  * ubuntu/debian linux: `sudo apt-get install php5-mysql`
	* also remember to start the service if you're in linux
  * composer: [instructions here](https://getcomposer.org/doc/00-intro.md)
  	* useful: [https://adamcod.es/2013/03/07/composer-install-vs-composer-update.html](https://adamcod.es/2013/03/07/composer-install-vs-composer-update.html) 
* run `composer install`
* copy .env.example into .env and configure your DB credentials (and create a DB)
* create the database
  * run `mysql -u root -p`
  * in the sql shell run `create database boilermake;`
* run `php artisan migrate`
* run `php artisan db:seed`
* Generate a jwt secret
  * php artisan jwt:generate
  * also run this: php artisan key:generate
* if you are on apache for dev; read this:
	* https://github.com/tymondesigns/jwt-auth/wiki/Authentication



####Phases:
These refer to the 3 phases a system can be in
1 - interest signups
2 - applications are open
3 - decisions are out
