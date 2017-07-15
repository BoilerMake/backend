<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function makeTestUser()
    {
        $faker = \Faker\Factory::create();
        $email = $faker->email;

        return User::addNew($email, null, false);
    }
}
