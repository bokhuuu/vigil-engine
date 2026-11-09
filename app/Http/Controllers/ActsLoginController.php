<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActsLoginController
{
    public function showLogin(): View|RedirectResponse
    {
        if (in_array(session('vigil_role'), ['manager', 'uploader', 'viewer'], true)) {
            return redirect()->route('dashboard');
        }

        return view('acts.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $user = (string) $request->input('username', '');
        $password = (string) $request->input('password', '');

        $role = $this->resolveRole($user, $password);

        if ($role === null) {
            return redirect()->route('acts.login')->withErrors(['credentials' => 'მომხმარებელი ან პაროლი არასწორია.']);
        }

        $request->session()->regenerate();
        session(['vigil_role' => $role]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('acts.login');
    }

    private function resolveRole(string $user, string $password): ?string
    {
        $credentials = [
            'manager' => [
                'user' => config('vigil.dashboard.user'),
                'password' => config('vigil.dashboard.password'),
            ],
            'uploader' => [
                'user' => config('vigil.dashboard.uploader_user'),
                'password' => config('vigil.dashboard.uploader_password'),
            ],
            'viewer' => [
                'user' => config('vigil.dashboard.viewer_user'),
                'password' => config('vigil.dashboard.viewer_password'),
            ],
        ];

        foreach ($credentials as $role => $creds) {
            if (
                $user === $creds['user']
                && $password === $creds['password']
            ) {
                return $role;
            }
        }

        return null;
    }
}
