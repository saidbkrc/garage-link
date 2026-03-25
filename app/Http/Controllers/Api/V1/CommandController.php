<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Gateway;
use App\Services\MqttService;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function index(Request $request)
    {
        $query = Command::where('is_active', true)->orderBy('order');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('device_type_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('device_type_id', $request->device_type_id)
                    ->orWhereNull('device_type_id');
            });
        }

        $commands = $query->get();

        return response()->json([
            'success' => true,
            'data' => $commands,
        ]);
    }

    public function sendGroupCommand(Request $request)
    {
        $request->validate([
            'command_slug' => 'required|string|exists:commands,slug',
            'params' => 'sometimes|array',
        ]);

        $command = Command::where('slug', $request->command_slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Dealer'ın online gateway'ini bul (yoksa herhangi birini al)
        $dealerId = $request->user()->dealer_id;
        $gateway = Gateway::where('dealer_id', $dealerId)
            ->orderByDesc('is_online')
            ->orderByDesc('last_seen_at')
            ->first();

        $this->mqttService->sendGroupCommand($command, $request->params ?? [], $gateway?->gateway_id);

        return response()->json([
            'success' => true,
            'message' => 'Grup komutu gönderildi',
            'data' => [
                'command' => $request->command_slug,
            ],
        ]);
    }
}
