<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk()->assertSee('Create your account');
    }

    public function test_patient_can_register_a_portal_account(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'email' => 'test@example.com',
            'phone' => '+256700000001',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertDatabaseHas('patients', ['first_name' => 'Test', 'last_name' => 'Patient', 'phone' => '+256700000001']);
    }
}
