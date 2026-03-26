<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GatewayController extends Controller
{
    public function index()
    {
        $user = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        // Son 10 dakikada mesaj atmış gateway'ler "gerçekten online" sayılır
        $onlineThreshold = now()->subMinutes(10);

        // Bu dealer'ın ONLINE gateway'leri (cihaz sayısı ile)
        $myGateways = Gateway::where('dealer_id', $dealerId)
            ->where('is_online', true)
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->withCount('devices')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn($g) => [
                'id'             => $g->id,
                'gateway_id'     => $g->gateway_id,
                'name'           => $g->name,
                'is_online'      => $g->is_online,
                'last_seen_at'   => $g->last_seen_at,
                'devices_count'  => $g->devices_count,
            ]);

        // Sahiplenilmemiş ama online görünen gateway'ler
        $unclaimedGateways = Gateway::whereNull('dealer_id')
            ->where('is_online', true)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn($g) => [
                'id'           => $g->id,
                'gateway_id'   => $g->gateway_id,
                'is_online'    => $g->is_online,
                'last_seen_at' => $g->last_seen_at,
            ]);

        return Inertia::render('Gateways/Index', [
            'myGateways'        => $myGateways,
            'unclaimedGateways' => $unclaimedGateways,
            'user'              => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'dealer_id'   => $user->dealer_id,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }
}
