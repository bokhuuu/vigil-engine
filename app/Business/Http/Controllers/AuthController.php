<?php

declare(strict_types=1);

namespace App\Business\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('business.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('business')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('business.dashboard'));
        }

        return back()->withErrors([
            'email' => 'ელ-ფოსტა ან პაროლი არასწორია.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('business')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('business.login');
    }
}
