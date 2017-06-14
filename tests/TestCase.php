<?php

use App\Models\User;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function getNewUser()
    {
        $faker = Faker\Factory::create();
        $password = $faker->password;
        $email = $faker->email;
        $this->post('/v1/users/register', ['password' => $password, 'email' => $email]);
        $user = User::where('email', $email)->first();

        return $user;
    }
}
