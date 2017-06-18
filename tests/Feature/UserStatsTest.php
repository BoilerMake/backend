<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Faker\Factory;

class UserStatsTest extends TestCase
{
    public function testUserEvent() {
//        $this->refreshApplication();
        $faker = Factory::create();
        $uuid = $faker->uuid;

        $user = factory(User::class)->create();
        $token = $user->getToken();

        $data = [
            "event"     => "eventname",
            "context"   => "",
            "uuid"      => $uuid,
            "referrer"  => null
        ];

        $this->json('POST', "/v1/stats", $data, ['HTTP_Authorization'=>"Bearer {$token}"]);//->dump();
//        Log::info($response->json());
//        $response->assertStatus(200);
//        $this->assertDatabaseHas('user_stats',['uuid'=>$uuid,'user_id'=>$user->id]);
    }
}
