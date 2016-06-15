<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->visit('/')->see('BoilerMake');
        $this->visit('/v1/ping')->see('pong');
    }
    public function testInterestSignup()
    {
        $faker = Faker\Factory::create();
        $email = $faker->email;
        $this->post('/v1/interest/signup', ['email' => $email])
            ->see('ok');
        $this->seeInDatabase('interest_signups', ['email' => $email]);
    }
}
