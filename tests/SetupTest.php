<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetupTest extends TestCase
{
    /**
     * A basic test to check that views are rendering correctly
     *
     * @return void
     */
    public function testInstall()
    {
        $this->visit('/')->see('BoilerMake');
    }

    /**
     * A basic test to check that the API route is working
     *
     * @return void
     */
    public function testAPICalls()
    {
        $this->visit('/v1/ping')->see('pong');
    }
}
