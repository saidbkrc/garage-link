<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Command;
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

        $this->mqttService->sendGroupCommand($command, $request->params ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Grup komutu gönderildi',
            'data' => [
                'command' => $request->command_slug,
            ],
        ]);
    }
}
