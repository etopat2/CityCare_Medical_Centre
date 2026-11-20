<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
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
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'symptoms' => fake()->sentence(),
            'diagnosis' => fake()->sentence(),
            'treatment_plan' => fake()->paragraph(),
            'prescription' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'follow_up_date' => fake()->optional()->dateTimeBetween('tomorrow', '+3 months')->format('Y-m-d'),
        ];
    }
}
