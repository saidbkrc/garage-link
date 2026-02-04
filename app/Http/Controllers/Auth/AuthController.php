<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Login sayfasını göster
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Login işlemi
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'E-posta adresi gerekli',
            'email.email' => 'Geçerli bir e-posta adresi girin',
            'password.required' => 'Şifre gerekli',
            'password.min' => 'Şifre en az 6 karakter olmalı',
        ]);

        // Kullanıcıyı bul
        $user = DealerUser::where('email', $request->email)->first();

        // Kullanıcı yoksa veya şifre yanlışsa
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya şifre hatalı',
            ]);
        }

        // Kullanıcı aktif değilse
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Hesabınız devre dışı bırakılmış',
            ]);
        }

        // Bayi aktif değilse
        if (!$user->dealer->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Bayiniz devre dışı bırakılmış',
            ]);
        }

        // Giriş yap
        Auth::guard('dealer')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * Logout işlemi
     */
    public function logout(Request $request)
    {
        Auth::guard('dealer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
