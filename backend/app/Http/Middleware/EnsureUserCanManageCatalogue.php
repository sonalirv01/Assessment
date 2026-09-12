<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageCatalogue
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canManageCatalogue()) {
            return response()->json([
                'message' => 'This action is restricted to admin or manager users.',
            ], 403);
        }

        return $next($request);
    }
}
