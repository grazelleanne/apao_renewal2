<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show login page.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'staff') {
                return redirect()->route('staff.dashboard');
            }

            if ($user->role === 'viewer') {
                return redirect()->route('viewer.dashboard');
            }

            Auth::logout();

            return redirect()->route('login');
        }

        return view('auth.login');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        $email = Str::lower(trim($request->input('email', '')));
        $password = $request->input('password', '');
        $remember = $request->boolean('remember');

        /*
        |--------------------------------------------------------------------------
        | Basic validation
        |--------------------------------------------------------------------------
        */

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 422);
        }

        if (empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Login rate limiting
        |--------------------------------------------------------------------------
        |
        | Maximum failed attempts: 5
        | Temporary lock:          5 minutes
        |
        | Email + IP are combined so the limiter is specific to the attempted
        | account and originating device/network.
        |
        */

        $maxAttempts = 5;
        $lockSeconds = 300;

        $throttleKey = 'login:' . $email . '|' . $request->ip();

        /*
        |--------------------------------------------------------------------------
        | Check if currently locked
        |--------------------------------------------------------------------------
        */

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $minutes = (int) ceil($seconds / 60);

            return response()->json([
                'success' => false,
                'message' => "Too many failed login attempts. Please try again in {$minutes} minute(s).",
                'retry_after' => $seconds,
            ], 429);
        }

        /*
        |--------------------------------------------------------------------------
        | Attempt authentication
        |--------------------------------------------------------------------------
        */

        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $remember)) {

            /*
            |--------------------------------------------------------------------------
            | Add failed attempt
            |--------------------------------------------------------------------------
            */

            RateLimiter::hit($throttleKey, $lockSeconds);

            $remaining = RateLimiter::remaining(
                $throttleKey,
                $maxAttempts
            );

            /*
            |--------------------------------------------------------------------------
            | Find attempted account for internal audit only
            |--------------------------------------------------------------------------
            |
            | We do NOT expose whether the email exists to the login user.
            |
            */

            $attemptedUser = DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Audit failed login
            |--------------------------------------------------------------------------
            */

            DB::table('audit_logs')->insert([
                'user_id' => $attemptedUser->id ?? null,
                'user_name' => $attemptedUser->name ?? $email,
                'user_role' => $attemptedUser->role ?? null,

                'action' => 'login_failed',

                'model_type' => null,
                'model_id' => null,
                'subject' => null,
                'old_values' => null,
                'new_values' => null,

                'description' => 'Failed login attempt for ' . $email . '.',

                'ip_address' => $request->ip(),
                'created_at' => Carbon::now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | If the fifth failed attempt was reached
            |--------------------------------------------------------------------------
            */

            if ($remaining <= 0) {

                DB::table('audit_logs')->insert([
                    'user_id' => $attemptedUser->id ?? null,
                    'user_name' => $attemptedUser->name ?? $email,
                    'user_role' => $attemptedUser->role ?? null,

                    'action' => 'login_temporarily_locked',

                    'model_type' => null,
                    'model_id' => null,
                    'subject' => null,
                    'old_values' => null,
                    'new_values' => null,

                    'description' =>
                        'Login temporarily blocked for 5 minutes after repeated failed login attempts.',

                    'ip_address' => $request->ip(),
                    'created_at' => Carbon::now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Too many failed login attempts. Login has been temporarily locked for 5 minutes.',
                ], 429);
            }

            return response()->json([
                'success' => false,

                // Do not reveal whether the email or account exists.
                'message' =>
                    "Invalid email or password. {$remaining} attempt(s) remaining.",
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Authentication successful
        |--------------------------------------------------------------------------
        */

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Account status check
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {

            /*
             * Correct password was supplied, so we can clear failed-password
             * attempts for this account/IP combination.
             */
            RateLimiter::clear($throttleKey);

            /*
            |--------------------------------------------------------------------------
            | Audit blocked login
            |--------------------------------------------------------------------------
            */

            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,

                'action' => 'login_blocked',

                'model_type' => null,
                'model_id' => null,
                'subject' => null,
                'old_values' => null,
                'new_values' => null,

                'description' =>
                    $user->name . ' attempted to log in using a deactivated account.',

                'ip_address' => $request->ip(),
                'created_at' => Carbon::now(),
            ]);

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' =>
                    'Your account has been deactivated. Please contact an administrator.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear failed attempts after successful login
        |--------------------------------------------------------------------------
        */

        RateLimiter::clear($throttleKey);

        /*
        |--------------------------------------------------------------------------
        | Regenerate session
        |--------------------------------------------------------------------------
        |
        | Helps protect against session fixation.
        |
        */

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Update last login time
        |--------------------------------------------------------------------------
        */

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'last_login_at' => Carbon::now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Audit successful login
        |--------------------------------------------------------------------------
        */

        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,

            'action' => 'login',

            'model_type' => null,
            'model_id' => null,
            'subject' => null,
            'old_values' => null,
            'new_values' => null,

            'description' =>
                $user->name . ' (' . $user->role . ') logged in.',

            'ip_address' => $request->ip(),
            'created_at' => Carbon::now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Determine dashboard
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'admin') {
            $redirectUrl = route('admin.dashboard');

        } elseif ($user->role === 'staff') {
            $redirectUrl = route('staff.dashboard');

        } elseif ($user->role === 'viewer') {
            $redirectUrl = route('viewer.dashboard');

        } else {

            /*
             * Unknown or unauthorized role.
             */

            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,

                'action' => 'login_invalid_role',

                'model_type' => null,
                'model_id' => null,
                'subject' => null,
                'old_values' => null,
                'new_values' => null,

                'description' =>
                    $user->name . ' authenticated but has an invalid system role.',

                'ip_address' => $request->ip(),
                'created_at' => Carbon::now(),
            ]);

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' =>
                    'Your account does not have permission to access the system.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Return successful response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => $redirectUrl,

            'user' => [
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Audit logout
        |--------------------------------------------------------------------------
        */

        if ($user) {
            DB::table('audit_logs')->insert([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,

                'action' => 'logout',

                'model_type' => null,
                'model_id' => null,
                'subject' => null,
                'old_values' => null,
                'new_values' => null,

                'description' => $user->name . ' logged out.',

                'ip_address' => $request->ip(),
                'created_at' => Carbon::now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Destroy authenticated session
        |--------------------------------------------------------------------------
        */

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Change logged-in user's password.
     */
    public function changePassword(Request $request)
    {
        $currentPassword = $request->input('current_password', '');
        $newPassword = $request->input('password', '');
        $confirmation = $request->input('password_confirmation', '');

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (empty($currentPassword)) {
            return back()->withErrors([
                'current_password' =>
                    'Current password is required.',
            ]);
        }

        if (empty($newPassword)) {
            return back()->withErrors([
                'password' =>
                    'New password is required.',
            ]);
        }

        if ($newPassword !== $confirmation) {
            return back()->withErrors([
                'password' =>
                    'Passwords do not match.',
            ]);
        }

        /*
         * You can increase this to 10 or 12 later if desired.
         */
        if (strlen($newPassword) < 8) {
            return back()->withErrors([
                'password' =>
                    'Password must be at least 8 characters.',
            ]);
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | Verify current password
        |--------------------------------------------------------------------------
        */

        if (!Hash::check($currentPassword, $user->password)) {
            return back()->withErrors([
                'current_password' =>
                    'Current password is incorrect.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent user from reusing current password
        |--------------------------------------------------------------------------
        */

        if (Hash::check($newPassword, $user->password)) {
            return back()->withErrors([
                'password' =>
                    'New password must be different from your current password.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update password
        |--------------------------------------------------------------------------
        */

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($newPassword),
                'updated_at' => Carbon::now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Audit password change
        |--------------------------------------------------------------------------
        */

        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,

            'action' => 'password_changed',

            'model_type' => null,
            'model_id' => null,
            'subject' => null,
            'old_values' => null,
            'new_values' => null,

            'description' =>
                $user->name . ' changed their password.',

            'ip_address' => $request->ip(),
            'created_at' => Carbon::now(),
        ]);

        return back()->with(
            'success',
            'Password changed successfully.'
        );
    }
}