<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->getUser();
        $password = $request->getPassword();

        if (
            $user !== config('vigil.dashboard.user') ||
            $password !== config('vigil.dashboard.password')
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Vigil Dashboard"',
            ]);
        }

        return $next($request);
    }
}
