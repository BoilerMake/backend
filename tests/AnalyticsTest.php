<?php


class AnalyticsTest extends TestCase
{
    /**
     * Test that AnalyticsEvents are being recorded corrently
     *
     * @return void
     */
    public function testSendEvent()
    {
        $faker = Faker\Factory::create();
        $eventName = "test-event-".$faker->sha256;
        $url = $faker->url;
        $response = $this->call('PUT', '/v1/analytics/event', ['name' => $eventName,'meta'=>['url'=>$url,'ua'=>'ua','client'=>'c','referrer'=>$url]]);
        $response->assertJsonFragment(['ok']);
        $this->assertDatabaseHas('analytics_events',['name' => $eventName,'url'=>$url]);
    }

    /**
     * Tests overriding the IP
     */
    public function testOverrideIP() {
        $faker = Faker\Factory::create();
        $eventName = "test-event-".$faker->sha256;
        $ip = $faker->ipv4;
        $response = $this->call('PUT', '/v1/analytics/event', ['name' => $eventName,'meta'=>['ip'=>$ip]]);
        $response->assertJsonFragment(['ok']);
        $this->assertDatabaseHas('analytics_events',['name' => $eventName,'ip'=>$ip]);
    }
}
