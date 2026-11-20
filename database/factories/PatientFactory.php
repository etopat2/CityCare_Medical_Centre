<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_number' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['female', 'male', 'other']),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'phone' => fake()->unique()->numerify('+2567########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->address(),
            'national_id' => fake()->boolean(25) ? fake()->unique()->bothify('CM###########') : null,
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('+2567########'),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'O+', 'O-']),
            'allergies' => fake()->optional()->words(3, true),
        ];
    }
}
