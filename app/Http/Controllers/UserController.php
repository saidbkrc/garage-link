<?php

namespace App\Http\Controllers;

use App\Models\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $user     = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        $users = DealerUser::where('dealer_id', $dealerId)
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'is_active'  => $u->is_active,
                'is_current' => $u->id === $user->id,
                'created_at' => $u->created_at?->toISOString(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'user'  => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user     = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        if ($user->role !== 'admin') {
            abort(403, 'Sadece yöneticiler kullanıcı ekleyebilir');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('dealer_users', 'email')],
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,technician,viewer',
        ], [
            'name.required'     => 'Ad alanı zorunludur',
            'email.required'    => 'E-posta alanı zorunludur',
            'email.unique'      => 'Bu e-posta adresi zaten kayıtlı',
            'password.min'      => 'Şifre en az 8 karakter olmalıdır',
            'role.in'           => 'Geçersiz rol',
        ]);

        DealerUser::create([
            'dealer_id' => $dealerId,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Kullanıcı başarıyla eklendi');
    }

    public function update(Request $request, DealerUser $user)
    {
        $currentUser = Auth::guard('dealer')->user();

        if ($user->dealer_id !== $currentUser->dealer_id) {
            abort(403);
        }

        if ($currentUser->role !== 'admin' && $currentUser->id !== $user->id) {
            abort(403, 'Sadece kendi bilgilerinizi düzenleyebilirsiniz');
        }

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'email', Rule::unique('dealer_users', 'email')->ignore($user->id)],
            'password' => 'sometimes|nullable|string|min:8',
            'role'     => 'sometimes|in:admin,technician,viewer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'email', 'role', 'is_active']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Kullanıcı güncellendi');
    }

    public function destroy(DealerUser $user)
    {
        $currentUser = Auth::guard('dealer')->user();

        if ($user->dealer_id !== $currentUser->dealer_id) {
            abort(403);
        }

        if ($user->id === $currentUser->id) {
            return redirect()->back()->with('error', 'Kendinizi silemezsiniz');
        }

        if ($currentUser->role !== 'admin') {
            abort(403, 'Sadece yöneticiler kullanıcı silebilir');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Kullanıcı silindi');
    }
}
