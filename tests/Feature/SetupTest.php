<?php

namespace Tests\Feature;

use Tests\TestCase;

class SetupTest extends TestCase
{
    /**
     * A basic test to check that views are rendering correctly.
     *
     * @return void
     */
    public function testInstall()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('BoilerMake');
    }

    /**
     * A basic test to check that the API route is working.
     *
     * @return void
     */
    public function testAPICalls()
    {
        $response = $this->call('GET', '/v1/ping');
        $response->assertStatus(200);
        $response->assertJsonFragment(['pong']);
    }

    public function test404()
    {
        $response = $this->call('GET', '/v1/missingroute');
        $response->assertStatus(404);
    }
}
