<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $role = Auth::user()->role;
            return redirect()->route($role . '.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $email    = $request->input('email', '');
        $password = $request->input('password', '');
        $remember = $request->input('remember', false);

        // Basic validation
        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required.',
            ], 422);
        }

        if (empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required.',
            ], 422);
        }

        $credentials = [
            'email'    => $email,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $remember)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact an administrator.',
            ], 403);
        }

        // Update last login
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update(['last_login_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')]);

        $request->session()->regenerate();

        // Audit log
        $logData = [
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'user_role'   => $user->role,
            'action'      => 'login',
            'model_type'  => null,
            'model_id'    => null,
            'subject'     => null,
            'old_values'  => null,
            'new_values'  => null,
            'description' => $user->name . ' (' . $user->role . ') logged in.',
            'ip_address'  => $request->ip(),
            'created_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];

        \Illuminate\Support\Facades\DB::table('audit_logs')->insert($logData);

        // Determine redirect URL based on role
        if ($user->role === 'admin') {
            $redirectUrl = route('admin.dashboard');
        } elseif ($user->role === 'staff') {
            $redirectUrl = route('staff.dashboard');
        } elseif ($user->role === 'viewer') {
            $redirectUrl = route('viewer.dashboard');
        } else {
            $redirectUrl = route('login');
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Login successful.',
            'redirect' => $redirectUrl,
            'user'     => [
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $logData = [
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'user_role'   => $user->role,
                'action'      => 'logout',
                'model_type'  => null,
                'model_id'    => null,
                'subject'     => null,
                'old_values'  => null,
                'new_values'  => null,
                'description' => $user->name . ' logged out.',
                'ip_address'  => $request->ip(),
                'created_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            ];

            \Illuminate\Support\Facades\DB::table('audit_logs')->insert($logData);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function changePassword(Request $request)
    {
        $currentPassword = $request->input('current_password', '');
        $newPassword     = $request->input('password', '');
        $confirmation    = $request->input('password_confirmation', '');

        if (empty($currentPassword)) {
            return back()->withErrors(['current_password' => 'Current password is required.']);
        }

        if (empty($newPassword)) {
            return back()->withErrors(['password' => 'New password is required.']);
        }

        if ($newPassword !== $confirmation) {
            return back()->withErrors(['password' => 'Passwords do not match.']);
        }

        if (strlen($newPassword) < 8) {
            return back()->withErrors(['password' => 'Password must be at least 8 characters.']);
        }

        $user = Auth::user();

        if (!Hash::check($currentPassword, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update(['password' => Hash::make($newPassword)]);

        $logData = [
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'user_role'   => $user->role,
            'action'      => 'password_changed',
            'model_type'  => null,
            'model_id'    => null,
            'subject'     => null,
            'old_values'  => null,
            'new_values'  => null,
            'description' => $user->name . ' changed their password.',
            'ip_address'  => $request->ip(),
            'created_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];

        \Illuminate\Support\Facades\DB::table('audit_logs')->insert($logData);

        return back()->with('success', 'Password changed successfully.');
    }
}