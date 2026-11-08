<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActsAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = session('vigil_role');

        if (! in_array($role, ['manager', 'uploader', 'viewer'], true)) {
            return redirect()->route('acts.login');
        }

        $request->attributes->set('vigil_role', $role);

        return $next($request);
    }
}
