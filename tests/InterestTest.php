<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InterestTest extends TestCase
{
    /**
     * Test that user interest signups are working
     *
     * @return void
     */
    public function testInterestSignup()
    {
        $faker = Faker\Factory::create();
        $email = $faker->email;
        $this->post('/v1/interest/signup', ['email' => $email])
            ->see('ok');
        $this->seeInDatabase('interest_signups', ['email' => $email]);
    }
}
