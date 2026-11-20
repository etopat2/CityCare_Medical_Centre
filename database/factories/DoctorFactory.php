<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'specialization' => fake()->randomElement(['General Medicine', 'Pediatrics', 'Cardiology', 'Dermatology']),
            'license_number' => fake()->unique()->bothify('UG-MD-#####'),
            'phone' => fake()->unique()->numerify('+2567########'),
            'consultation_fee' => fake()->randomElement([30000, 40000, 50000, 60000]),
            'bio' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
