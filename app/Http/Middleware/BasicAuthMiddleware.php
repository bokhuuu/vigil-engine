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

        $isManager = $user === config('vigil.dashboard.user')
            && $password === config('vigil.dashboard.password');

        $isUploader = $user === config('vigil.dashboard.uploader_user')
            && $password === config('vigil.dashboard.uploader_password');

        if (! $isManager && ! $isUploader) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Vigil Dashboard"',
            ]);
        }

        $request->attributes->set('vigil_role', $isManager ? 'manager' : 'uploader');

        return $next($request);
    }
}
