<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use \App\Models\User;

class AuthTest extends TestCase
{
    /**
     * Test that sign up validation and token generation is working.
     *
     * @return void
     */
    public function testValidationSignUp()
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['email' => $faker->email])
            ->assertSee('["The password field is required."]');
        $this->post('/v1/users/register', ['password' => $password])
            ->assertSee('["The email field is required."]');
        $this->post('/v1/users/register', ['password' => $password, 'email' => $faker->email])
            ->assertJsonStructure(['data'=>['token']]);

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
            'identifier',
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
        config(['app.phase' => 1]);
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
        config(['app.phase' => 3]);
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
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'success' => true,
                 'data'=>'Email confirmed!',
             ]);
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'success' => true,
                 'data'=>'Email confirmed!',
             ]);
        $this->get('/v1/users/verify/'.$faker->uuid)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Code is invalid',
            ]);
        $this->get('/v1/users/verify/')
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
}
