<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * Tek alarmı okundu olarak işaretle
     */
    public function markRead(Alert $alert)
    {
        $user = Auth::guard('dealer')->user();

        if ($alert->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $alert->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    /**
     * Tüm okunmamış alarmları okundu olarak işaretle
     */
    public function markAllRead()
    {
        $user = Auth::guard('dealer')->user();

        Alert::where('dealer_id', $user->dealer_id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }
}
