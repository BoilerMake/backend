<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * Test that user signups are working.
     *
     * @return void
     */
    public function testGetUpdateUser()
    {
        $user = $this->makeTestUser();
        $token = $user->getToken();
        $response = $this->json('GET', '/v1/users/me', [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json()['data'];
        $email = $user->email;
        $this->assertEquals($email, $data['email']);

        $faker = \Faker\Factory::create();
        $firstName = $faker->firstName;
        $data[User::FIELD_FIRSTNAME] = $firstName;

        $response = $this->json('PUT', '/v1/users/me', $data, ['HTTP_Authorization' => 'Bearer '.$token]);
        $this->assertDatabaseHas('users', [
            'id'=>$user->id,
            User::FIELD_FIRSTNAME=>$firstName,
        ]);
    }
}
