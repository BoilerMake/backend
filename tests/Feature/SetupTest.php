<?php


namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        $response->assertSee('pong');
    }

    public function testSamiDocs()
    {
        $response = $this->call('GET', '/docs');
//        Log::info(exec('composer run-script docs'));
    }
}
