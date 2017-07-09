<?php

namespace Tests\Http\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;
use Meta;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testAuthLostParams()
    {
        $params = [
            'stage' => 'login',
            'gw_id' => 'D4EE073700C2',
        ];
        $response = $this->call('GET', '/auth/', $params);

        $response->assertStatus(400);
        $this->assertEquals('Auth: -1', $response->getContent());
    }

    public function grantToken()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->withSession([
                'gw_address' => '192.168.199.1',
                'gw_port' => '2060',
            ])->get('/home');
        $response->assertViewHas('wifidog_uri');
        $wifidog_token = Meta::get('wifidog-token');
        $this->assertNotEmpty($wifidog_token);
        return $wifidog_token;
    }

    public function testLogin()
    {
        $params = [
            'stage' => 'login',
            'token' => $this->grantToken(),
            'gw_id' => 'D4EE073700C2',
        ];
        $response = $this->call('GET', '/auth/', $params);

        $response->assertStatus(200);
        $this->assertEquals('Auth: 1', $response->getContent());
    }

    public function testLoginWithBadToken()
    {
        $params = [
            'stage' => 'login',
            'token' => 'thisIsABadToken',
            'gw_id' => 'D4EE073700C2',
        ];
        $response = $this->call('GET', '/auth/', $params);

        $response->assertStatus(401);
        $this->assertEquals('Auth: 0', $response->getContent());
    }

    public function testCount()
    {
        $params = [
            'stage' => 'counters',
            'token' => $this->grantToken(),
            'gw_id' => 'D4EE073700C2',
            'incoming' => rand(1, 999),
            'outgoing' => rand(1, 999),
            'ip' => $this->faker->ipv4(),
            'mac' => $this->faker->macAddress(),
        ];
        $response = $this->call('GET', '/auth/', $params);

        $response->assertStatus(200);
        $this->assertEquals('Auth: 1', $response->getContent());
    }
}
