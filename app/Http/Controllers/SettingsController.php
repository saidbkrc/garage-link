<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::guard('dealer')->user();

        return Inertia::render('Settings/Index', [
            'dealer' => [
                'id'         => $user->dealer->id,
                'name'       => $user->dealer->name,
                'created_at' => $user->dealer->created_at?->toISOString(),
            ],
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:dealer_users,email,' . $user->id,
        ], [
            'name.required' => 'Ad alanı zorunludur',
            'email.unique'  => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Profil bilgileri güncellendi');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Mevcut şifre gereklidir',
            'password.min'              => 'Yeni şifre en az 8 karakter olmalıdır',
            'password.confirmed'        => 'Şifre tekrarı eşleşmiyor',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Mevcut şifre hatalı']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->back()->with('success', 'Şifre başarıyla değiştirildi');
    }
}
