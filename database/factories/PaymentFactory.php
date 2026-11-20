<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'patient_id' => Patient::factory(),
            'amount' => 40000,
            'amount_paid' => 0,
            'payment_method' => 'cash',
            'status' => 'pending',
            'reference_number' => fake()->unique()->bothify('CCPAY-##########'),
            'paid_at' => null,
            'received_by' => User::factory(),
            'notes' => null,
        ];
    }
}
