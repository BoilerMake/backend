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
        $response = $this->jsonWithAuth('GET', '/v1/users/me', [], $user);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json()['data'];
        $email = $user->email;
        $this->assertEquals($email, $data['email']);

        $faker = \Faker\Factory::create();
        $firstName = $faker->firstName;
        $data[User::FIELD_FIRSTNAME] = $firstName;

        $response = $this->jsonWithAuth('PUT', '/v1/users/me', $data, $user);
        $this->assertDatabaseHas('users', [
            'id'=>$user->id,
            User::FIELD_FIRSTNAME=>$firstName,
        ]);
    }
}
