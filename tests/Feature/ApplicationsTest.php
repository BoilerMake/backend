<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Application;

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
        $response = $this->jsonWithAuth('GET', '/v1/users/me/application', [], $user);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json()['data'];
        $application = $data['application'];
        foreach (Application::USER_FIELDS_TO_INJECT as $x) {
            $this->assertTrue(array_key_exists($x, $application));
        }

        $firstName = $faker->firstName;
        $linedinVal = $faker->userName;
        $github = $faker->userName;
        $application[User::FIELD_FIRSTNAME] = $firstName;
        $application[Application::FIELD_LINKEDIN] = $linedinVal;
        $application[Application::FIELD_GITHUB] = $github;

        $response = $this->jsonWithAuth('PUT', '/v1/users/me/application', $application, $user);
        $response->assertStatus(200)->assertJson(['success' => true]);
//        $data = $response->json()['data'];

        $this->assertDatabaseHas('users', [
            'id'=>$user->id,
            User::FIELD_FIRSTNAME=>$firstName,
        ]);
        $this->assertDatabaseHas('applications', [
            'user_id'=>$user->id,
            Application::FIELD_LINKEDIN=>$linedinVal,
            Application::FIELD_GITHUB=>$github,
        ]);
    }

    /**
     * Ensure that APP_PHASE stops us from leaking decisions.
     */
    public function testLeakingDecisions()
    {
        $user = $this->makeTestUser();

        $release = Application::PHASE_DECISIONS_REVEALED;
        putenv("APP_PHASE={$release}");
        $response = $this->jsonWithAuth('GET', '/v1/users/me/application', [], $user);
        $application = $response->json()['data']['application'];
        $this->assertTrue(array_key_exists(Application::FIELD_DECISION, $application));

        $hide = Application::PHASE_APPLICATIONS_OPEN;
        putenv("APP_PHASE={$hide}");
        $response = $this->jsonWithAuth('GET', '/v1/users/me/application', [], $user);
        $application = $response->json()['data']['application'];
        $this->assertFalse(array_key_exists(Application::FIELD_DECISION, $application));
    }

    public function testHintEmail() {
        $faker = \Faker\Factory::create();

        $purdueStudent = $this->makeTestUser("{$faker->userName}@purdue.edu");
        $otherStudent = $this->makeTestUser("{$faker->userName}@nomatch.edu");

        $this->assertNotEquals($purdueStudent->hintSchoolIdFromEmail(),null);
        $this->assertEquals($otherStudent->hintSchoolIdFromEmail(),null);
    }

//    public function testGetApplicationEmailNotConfirmed() {
//
//    }
}
