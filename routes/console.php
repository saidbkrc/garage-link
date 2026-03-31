<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Zamanlanmış Görev Çalıştırıcı
|--------------------------------------------------------------------------
| Her dakika çalışır, aktif schedule kayıtlarından bugünün gün ve saatine
| uyanları bulup MQTT komutunu veya senaryo aksiyonlarını tetikler.
*/
Schedule::call(function () {
    $now     = now();
    $dayKey  = strtolower($now->format('D')); // "mon", "tue", "wed"...
    $timeStr = $now->format('H:i');           // "08:30"

    $schedules = \App\Models\Schedule::where('is_active', true)
        ->where('time', $timeStr)
        ->with(['device', 'scene'])
        ->get()
        ->filter(function ($schedule) use ($dayKey) {
            return in_array($dayKey, $schedule->days ?? []);
        });

    if ($schedules->isEmpty()) {
        return;
    }

    /** @var \App\Services\MqttService $mqttService */
    $mqttService = app(\App\Services\MqttService::class);

    foreach ($schedules as $schedule) {
        try {
            // Cihaz komutu
            if ($schedule->device_id && $schedule->command_slug && $schedule->device) {
                $mqttService->sendCommandBySlug(
                    $schedule->device,
                    $schedule->command_slug,
                    $schedule->params ?? [],
                    null
                );
                Log::info("Schedule #{$schedule->id} tetiklendi: cihaz={$schedule->device->name}, komut={$schedule->command_slug}");
            }
            // Senaryo
            elseif ($schedule->scene_id && $schedule->scene) {
                $slugMap = [
                    'turn_on'        => 'turn_on',
                    'turn_off'       => 'turn_off',
                    'toggle'         => 'toggle',
                    'set_brightness' => 'brightness',
                    'set_color'      => 'color',
                    'color'          => 'color',
                ];

                $executed = 0;
                foreach ($schedule->scene->actions ?? [] as $action) {
                    $device = \App\Models\Device::find($action['device_id'] ?? null);
                    if (!$device || !$device->is_active) continue;
                    $slug = $slugMap[$action['command'] ?? ''] ?? null;
                    if ($slug) {
                        $mqttService->sendCommandBySlug($device, $slug, $action['params'] ?? [], null);
                        $executed++;
                    }
                }
                Log::info("Schedule #{$schedule->id} tetiklendi: senaryo={$schedule->scene->name}, {$executed} cihaz");
            }

            $schedule->update(['last_triggered_at' => $now]);

        } catch (\Exception $e) {
            Log::error("Schedule #{$schedule->id} hatası: " . $e->getMessage());
        }
    }
})->everyMinute()->name('run-schedules')->withoutOverlapping();
