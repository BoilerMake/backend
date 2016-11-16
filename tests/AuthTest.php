<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    /**
     * Test that sign up validation and token generation is working
     *
     * @return void
     */
    public function testValidationSignUp()
    {
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users', ['email' => $email])
            ->see('["The first name field is required.","The last name field is required.","The password field is required."]');
        $this->post('/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password])
            ->see('["The email field is required."]');
        $this->post('/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->seeJsonStructure([
                 'token',
            ]);
    }
    /**
     * Test that registration and using the returned token allows for auth page access
     *
     * @return void
     */
    public function testValidSignUpToken()
    {
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $response = $this->call('POST', '/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);
        $token = json_decode($response->getContent(), true)['token'];
        $response = $this->call('GET', '/v1/users/me?token=' . $token, [], [], [], []);
        $this->seeJsonStructure([
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'created_at',
            'updated_at',
            'identifier'
        ], json_decode($response->getContent(), true));
    }
}
