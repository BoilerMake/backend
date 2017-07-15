<?php

namespace Tests\Feature;

use Faker\Factory;
use Tests\TestCase;
use App\Models\User;

class UserStatsTest extends TestCase
{
    public function testUserEvent()
    {
        $faker = Factory::create();
        $uuid = $faker->uuid;
        $user = factory(User::class)->create();
        $token = $user->getToken();

        $data = [
            'event'     => 'eventname',
            'context'   => 'c',
            'referrer'  => null,
        ];

        $response = $this->json('POST', "/v1/stats", $data, ['HTTP_Authorization'=>'Bearer '.$token, 'x-uuid'=>$uuid]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_stats',['uuid'=>$uuid,'user_id'=>$user->id]);
    }
    public function testGuestEvent()
    {
        $faker = Factory::create();
        $uuid = $faker->uuid;
        $data = [
            'event'     => 'eventname',
            'context'   => 'c',
            'referrer'  => null,
        ];

        $response = $this->json('POST', "/v1/stats", $data, ['x-uuid'=>$uuid]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_stats',['uuid'=>$uuid]);
    }
}
