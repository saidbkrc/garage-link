<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debug MQTT test ucu yalnızca 'local' ortamda kayıt olur.
     * Testler 'testing' ortamında koştuğu için bu route bulunmamalı (404).
     * Bu, üretimde de yüklenmediğini garanti eder.
     */
    public function test_mqtt_debug_route_not_registered_outside_local(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->get('/test/mqtt/turn_on')->assertNotFound();
    }
}
