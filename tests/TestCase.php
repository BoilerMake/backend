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
    public function jsonWithAuth($method, $url, $params,User $user) {
        return $this->json($method, $url, $params, ['HTTP_Authorization' => 'Bearer '.$user->getToken()]);
    }
}
