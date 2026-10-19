<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use App\Modules\Identity\Contracts\SMSAdapterInterface;
use App\Modules\Identity\Contracts\OTPServiceInterface;

class OTPController extends Controller
{
    protected SMSAdapterInterface $sms;
    protected OTPServiceInterface $otp;

    public function __construct(SMSAdapterInterface $sms, OTPServiceInterface $otp)
    {
        $this->sms = $sms;
        $this->otp = $otp;
    }

    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
        ]);

        $key = 'otp_request_' . $validated['phone'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many OTP requests. Please try again later.'], 429);
        }

        RateLimiter::hit($key, 60);

        $this->otp->send($validated['phone']);

        return response()->json([
            'message' => 'OTP sent successfully',
            'phone' => $validated['phone'],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[\+]?[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'length:6'],
        ]);

        $key = 'otp_verify_' . $validated['phone'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many verification attempts.'], 429);
        }

        RateLimiter::hit($key, 60);

        $user = $this->otp->verify($validated['phone'], $validated['otp']);

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Authentication successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}