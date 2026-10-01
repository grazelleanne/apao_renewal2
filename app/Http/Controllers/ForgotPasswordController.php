<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    // Step 1: Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));
        $key = 'password-reset:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => 'Please wait before requesting another code.'], 429);
        }
        RateLimiter::hit($key, 60);

        // Do not reveal whether an email address is registered.
        if (!User::where('email', $email)->exists()) {
            return response()->json(['message' => 'If the account exists, a code will be sent shortly.']);
        }

        $otp = random_int(100000, 999999);

        DB::table('password_resets_otp')->updateOrInsert(
            ['email' => $email],
            [
                'otp'        => $otp,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
            ]
        );

        Mail::to($email)->send(new OtpMail((string) $otp));

        return response()->json(['message' => 'If the account exists, a code will be sent shortly.']);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email', 'code' => 'required|digits:6']);
        $email = strtolower(trim($request->email));
        $record = DB::table('password_resets_otp')
            ->where('email', $email)
            ->where('otp', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['error' => 'Invalid or expired code.'], 422);
        }

        return response()->json(['message' => 'OTP verified.']);
    }

    // Step 3: Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|digits:6',
            'password'              => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => 'required',
        ]);

        $otp = DB::table('password_resets_otp')
            ->where('email', strtolower(trim($request->email)))
            ->where('otp', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(['error' => 'Invalid or expired code.'], 422);
        }

        User::where('email', strtolower(trim($request->email)))
            ->update(['password' => bcrypt($request->password)]);

        DB::table('password_resets_otp')
            ->where('email', strtolower(trim($request->email)))
            ->delete();

        return response()->json(['message' => 'Password reset successful.']);
    }
}
