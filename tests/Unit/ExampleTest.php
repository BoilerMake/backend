<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }
    public function testGithubExtraction() {
        $this->assertEquals(User::extractUsernameFromURL("nickysemenza"),"nickysemenza");
        $this->assertEquals(User::extractUsernameFromURL("github.com/nickysemenza"),"nickysemenza");
        $this->assertEquals(User::extractUsernameFromURL("http://linkedin.com/in/nickysemenza"),"nickysemenza");
        $this->assertEquals(User::extractUsernameFromURL("https://github.com/nickysemenza"),"nickysemenza");
        $this->assertEquals(User::extractUsernameFromURL(""),null);
        $this->assertEquals(User::extractUsernameFromURL(null),null);
    }
}
