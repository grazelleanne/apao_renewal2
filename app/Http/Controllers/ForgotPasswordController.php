<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    // Step 1: Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $otp = rand(100000, 999999);

        DB::table('password_resets_otp')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp'        => $otp,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
            ]
        );

        Mail::to($request->email)->send(new OtpMail((string) $otp));

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $record = DB::table('password_resets_otp')
            ->where('email', $request->email)
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
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        User::where('email', $request->email)
            ->update(['password' => bcrypt($request->password)]);

        DB::table('password_resets_otp')
            ->where('email', $request->email)
            ->delete();

        return response()->json(['message' => 'Password reset successful.']);
    }
}