<?php


class UserTest extends TestCase
{
    public function getToken()
    {
        $faker = Faker\Factory::create();
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $password = $faker->password;
        $email = $faker->email;
        $response = $this->call('POST', '/v1/users', ['first_name' => $first_name, 'last_name' => $last_name, 'password' => $password, 'email' => $email]);

        return json_decode($response->getContent(), true)['token'];
    }

    /**
     * Test that user interest signups are working.
     *
     * @return void
     */
    public function testDefaultApplication()
    {
        $response = $this->call('GET', '/v1/users/me/application', [], [], [], ['HTTP_Authorization' => 'Bearer: '.$this->getToken()]);
    }
}
