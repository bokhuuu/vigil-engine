<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSecret
{
    /**
     * Timing-safe webhook secret check. Fails closed: if no secret
     * is configured, every request is rejected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('vigil.webhook.secret');
        $given = (string) ($request->header('X-Airtable-Hmac-SHA256') ?? $request->query('secret', ''));

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
