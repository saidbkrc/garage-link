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

        // Bu dealer'ın TÜM gateway'leri (online/offline fark etmez — badge UI'da değişir)
        $myGateways = Gateway::where('dealer_id', $dealerId)
            ->withCount('devices')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn($g) => [
                'id'                => $g->id,
                'gateway_id'        => $g->gateway_id,
                'name'              => $g->name,
                'is_online'         => $g->is_online,
                'is_actually_online' => $g->is_online
                    && $g->last_seen_at
                    && $g->last_seen_at->gte($onlineThreshold),
                'last_seen_at'      => $g->last_seen_at,
                'devices_count'     => $g->devices_count,
            ]);

        // Sahiplenilmemiş TÜM gateway'ler (is_online filtresi yok — DB'de kayıt varsa göster)
        $unclaimedGateways = Gateway::whereNull('dealer_id')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn($g) => [
                'id'                => $g->id,
                'gateway_id'        => $g->gateway_id,
                'is_online'         => $g->is_online,
                'is_actually_online' => $g->is_online
                    && $g->last_seen_at
                    && $g->last_seen_at->gte($onlineThreshold),
                'last_seen_at'      => $g->last_seen_at,
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
