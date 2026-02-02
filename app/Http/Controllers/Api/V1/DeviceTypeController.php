<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceType;
use Illuminate\Http\Request;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $types = DeviceType::with(['commands' => function ($query) {
            $query->where('is_active', true)->orderBy('order');
        }])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    public function show($id)
    {
        $type = DeviceType::with(['commands' => function ($query) {
            $query->where('is_active', true)->orderBy('order');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $type,
        ]);
    }
}
