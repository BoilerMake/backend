<?php

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
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['email' => $email])
            ->assertSee('["The password field is required."]');
        $this->post('/v1/users/register', ['password' => $password])
            ->assertSee('["The email field is required."]');
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email])
            ->assertJsonStructure([
                 'token',
            ]);
    }

    /**
     * Test that registration and using the returned token allows for auth page access.
     *
     * @return void
     */
    public function testValidSignUpToken()
    {
        $faker = Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $response = $this->call('POST', '/v1/users/register', ['password' => $password, 'email' => $email]);
        $token = json_decode($response->getContent(), true)['token'];
        $response = $this->call('GET', '/v1/users/me?token='.$token, [], [], [], []);
        $response->assertJsonStructure(['data'=>[
            'id',
            'email',
            'created_at',
            'updated_at',
            'identifier',
        ]], json_decode($response->getContent(), true));
        $response = $this->call('GET', '/v1/debug', [], [], [], ['HTTP_Authorization' => 'Bearer: '.$token]);
        $response->assertJsonStructure(['data'=>[
            'id',
            'first_name',
            'last_name',
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
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->call('POST', '/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);
        $this->post('/v1/users/login', ['email' => $email, 'password' => $password])
            ->assertJsonStructure(['token']);
        $this->post('/v1/users/login', [])
            ->assertSee('["The email field is required.","The password field is required."]');
        $this->post('/v1/users/login', ['email' => $email, 'password' => $password.'#'])
            ->assertJson([
                 'error' => 'invalid_credentials',
             ]);
    }

    public function testAppPhaseSignups()
    {
        config(['app.phase' => 1]);
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->assertJsonFragment([
                 'error' => 'applications are not open',
             ]);
        config(['app.phase' => 3]);
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->assertJsonStructure(['token']);
    }

    public function testConfirmationCode()
    {
        $faker = Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email]);
        $user = User::where('email', $email)->first();
        $this->assertDatabaseHas('users', ['email' => $email, 'confirmed' => 0, 'confirmation_code' => $user->confirmation_code]);
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'success' => 'Email Confirmed',
             ]);
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->assertJsonFragment([
                 'success' => 'Email Confirmed',
             ]);
        $this->get('/v1/users/verify/'.$faker->uuid)
            ->assertJsonFragment([
                'error' => 'Invalid Code',
            ]);
        $this->get('/v1/users/verify/')
            ->assertJsonFragment([
                'error' => 'Code Required',
            ]);
    }
}
