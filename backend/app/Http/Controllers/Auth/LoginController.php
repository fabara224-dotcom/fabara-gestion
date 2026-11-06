<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt([...$data, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-mail ou mot de passe incorrect.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return redirect()->intended($user->isSuperAdmin() ? route('admin.tenants.index') : route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
