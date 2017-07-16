<?php

namespace Tests\Feature;

use Tests\TestCase;

class MiscTest extends TestCase
{
    public function testGetEvents()
    {
        //TODO: flush out
        $response = $this->json('GET', '/v1/events', []);
        $response->assertStatus(200);
    }

    public function testGetannouncements()
    {
        //TODO: flush out
        $response = $this->json('GET', '/v1/announcements', []);
        $response->assertStatus(200);
    }

    public function testGetActivity()
    {
        //TODO: flush out
        $response = $this->json('GET', '/v1/activity', []);
        $response->assertStatus(200);
    }
}
