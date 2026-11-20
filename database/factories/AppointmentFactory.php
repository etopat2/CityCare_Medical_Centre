<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'department_id' => Department::factory(),
            'appointment_date' => fake()->dateTimeBetween('tomorrow', '+2 months')->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'scheduled',
            'reason' => fake()->sentence(),
            'booked_by' => User::factory(),
        ];
    }
}
