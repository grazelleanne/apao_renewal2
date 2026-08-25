<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $this->currentActiveUser($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
        ]);

        $name = trim($validated['name']);
        $email = Str::lower(trim($validated['email']));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => ['The full name field is required.']]);
        }

        $emailChanged = $email !== Str::lower($user->email);
        $nameChanged = $name !== $user->name;
        if ($emailChanged && (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password))) {
            throw ValidationException::withMessages(['current_password' => ['Current password is incorrect.']]);
        }

        if ($nameChanged || $emailChanged) {
            $oldEmail = $user->email;
            DB::table('users')->where('id', $user->id)->update([
                'name' => $name,
                'email' => $email,
                'updated_at' => now(),
            ]);
            $user = DB::table('users')->where('id', $user->id)->first();
            session(['user' => (array) $user]);

            if ($nameChanged) {
                auditLog('profile_updated', $user->email, ['role' => $user->role, 'changed_fields' => ['name']]);
            }
            if ($emailChanged) {
                auditLog('email_changed', $user->email, [
                    'role' => $user->role,
                    'old_email' => $oldEmail,
                    'new_email' => $user->email,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => ['name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $this->currentActiveUser($request);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Current password is incorrect.']]);
        }
        if (Hash::check($validated['new_password'], $user->password)) {
            throw ValidationException::withMessages(['new_password' => ['The new password must be different from your current password.']]);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);
        auditLog('password_changed', $user->email, ['role' => $user->role]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    private function currentActiveUser(Request $request): object
    {
        $sessionUser = session('user');
        $userId = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : ($sessionUser->id ?? null);
        $sessionRole = is_array($sessionUser) ? ($sessionUser['role'] ?? null) : ($sessionUser->role ?? null);
        $expectedRole = $request->routeIs('admin.*') ? 'admin' : ($request->routeIs('staff.*') ? 'staff' : null);
        $user = $userId ? DB::table('users')->where('id', $userId)->first() : null;

        $authorized = $user
            && in_array($user->role, ['admin', 'staff'], true)
            && $user->role === $sessionRole
            && $user->role === $expectedRole
            && (int) $user->is_active === 1;

        if (!$authorized) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Your account is not authorized to make this change.',
            ], 403));
        }

        return $user;
    }
}
