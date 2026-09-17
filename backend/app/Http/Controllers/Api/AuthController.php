<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // The route's throttle:5,1 middleware keys by IP, which doesn't stop
        // a distributed credential-stuffing attempt that spreads guesses
        // for one account across many IPs. This keys by email specifically,
        // closing that gap independent of where the requests come from.
        $lockoutKey = 'login-attempts:'.mb_strtolower($credentials['email']);

        if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
            $seconds = RateLimiter::availableIn($lockoutKey);

            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        if (! Auth::once($credentials)) {
            RateLimiter::hit($lockoutKey, 60);

            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        RateLimiter::clear($lockoutKey);

        /** @var User $user */
        $user = Auth::user();

        // One active session per admin: drop any previous spa-login token
        // rather than letting them accumulate indefinitely (each of which
        // would otherwise stay valid until it individually expires).
        $user->tokens()->where('name', 'spa-login')->delete();

        $token = $user->createToken('spa-login')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }
}
