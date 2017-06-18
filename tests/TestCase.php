<?php


namespace Tests;
use App\Models\User;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function getNewUser()
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email]);
        $user = User::where('email', $email)->first();

        return $user;
    }
}
