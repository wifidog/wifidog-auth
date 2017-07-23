<?php

namespace Tests\Http\Controllers;

use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    public function testPing()
    {
        foreach (['denied', 'activate', 'failed_validation'] as $message) {
            $response = $this->call('GET', '/messages/', [
                'message' => $message,
            ]);
        }

        $response->assertStatus(200)->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
