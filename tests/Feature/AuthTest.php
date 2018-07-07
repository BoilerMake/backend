<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\GithubUser;
use App\Models\Application;

class AuthTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        //we'll overrride the phase on a test by test basis.
        $phase = Application::PHASE_APPLICATIONS_OPEN;
        putenv("APP_PHASE={$phase}");
    }

    /**
     * Test that sign up validation and token generation is working.
     *
     * @return void
     */
    public function testValidationSignUp()
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $this->post('/v1/users/register', ['email' => $faker->email])
            ->assertSee('["The password field is required."]');
        $this->post('/v1/users/register', ['password' => $password])
            ->assertSee('["The email field is required."]');
        //this one should succeed
        $email = $faker->email;
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email])
            ->assertJsonStructure(['data'=>['token']]);
        //and now we can't sign up with same email
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email])
            ->assertJsonFragment(['success'=>false]);
        //need a password
        $this->post('/v1/users/register', ['password' => '', 'email' => $faker->email])
            ->assertJsonFragment(['success'=>false]);
    }

    /**
     * Test that registration and using the returned token allows for auth page access.
     *
     * @return void
     */
    public function testValidSignUpToken()
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $response = $this->call('POST', '/v1/users/register', ['password' => $password, 'email' => $email]);
        $token = json_decode($response->getContent(), true)['data']['token'];
        $response = $this->call('GET', '/v1/users/me?token='.$token, [], [], [], []);
        $response->assertJsonStructure(['data'=>[
            'id',
            'email',
            'created_at',
            'updated_at',
        ]], json_decode($response->getContent(), true));
    }

    /**
     * Test that login works correctly.
     *
     * @return void
     */
    public function testAuthentication()
    {
        $faker = \Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->call('POST', '/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);
        $this->post('/v1/users/login', ['email' => $email, 'password' => $password])
            ->assertJsonStructure(['data'=>['token']]);
        $this->post('/v1/users/login', [])
            ->assertSee('["The email field is required.","The password field is required."]');
        $this->post('/v1/users/login', ['email' => $email, 'password' => $password.'#'])
            ->assertJson([
                'data'=>null,
//                "message"=>"applications are not open",
                'success'=>false, ]);
    }

    public function testAppPhaseSignups()
    {
        $phase = Application::PHASE_INTEREST_SIGNUPS;
        putenv("APP_PHASE={$phase}");
        $faker = \Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->assertJsonFragment([
                'data'=>null,
                'message'=>'applications are not open',
                'success'=>false, ]);
        $phase = Application::PHASE_APPLICATIONS_OPEN;
        putenv("APP_PHASE={$phase}");
        $faker = \Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->assertJsonStructure(['data'=>['token']]);
    }

    public function testConfirmationCode()
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email]);
        $user = User::where('email', $email)->first();
        $this->assertDatabaseHas('users', ['email' => $email, 'confirmed' => 0, 'confirmation_code' => $user->confirmation_code]);
        $this->post('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'message'=>'Email confirmed!',
             ]);
        $this->post('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'message'=>'Email confirmed!',
             ]);
        $this->post('/v1/users/verify/'.$faker->uuid)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Code is invalid',
            ]);
        $this->post('/v1/users/verify/')
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Code is required',
            ]);
    }

    public function testPasswordReset()
    {

        //requesting a reset for a nonexistent user should fail
        $response = $this->call('POST', '/v1/users/reset/send', ['email' => \Faker\Factory::create()->email]);
        $response->assertJsonFragment(['success'=>false]);

        $user = factory(User::class)->create();

        //should be able to request a reset for a valid user
        $response = $this->call('POST', '/v1/users/reset/send', ['email' => $user->email]);
        $response->assertJsonFragment(['success'=>true]);

        $this->assertDatabaseHas('password_resets', ['user_id'=>$user->id]);
        $resetToken = \App\Models\PasswordReset::where('user_id', $user->id)->first()->token;
        $newPassword = 'newpass';

        //should be able to reset password with token
        $response = $this->call('POST', '/v1/users/reset/perform', ['token' => $resetToken, 'password'=>$newPassword]);
        $response->assertJsonFragment(['success'=>true]);

        //but only once...
        $response = $this->call('POST', '/v1/users/reset/perform', ['token' => $resetToken, 'password'=>$newPassword]);
        $response->assertJsonFragment(['success'=>false, 'message'=>'link already used']);

        //and must be a valid token/link
        $response = $this->call('POST', '/v1/users/reset/perform', ['token' => $resetToken.'123', 'password'=>$newPassword]);
        $response->assertJsonFragment(['success'=>false, 'message'=>'invalid reset link']);

        Carbon::setTestNow(Carbon::now()->addHours(50));
        //and can't be too old
        $response = $this->call('POST', '/v1/users/reset/perform', ['token' => $resetToken, 'password'=>$newPassword]);
        $response->assertJsonFragment(['success'=>false, 'message'=>'link expired']);

        //and we should be able to login with new password
        $this->post('/v1/users/login', ['email' => $user->email, 'password' => $newPassword])
            ->assertJsonStructure(['data'=>['token']]);

        //        $token = json_decode($response->getContent(), true)['data']['token'];
    }

    public function testSavingGithub()
    {
        $faker = \Faker\Factory::create();
        $token = $faker->uuid;
        $username = $faker->userName;
        $data = ['login' => $username,
            'id' => 707582,
            'avatar_url' => 'https://avatars5.githubusercontent.com/u/707582?v=4',
            'gravatar_id' => '',
            'url' => "https://api.github.com/users/{$username}",
            'html_url' => "https://github.com/{$username}",
            'followers_url' => "https://api.github.com/users/{$username}/followers",
            'following_url' => "https://api.github.com/users/{$username}/following{/other_user}",
            'gists_url' => "https://api.github.com/users/{$username}/gists{/gist_id}",
            'starred_url' => "https://api.github.com/users/{$username}/starred{/owner}{/repo}",
            'subscriptions_url' => "https://api.github.com/users/{$username}/subscriptions",
            'organizations_url' => "https://api.github.com/users/{$username}/orgs",
            'repos_url' => "https://api.github.com/users/{$username}/repos",
            'events_url' => "https://api.github.com/users/{$username}/events{/privacy}",
            'received_events_url' => "https://api.github.com/users/{$username}/received_events",
            'type' => 'User',
            'site_admin' => false,
            'name' => $faker->name,
            'company' => 'asdfg ',
            'blog' => "http://{$username}.com",
            'location' => 'Purdue',
            'email' => "user@{$username}.com",
            'hireable' => true,
            'bio' => 'bioo',
            'public_repos' => 33,
            'public_gists' => 12,
            'followers' => 41,
            'following' => 34,
            'created_at' => '2011-04-04T03:27:14Z',
            'updated_at' => '2017-07-12T17:00:01Z',
        ];
        GithubUser::store($data, $token);
        $this->assertDatabaseHas('github_users', ['username' => $username]);
    }

    public function testMissingToken()
    {
        $response = $this->json('GET', '/v1/users/me');
        $response
            ->assertStatus(401)
            ->assertJson(['message' => 'Token not provided']);
    }

    public function testInvalidToken()
    {
        $response = $this->json('GET', '/v1/users/me', [], ['HTTP_Authorization'=>'Bearer blah']);
        $response
            ->assertStatus(401)
            ->assertJson(['message' => 'Wrong number of segments']);
    }
}
