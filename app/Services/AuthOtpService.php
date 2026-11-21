<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthOtpService
{
    public const LOGIN = 'login';

    public const PASSWORD_RESET = 'password_reset';

    public function issue(User $user, string $purpose): string
    {
        $code = (string) random_int(100000, 999999);
        $now = now();

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
        $consumed = DB::transaction(function () use ($user, $purpose, $code, $onVerified): bool {
            $challenge = DB::table('auth_otp_challenges')
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->lockForUpdate()
                ->first();

            if ($challenge === null || now()->greaterThan($challenge->expires_at) || $challenge->attempts >= 5) {
                return false;
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $attempts = $challenge->attempts + 1;

                if ($attempts >= 5) {
                    DB::table('auth_otp_challenges')
                        ->where('id', $challenge->id)
                        ->delete();
                } else {
                    DB::table('auth_otp_challenges')
                        ->where('id', $challenge->id)
                        ->update(['attempts' => $attempts, 'updated_at' => now()]);
                }

                return false;
            }

            $onVerified($user);

            DB::table('auth_otp_challenges')
                ->where('id', $challenge->id)
                ->delete();

            return true;
        });

        if (! $consumed) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Request a new one and try again.',
            ]);
        }
    }

    public function limiterKey(string $purpose, string $identifier, string $ip): string
    {
        return 'auth-otp:'.Str::lower($purpose).'|'.hash('sha256', Str::lower($identifier).'|'.$ip);
    }
}
