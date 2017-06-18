<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserTest extends TestCase
{
    public function getToken()
    {
        $faker = \Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $response = $this->call('POST', '/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);

        return json_decode($response->getContent(), true)['data']['token'];
    }

    /**
     * Test that user interest signups are working.
     *
     * @return void
     */
    public function testGetUser()
    {
        $response = $this->json('GET', '/v1/users/me', [], ['HTTP_Authorization' => 'Bearer '.$this->getToken()]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
