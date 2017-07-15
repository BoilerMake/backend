<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Tests\TestCase;

class ApplicationsTest extends TestCase
{
    /**
     * Test that user signups are working.
     *
     * @return void
     */
    public function testGetUpdateApplication()
    {
        $faker = \Faker\Factory::create();
        $user = $this->makeTestUser();
        $token = $user->getToken();
        $response = $this->json('GET', '/v1/users/me/application', [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json()['data'];
        $application = $data['application'];
        foreach(Application::USER_FIELDS_TO_INJECT as $x)
            $this->assertTrue(array_key_exists($x,$application));

        $firstName = $faker->firstName;
        $linedinVal = $faker->userName;
        $github = $faker->userName;
        $application[User::FIELD_FIRSTNAME] = $firstName;
        $application[Application::FIELD_LINKEDIN] = $linedinVal;
        $application[Application::FIELD_GITHUB] = $github;


        $response = $this->json('PUT', '/v1/users/me/application', $application, ['HTTP_Authorization' => 'Bearer '.$token]);
        $response->assertStatus(200)->assertJson(['success' => true]);
//        $data = $response->json()['data'];

        $this->assertDatabaseHas('users',[
            'id'=>$user->id,
            User::FIELD_FIRSTNAME=>$firstName
        ]);
        $this->assertDatabaseHas('applications',[
            'user_id'=>$user->id,
            Application::FIELD_LINKEDIN=>$linedinVal,
            Application::FIELD_GITHUB=>$github
        ]);

    }

    /**
     * Ensure that APP_PHASE stops us from leaking decisions
     */
    public function testLeakingDecisions() {
        $user = $this->makeTestUser();
        $token = $user->getToken();

        $release = Application::PHASE_DECISIONS_REVEALED;
        putenv("APP_PHASE={$release}");
        $response = $this->json('GET', '/v1/users/me/application', [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $application = $response->json()['data']['application'];
        $this->assertTrue(array_key_exists(Application::FIELD_DECISION,$application));

        $hide = Application::PHASE_APPLICATIONS_OPEN;
        putenv("APP_PHASE={$hide}");
        $response = $this->json('GET', '/v1/users/me/application', [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $application = $response->json()['data']['application'];
        $this->assertFalse(array_key_exists(Application::FIELD_DECISION,$application));


    }
//    public function testGetApplicationEmailNotConfirmed() {
//
//    }
}
