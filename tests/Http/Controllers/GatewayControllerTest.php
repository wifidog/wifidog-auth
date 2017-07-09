<?php

namespace Tests\Http\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Gateway;

class GatewayControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testPing()
    {
        $params = [
            'gw_id' => $this->faker->word(),
            'sys_uptime' => rand(1, 999),
            'sys_memfree' => rand(1, 999),
            'sys_load' => $this->faker->randomFloat(2, 0, 100),
            'wifidog_uptime' => rand(1, 999),
        ];
        $response = $this->call('GET', '/ping/', $params);

        $response->assertStatus(200);
        $this->assertEquals('Pong', $response->getContent());

        $gateways = Gateway::get();
        $this->assertEquals(1, count($gateways));
        $gateway = $gateways->first()->toArray();
        $tmp = $params;
        $tmp['id'] = $params['gw_id'];
        unset($tmp['gw_id']);
        foreach ($tmp as $k => $v) {
            $this->assertEquals($v, $gateway[$k]);
        }
    }
}
