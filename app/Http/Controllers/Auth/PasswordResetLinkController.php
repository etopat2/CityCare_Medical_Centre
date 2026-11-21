<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AuthOtpNotification;
use App\Services\AuthOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $key = $otpService->limiterKey(AuthOtpService::PASSWORD_RESET, $validated['email'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, 4)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait before requesting another password reset code.',
            ]);
        }

        RateLimiter::hit($key, 60);
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user !== null) {
            $user->notify(new AuthOtpNotification(
                $otpService->issue($user, AuthOtpService::PASSWORD_RESET),
                AuthOtpService::PASSWORD_RESET,
            ));
        }

        $request->session()->put('password_otp_email', $validated['email']);

        return redirect()->route('password.reset')->with('status', 'If that account exists, a password reset code has been sent.');
    }
}
