<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Service for handling one-time password (OTP) challenges for authentication
class AuthOtpService
{
    // Constants representing the purposes for which an OTP can be issued
    public const LOGIN = 'login';

    public const PASSWORD_RESET = 'password_reset';

    // Issue a new OTP challenge for a user and purpose, returning the generated code
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
                
                // Optionally, you could log or notify the user about the failed attempts here
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

    // Generate a unique key for rate limiting OTP requests based on the purpose, user identifier, and IP address
    public function limiterKey(string $purpose, string $identifier, string $ip): string
    {
        return 'auth-otp:'.Str::lower($purpose).'|'.hash('sha256', Str::lower($identifier).'|'.$ip);
    }
}
