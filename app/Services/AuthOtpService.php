<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Issues, verifies, rate-limits, and consumes one-time codes for sign-in and password reset.
 */
class AuthOtpService
{
    // A challenge is tied to one of these purposes so a login code cannot reset a password.
    public const LOGIN = 'login';

    public const PASSWORD_RESET = 'password_reset';

    /**
     * Replaces the user's active challenge with a short-lived code and returns the plaintext for delivery.
     * Only the hash is persisted; callers must send the returned code through the configured notification channel.
     */
    public function issue(User $user, string $purpose): string
    {
        // Generate a random 6-digit code
        $code = (string) random_int(100000, 999999);
        $now = now();

        // Store the hashed code and related information in the database, updating or inserting as necessary
        DB::table('auth_otp_challenges')->updateOrInsert(
            ['user_id' => $user->id, 'purpose' => $purpose],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => $now->copy()->addMinutes(10),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        return $code;
    }

    /**
     * Consume a valid challenge and perform its protected change in one transaction.
     *
     * @param  callable(User): void  $onVerified
     */
    public function consume(User $user, string $purpose, string $code, callable $onVerified): void
    {
        // Use a database transaction to ensure atomicity of the verification and consumption process
        $consumed = DB::transaction(function () use ($user, $purpose, $code, $onVerified): bool {
            $challenge = DB::table('auth_otp_challenges')
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->lockForUpdate()
                ->first();

            // Check if the challenge is valid, not expired, and has not exceeded the maximum number of attempts
            if ($challenge === null || now()->greaterThan($challenge->expires_at) || $challenge->attempts >= 5) {
                return false;
            }

            // Verify the provided code against the stored hash
            if (! Hash::check($code, $challenge->code_hash)) {
                $attempts = $challenge->attempts + 1;

                // If the maximum number of attempts has been reached, delete the challenge; otherwise, update the attempt count
                if ($attempts >= 5) {
                    DB::table('auth_otp_challenges')
                        ->where('id', $challenge->id)
                        ->delete();
                } else {
                    // Update the attempts count and timestamp for the challenge
                    DB::table('auth_otp_challenges')
                        ->where('id', $challenge->id)
                        ->update(['attempts' => $attempts, 'updated_at' => now()]);
                }

                return false;
            }
            // If the code is valid, execute the provided callback to perform the protected action
            $onVerified($user);

            // Delete the challenge after successful verification to prevent reuse
            DB::table('auth_otp_challenges')
                ->where('id', $challenge->id)
                ->delete();

            return true;
        });

        // If the challenge was not consumed successfully, throw a validation exception with an appropriate message
        if (! $consumed) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Request a new one and try again.',
            ]);
        }
    }

    /**
     * Builds a stable, privacy-preserving key that separates limits by purpose, identifier, and source IP.
     */
    public function limiterKey(string $purpose, string $identifier, string $ip): string
    {
        return 'auth-otp:'.Str::lower($purpose).'|'.hash('sha256', Str::lower($identifier).'|'.$ip);
    }
}
