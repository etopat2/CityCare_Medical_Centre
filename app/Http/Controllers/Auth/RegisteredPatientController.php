<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisteredPatientController extends Controller
{
    public function create(): View
    {
        return view('auth.register-patient');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['female', 'male', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'confirmed', 'min:8'],
            'accepted_terms' => ['accepted'],
        ]);

        if (Patient::withTrashed()->where('phone', $data['phone'])->whereDate('date_of_birth', $data['date_of_birth'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'An account with this phone number and date of birth already exists. Contact CityCare reception if you need help.']);
        }

        DB::transaction(function () use ($data): void {
            $user = User::query()->create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole('patient');

            Patient::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'] ?? null,
            ]);
        });

        return redirect()->route('login')->with('status', 'Your patient account is ready. Sign in to verify your email and continue.');
    }
}
