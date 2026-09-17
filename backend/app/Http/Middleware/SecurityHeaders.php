<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response.
 *
 * This is a pure JSON API — nothing it returns should ever be framed,
 * sniffed as a different content type, or treated as a page with its own
 * script/style origins — so the policy here is deliberately locked down
 * rather than tuned for rendering content.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        return $response;
    }
}
