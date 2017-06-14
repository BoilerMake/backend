<?php


class InterestTest extends TestCase
{
    /**
     * Test that user interest signups are working.
     *
     * @return void
     */
    public function testInterestSignup()
    {
        $faker = Faker\Factory::create();
        $email = $faker->email;
        $this->post('/v1/interest/signup', ['email' => $email])
            ->assertJsonFragment([
                 'success' => true,
                 'data' => 'all signed up!',
             ]);
        $this->assertDatabaseHas('interest_signups', ['email' => $email]);
    }

    /**
     * Test that duplicate email results in an error.
     *
     * @return void
     */
    public function testDoubleInterestSignup()
    {
        $faker = Faker\Factory::create();
        $email = $faker->email;
        $this->post('/v1/interest/signup', ['email' => $email])
            ->assertJsonFragment([
                 'success' => true,
                 'data' => 'all signed up!',
             ]);
        $this->assertDatabaseHas('interest_signups', ['email' => $email]);
        $this->post('/v1/interest/signup', ['email' => $email])
            ->assertJsonFragment([
                 'success' =>  false,
                 'message' => 'you were already signed up!',
             ]);
    }

    /**
     * Test that duplicate email results in an error.
     *
     * @return void
     */
    public function testInvalidEmailSignup()
    {
        $this->post('/v1/interest/signup', ['email' => 'Abc.example.com'])
            ->assertJsonFragment([
                 'success' => false,
                 'message' => 'email is not valid!',
             ]);
        $this->post('/v1/interest/signup', ['email' => 'A@b@c@example.com'])
            ->assertJsonFragment([
                 'success' => false,
                 'message' => 'email is not valid!',
             ]);
        $this->post('/v1/interest/signup', ['email' => 'a"b(c)d,e:f;gi[j\k]l@example.com'])
            ->assertJsonFragment([
                 'success' => false,
                 'message' => 'email is not valid!',
             ]);
        $this->post('/v1/interest/signup', ['email' => 'this is"not\allowed@example.com'])
            ->assertJsonFragment([
                 'success' => false,
                 'message' => 'email is not valid!',
             ]);
    }
}
