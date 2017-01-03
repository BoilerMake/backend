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
        $this->post('/v1/users', ['email' => $email])
            ->see('["The password field is required."]');
        $this->post('/v1/users', ['password' => $password])
            ->see('["The email field is required."]');
        $this->post('/v1/users', ['password' => $password, 'email' => $email])
            ->seeJsonStructure([
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
        $response = $this->call('POST', '/v1/users', ['password' => $password, 'email' => $email]);
        $token = json_decode($response->getContent(), true)['token'];
        $response = $this->call('GET', '/v1/users/me?token='.$token, [], [], [], []);
        $this->seeJsonStructure([
            'id',
            'email',
            'phone',
            'created_at',
            'updated_at',
            'identifier',
        ], json_decode($response->getContent(), true));
        $response = $this->call('GET', '/v1/debug', [], [], [], ['HTTP_Authorization' => 'Bearer: '.$token]);
        $this->seeJsonStructure([
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'created_at',
            'updated_at',
            'identifier',
        ], json_decode($response->getContent(), true));
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
        $this->call('POST', '/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);
        $this->post('/v1/auth', ['email' => $email, 'password' => $password])
            ->seeJsonStructure(['token']);
        $this->post('/v1/auth', [])
            ->see('["The email field is required.","The password field is required."]');
        $this->post('/v1/auth', ['email' => $email, 'password' => $password.'#'])
            ->seeJsonEquals([
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
        $this->post('/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->seeJsonEquals([
                 'error' => 'applications are not open',
             ]);
        config(['app.phase' => 3]);
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email])
            ->seeJsonStructure(['token']);
    }

    public function testConfirmationCode()
    {
        $faker = Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users', ['password' => $password, 'email' => $email]);
        $user = User::where('email', $email)->first();
        $this->seeInDatabase('users', ['email' => $email, 'confirmed' => 0, 'confirmation_code' => $user->confirmation_code]);
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->seeJsonEquals([
                 'success' => 'Email Confirmed',
             ]);
        $this->get('/v1/users/verify/'.$user->confirmation_code)
            ->seeJsonEquals([
                 'success' => 'Email Confirmed',
             ]);
        $this->get('/v1/users/verify/'.$faker->uuid)
            ->seeJsonEquals([
                'error' => 'Invalid Code',
            ]);
        $this->get('/v1/users/verify/')
            ->seeJsonEquals([
                'error' => 'Code Required',
            ]);
    }
}
