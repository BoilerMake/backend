#BoilerMake Website [backend]


###getting up and running:
* get PHP + mySQL + composer up and running
* run `composer install`
* copy .env.example into .env and configure your DB credentials (and create a DB)
* run `php artisan migrate`
* Generate a jwt secret
  * php artisan jwt:generate
* if you are on apache for dev; read this: 
	* https://github.com/tymondesigns/jwt-auth/wiki/Authentication 
* make a `hacker` role in the table (todo: seeder for this?)