<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\AuthOtpNotification;
use App\Services\AuthOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, AuthOtpService $otpService): RedirectResponse
    {
        $user = $request->authenticate();

        if ($user->email_verified_at !== null && ! $user->hasRole('admin')) {
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $key = $otpService->limiterKey(AuthOtpService::LOGIN, (string) $user->id, $request->ip());

        if (RateLimiter::tooManyAttempts($key, 4)) {
            throw ValidationException::withMessages([
                'email' => 'Please wait before requesting another sign-in code.',
            ]);
        }

        RateLimiter::hit($key, 60);
        $code = $otpService->issue($user, AuthOtpService::LOGIN);

        $user->notify(new AuthOtpNotification($code, AuthOtpService::LOGIN));

        $request->session()->put([
            'auth_otp_user_id' => $user->id,
            'auth_otp_remember' => $request->boolean('remember'),
        ]);

        return redirect()->route('login.otp.create')->with('status', 'A sign-in code has been sent to your email address.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
