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

    public function testHashIDs()
    {
        $user = $this->makeTestUser();
        $hash = $user->getHashIDAttribute();
        $lookupUser = User::getFromHashID($hash);
        $this->assertEquals($user->id, $lookupUser->id);
    }

    public function testBadHashIDs()
    {
        $lookupUser = User::getFromHashID('148971asdfjk');
        $this->assertEquals($lookupUser, null);
    }

    public function testGetResumeFilePath()
    {
        putenv('S3_PREFIX=prod');
        $user = $this->makeTestUser();
        $this->assertEquals($user->getResumeFilePath(), 'prod/resumes/'.$user->id.'.pdf');
    }

    public function testSlug()
    {
        $user = $this->makeTestUser();
        $this->assertEquals($user->slug(), $user->first_name.' '.$user->last_name.' (#'.$user->id.')');
    }

    public function testHasApplication()
    {
        $user = $this->makeTestUser();
        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($user->application());
    }
}
