<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SceneController extends Controller
{
    /**
     * Senaryo listesi sayfası
     */
    public function index()
    {
        $user = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        $scenes = Scene::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($scene) {
                return [
                    'id' => $scene->id,
                    'name' => $scene->name,
                    'icon' => $scene->icon,
                    'color' => $scene->color ?? 'blue',
                    'actions' => $scene->actions ?? [],
                ];
            });

        $devices = Device::with(['deviceType', 'room'])
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'room' => $device->room?->name,
                    'icon' => $device->deviceType?->icon,
                    'category' => $device->deviceType?->category,
                ];
            });

        // Stats (mock data for now)
        $stats = [
            'mostUsed' => $scenes->first() ? [
                'name' => $scenes->first()['name'],
                'count' => 42
            ] : null,
            'lastRun' => $scenes->count() > 1 ? [
                'name' => $scenes->skip(1)->first()['name'],
                'time' => '10 dk önce'
            ] : null,
        ];

        return Inertia::render('Scenes/Index', [
            'scenes' => $scenes,
            'devices' => $devices,
            'stats' => $stats,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    /**
     * Yeni senaryo oluştur
     */
    public function store(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'actions' => 'required|array|min:1',
            'actions.*.device_id' => 'required|exists:devices,id',
            'actions.*.command' => 'required|string',
            'actions.*.params' => 'nullable|array',
        ], [
            'name.required' => 'Senaryo adı gerekli',
            'actions.required' => 'En az bir aksiyon ekleyin',
            'actions.min' => 'En az bir aksiyon ekleyin',
        ]);

        // Get max order
        $maxOrder = Scene::where('dealer_id', $user->dealer_id)->max('order') ?? 0;

        Scene::create([
            'dealer_id' => $user->dealer_id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'play_circle',
            'color' => $validated['color'] ?? 'blue',
            'actions' => $validated['actions'],
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Senaryo başarıyla oluşturuldu');
    }

    /**
     * Senaryo güncelle
     */
    public function update(Request $request, Scene $scene)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($scene->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'actions' => 'required|array|min:1',
            'actions.*.device_id' => 'required|exists:devices,id',
            'actions.*.command' => 'required|string',
            'actions.*.params' => 'nullable|array',
        ]);

        $scene->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'actions' => $validated['actions'],
        ]);

        return redirect()->back()->with('success', 'Senaryo güncellendi');
    }

    /**
     * Senaryo sil
     */
    public function destroy(Scene $scene)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($scene->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $scene->delete();

        return redirect()->back()->with('success', 'Senaryo silindi');
    }

    /**
     * Senaryo çalıştır
     */
    public function run(Scene $scene)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($scene->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $actions = $scene->actions ?? [];
        $results = [];

        foreach ($actions as $action) {
            $device = Device::find($action['device_id']);

            if (!$device || $device->dealer_id !== $user->dealer_id) {
                continue;
            }

            // Execute command based on type
            $command = $action['command'];
            $params = $action['params'] ?? [];

            // Update device state based on command
            $newState = $device->current_state ?? [];

            switch ($command) {
                case 'turn_on':
                    $newState['power'] = true;
                    break;
                case 'turn_off':
                    $newState['power'] = false;
                    break;
                case 'set_brightness':
                    $newState['power'] = true;
                    $newState['brightness'] = $params['brightness'] ?? 100;
                    break;
                case 'set_color':
                    $newState['power'] = true;
                    $newState['color'] = $params['color'] ?? '#FFFFFF';
                    break;
                case 'set_temperature':
                    $newState['power'] = true;
                    $newState['target_temp'] = $params['temperature'] ?? 22;
                    break;
                case 'set_position':
                    $newState['position'] = $params['position'] ?? 0;
                    break;
                case 'lock':
                    $newState['locked'] = true;
                    break;
                case 'unlock':
                    $newState['locked'] = false;
                    break;
                case 'arm':
                    $newState['armed'] = true;
                    break;
                case 'disarm':
                    $newState['armed'] = false;
                    break;
            }

            $device->updateState($newState);

            // TODO: Send MQTT command to actual device
            // $this->mqttService->publish($device->mqtt_topic, $command, $params);

            $results[] = [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'command' => $command,
                'success' => true,
            ];
        }

        return redirect()->back()->with('success', "Senaryo çalıştırıldı: {$scene->name}");
    }
}
