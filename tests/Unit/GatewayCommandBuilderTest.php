<?php

namespace Tests\Unit;

use App\Services\GatewayCommandBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Builder çıktıları "PIYA döküman"daki örnek JSON'larla BİREBİR eşleşmeli.
 * Donanım olmadan firmware sözleşmesini doğrulamanın yolu budur.
 */
class GatewayCommandBuilderTest extends TestCase
{
    private GatewayCommandBuilder $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->b = new GatewayCommandBuilder();
    }

    public function test_rgb_control_matches_doc(): void
    {
        $this->assertSame([
            'RGB_control' => [
                'address'    => '28D9C2FEFFEEF648',
                'color'      => 'rgb(255,100,255)',
                'brightness' => 20,
                'endpoint'   => 1,
            ],
        ], $this->b->rgb('28D9C2FEFFEEF648', 'rgb(255,100,255)', 20, 1));
    }

    public function test_rgb_control_omits_optional_fields(): void
    {
        $this->assertSame([
            'RGB_control' => [
                'address' => '28D9C2FEFFEEF648',
                'color'   => 'rgb(0,0,0)',
            ],
        ], $this->b->rgb('28D9C2FEFFEEF648', 'rgb(0,0,0)'));
    }

    public function test_brightness_zero_is_preserved(): void
    {
        // brightness=0 geçerli bir değer — atlanmamalı
        $payload = $this->b->rgb('AABB', 'rgb(1,1,1)', 0);
        $this->assertArrayHasKey('brightness', $payload['RGB_control']);
        $this->assertSame(0, $payload['RGB_control']['brightness']);
    }

    public function test_color_temperature_matches_doc(): void
    {
        $this->assertSame([
            'CT_control' => [
                'address'           => '28D9C2FEFFEEF648',
                'color_temperature' => 10,
                'brightness'        => 70,
                'endpoint'          => 2,
            ],
        ], $this->b->colorTemperature('28D9C2FEFFEEF648', 10, 70, 2));
    }

    public function test_dim_control_matches_doc(): void
    {
        $this->assertSame([
            'DIM_control' => [
                'address'  => '28D9C2FEFFEEF648',
                'commands' => [
                    ['endpoint' => 1, 'brightness' => 100],
                    ['endpoint' => 2, 'brightness' => 0],
                    ['endpoint' => 3, 'brightness' => 0],
                    ['endpoint' => 4, 'brightness' => 70],
                ],
            ],
        ], $this->b->dim('28D9C2FEFFEEF648', [
            ['endpoint' => 1, 'brightness' => 100],
            ['endpoint' => 2, 'brightness' => 0],
            ['endpoint' => 3, 'brightness' => 0],
            ['endpoint' => 4, 'brightness' => 70],
        ]));
    }

    public function test_switch_control_matches_doc(): void
    {
        $this->assertSame([
            'switch_control' => [
                'address'     => '404CCAFFFE5FD670',
                'read_status' => true,
                'commands'    => [
                    ['endpoint' => 1, 'state' => 'on'],
                    ['endpoint' => 2, 'state' => 'off'],
                ],
            ],
        ], $this->b->switchControl('404CCAFFFE5FD670', [
            ['endpoint' => 1, 'state' => 'on'],
            ['endpoint' => 2, 'state' => 'off'],
        ], true));
    }

    public function test_switch_one_shortcut(): void
    {
        $this->assertSame([
            'switch_control' => [
                'address'  => '404CCAFFFE5FD670',
                'commands' => [
                    ['endpoint' => 3, 'state' => 'on'],
                ],
            ],
        ], $this->b->switchOne('404CCAFFFE5FD670', 3, true));
    }

    public function test_read_switch_state_matches_doc(): void
    {
        $this->assertSame([
            'read_switch_state' => [
                'address'  => '404CCAFFFE5FD670',
                'endpoint' => 1,
            ],
        ], $this->b->readSwitchState('404CCAFFFE5FD670', 1));
    }

    public function test_schedule_matches_doc(): void
    {
        $this->assertSame([
            'SCHED_control' => [
                'address'  => '08D9C2FEFFEEF648',
                'commands' => [
                    ['hour' => 16, 'minute' => 36, 'endpoint' => 1, 'action' => 'off', 'name' => 'deneme1', 'enabled' => true],
                    ['hour' => 16, 'minute' => 37, 'endpoint' => 1, 'action' => 'on', 'name' => 'deneme2', 'enabled' => true],
                ],
            ],
        ], $this->b->schedule('08D9C2FEFFEEF648', [
            ['hour' => 16, 'minute' => 36, 'endpoint' => 1, 'action' => 'off', 'name' => 'deneme1', 'enabled' => true],
            ['hour' => 16, 'minute' => 37, 'endpoint' => 1, 'action' => 'on', 'name' => 'deneme2', 'enabled' => true],
        ]));
    }

    public function test_scheduler_remove_and_enable(): void
    {
        $this->assertSame(['scheduler_remove' => 3], $this->b->schedulerRemove(3));
        $this->assertSame(['scheduler_enable' => ['id' => 3, 'enabled' => true]], $this->b->schedulerEnable(3, true));
    }

    public function test_gateway_control_matches_doc(): void
    {
        $this->assertSame([
            'GATEWAY_control' => [
                'get_gateway_info' => true,
                'get_ip'           => true,
                'get_health'       => true,
                'get_time'         => true,
            ],
        ], $this->b->gatewayControl([
            'get_gateway_info' => true,
            'get_ip'           => true,
            'get_health'       => true,
            'get_time'         => true,
        ]));
    }

    public function test_set_time_matches_doc(): void
    {
        $this->assertSame([
            'set_time' => ['hour' => 12, 'minute' => 34, 'second' => 0, 'day' => 1, 'month' => 1, 'year' => 2026],
        ], $this->b->setTime(12, 34, 0, 1, 1, 2026));
    }

    public function test_scan_helpers(): void
    {
        $this->assertSame(['scan_mode' => 60], $this->b->scanMode(60));
        $this->assertSame(['get_scan_results' => true], $this->b->getScanResults());
        $this->assertSame(['add_scanned_device' => 'A1B2C3D4E5F60718'], $this->b->addScannedDevice('A1B2C3D4E5F60718'));
        $this->assertSame(['add_all_scanned_devices' => true], $this->b->addAllScannedDevices());
        $this->assertSame(['get_devicelist' => true], $this->b->getDeviceList());
    }

    public function test_factory_reset_and_set_device_name(): void
    {
        $this->assertSame(
            ['piya_factory_reset' => true, 'ieee_addr' => '404CCAFFFE5FD670'],
            $this->b->factoryReset('404CCAFFFE5FD670')
        );
        $this->assertSame(
            ['set_device_name' => ['ieee_addr' => '404CCAFFFE5FD670', 'name' => 'salon']],
            $this->b->setDeviceName('404CCAFFFE5FD670', 'salon')
        );
    }

    public function test_switch_control_json_encodes_as_expected(): void
    {
        $json = json_encode($this->b->switchOne('404CCAFFFE5FD670', 1, false));
        $this->assertSame(
            '{"switch_control":{"address":"404CCAFFFE5FD670","commands":[{"endpoint":1,"state":"off"}]}}',
            $json
        );
    }
}
