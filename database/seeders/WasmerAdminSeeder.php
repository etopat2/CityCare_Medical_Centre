<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WasmerAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('wasmer.admin_email')));
        $password = (string) config('wasmer.admin_password');
        $name = trim((string) config('wasmer.admin_name', 'CityCare Administrator'));

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:16'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $user->assignRole('admin');
    }
}
