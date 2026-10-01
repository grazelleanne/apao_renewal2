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
                return $this->preventCaching(
                    response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401)
                );
            }
            return $this->preventCaching(redirect()->route('login'));
        }

        $userRole = is_object($user) ? $user->role : ($user['role'] ?? null);

        // Wrong role → redirect to their actual dashboard instead of aborting
        $hasAccess = $userRole === $role
            || ($userRole === 'super_admin' && $role === 'admin');

        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return $this->preventCaching(
                    response()->json(['success' => false, 'message' => 'Access denied.'], 403)
                );
            }
            return $this->preventCaching(redirect()->route(match($userRole) {
                'super_admin' => 'admin.dashboard',
                'admin' => 'admin.dashboard',
                'staff' => 'staff.dashboard',
                default => 'login',
            }));
        }

        return $this->preventCaching($next($request));
    }

    /**
     * Authenticated content must never be restored from the browser cache after
     * logout. In particular, this prevents the back/forward cache from showing
     * a stale dashboard when the server-side session has already been destroyed.
     */
    private function preventCaching($response)
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $response;
    }
}
