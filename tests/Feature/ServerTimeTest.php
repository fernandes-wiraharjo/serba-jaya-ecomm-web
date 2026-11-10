<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_server_time_in_the_configured_timezone(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo('2026-11-10 00:00:00');

        $this->signedApi('GET', route('api.server-time'))
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Server time retrieved.',
                'data' => [
                    'iso8601' => '2026-11-10T07:00:00+07:00',
                    'timezone' => 'Asia/Jakarta',
                    'offset' => '+07:00',
                    'timestamp' => 1794268800,
                ],
                'errors' => null,
            ]);
    }
}
