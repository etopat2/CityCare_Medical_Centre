<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AuthOtpNotification;
use App\Services\AuthOtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetOtpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_otp_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    public function store(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $email = $request->session()->get('password_otp_email');
        $user = is_string($email) ? User::query()->where('email', $email)->first() : null;

        if ($user === null) {
            throw ValidationException::withMessages(['code' => 'That code is invalid or has expired. Request a new one and try again.']);
        }

        $otpService->consume($user, AuthOtpService::PASSWORD_RESET, $validated['code'], function (User $verifiedUser) use ($validated): void {
            $verifiedUser->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($verifiedUser));
        });

        $request->session()->forget('password_otp_email');

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    public function resend(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $email = $request->session()->get('password_otp_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        $key = $otpService->limiterKey(AuthOtpService::PASSWORD_RESET, $email, $request->ip());

        if (RateLimiter::tooManyAttempts($key, 4)) {
            throw ValidationException::withMessages(['code' => 'Please wait before requesting another code.']);
        }

        RateLimiter::hit($key, 60);
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $user->notify(new AuthOtpNotification($otpService->issue($user, AuthOtpService::PASSWORD_RESET), AuthOtpService::PASSWORD_RESET));
        }

        return back()->with('status', 'If that account exists, a new password reset code has been sent.');
    }
}
