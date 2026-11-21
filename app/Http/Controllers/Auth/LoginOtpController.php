<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AuthOtpNotification;
use App\Services\AuthOtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginOtpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = User::query()->find($request->session()->get('auth_otp_user_id'));

        if ($user === null || ! $user->is_active) {
            $request->session()->forget(['auth_otp_user_id', 'auth_otp_remember']);

            return redirect()->route('login')->with('error', 'Please sign in again to request a new code.');
        }

        return view('auth.login-otp', ['email' => $user->email]);
    }

    public function store(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = User::query()->find($request->session()->get('auth_otp_user_id'));

        if ($user === null || ! $user->is_active) {
            $request->session()->forget(['auth_otp_user_id', 'auth_otp_remember']);

            return redirect()->route('login')->with('error', 'Please sign in again to request a new code.');
        }

        $otpService->consume($user, AuthOtpService::LOGIN, $validated['code'], function (User $verifiedUser): void {
            if ($verifiedUser->email_verified_at === null) {
                $verifiedUser->forceFill(['email_verified_at' => now()])->save();

                if ($verifiedUser instanceof MustVerifyEmail) {
                    event(new Verified($verifiedUser));
                }
            }
        });

        Auth::guard('web')->login($user, (bool) $request->session()->get('auth_otp_remember', false));
        $request->session()->forget(['auth_otp_user_id', 'auth_otp_remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false))->with('status', 'You are signed in securely.');
    }

    public function resend(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $user = User::query()->find($request->session()->get('auth_otp_user_id'));

        if ($user === null || ! $user->is_active) {
            $request->session()->forget(['auth_otp_user_id', 'auth_otp_remember']);

            return redirect()->route('login')->with('error', 'Please sign in again to request a new code.');
        }

        $key = $otpService->limiterKey(AuthOtpService::LOGIN, (string) $user->id, $request->ip());

        if (RateLimiter::tooManyAttempts($key, 4)) {
            throw ValidationException::withMessages(['code' => 'Please wait before requesting another code.']);
        }

        RateLimiter::hit($key, 60);
        $user->notify(new AuthOtpNotification($otpService->issue($user, AuthOtpService::LOGIN), AuthOtpService::LOGIN));

        return back()->with('status', 'A new sign-in code has been sent to your email address.');
    }
}
