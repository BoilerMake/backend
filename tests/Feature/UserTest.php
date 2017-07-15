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
    public function testGetUser()
    {
        $user = factory(User::class)->create();
        $token = $user->getToken();
        $response = $this->json('GET', '/v1/users/me', [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
