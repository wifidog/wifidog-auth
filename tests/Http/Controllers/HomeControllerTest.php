<?php

namespace Tests\Http\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;
use Meta;

class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)
            ->withSession([
                'gw_address' => '192.168.199.1',
                'gw_port' => '2060',
            ])->get('/home');
        $response->assertStatus(200)->assertViewHas('wifidog_uri');
        $wifidog_token = Meta::get('wifidog-token');
        $this->assertNotEmpty($wifidog_token);
    }

    public function testIndexWithoutWifidog()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $this->assertEmpty(Meta::get('wifidog-token'));
    }
}
