<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSession
{
    public function handle(Request $request, Closure $next, string $role = 'staff')
    {
        $user = session('user');

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $userRole = is_object($user) ? $user->role : ($user['role'] ?? null);

        // Wrong role → redirect to their actual dashboard instead of aborting
        if ($userRole !== $role) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
            }
            return redirect()->route(match($userRole) {
                'admin' => 'admin.dashboard',
                'staff' => 'staff.dashboard',
                default => 'login',
            });
        }

        return $next($request);
    }
}