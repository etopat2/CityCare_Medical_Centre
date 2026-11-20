<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public static function roleDashboards(): array
    {
        return [
            'admin' => ['admin'],
            'receptionist' => ['receptionist'],
            'doctor' => ['doctor'],
            'cashier' => ['cashier'],
            'patient' => ['patient'],
        ];
    }

    #[DataProvider('roleDashboards')]
    public function test_user_can_open_only_their_role_dashboard(string $role): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('dashboard.'.$role))->assertOk();

        foreach (array_diff(['admin', 'receptionist', 'doctor', 'cashier', 'patient'], [$role]) as $otherRole) {
            $this->get(route('dashboard.'.$otherRole))->assertForbidden();
        }
    }

    public function test_dashboard_route_redirects_to_the_matching_role_path(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('cashier');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.cashier'));
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'inactive@citycare.test',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'inactive@citycare.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
