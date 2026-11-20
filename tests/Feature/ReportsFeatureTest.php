<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_daily_report_in_pdf_excel_and_csv_formats(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Appointment::factory()->create(['appointment_date' => now()->toDateString()]);
        $this->actingAs($admin);

        foreach (['pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'csv' => 'text/csv; charset=utf-8'] as $format => $contentType) {
            $this->get(route('reports.show', ['report' => 'daily-appointments', 'date' => now()->toDateString(), 'export' => $format]))
                ->assertOk()
                ->assertHeader('content-type', $contentType);
        }
    }

    public function test_doctor_workload_report_contains_only_the_signed_in_doctor(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $doctorUser = User::factory()->create(['name' => 'Dr Own Scope']);
        $doctorUser->assignRole('doctor');
        $ownDoctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $otherDoctor = Doctor::factory()->create(['user_id' => User::factory()->create(['name' => 'Dr Private Scope'])->id]);
        Appointment::factory()->create(['doctor_id' => $ownDoctor->id, 'department_id' => $ownDoctor->department_id, 'appointment_date' => now()->toDateString()]);
        Appointment::factory()->create(['doctor_id' => $otherDoctor->id, 'department_id' => $otherDoctor->department_id, 'appointment_date' => now()->toDateString()]);

        $response = $this->actingAs($doctorUser)->get(route('reports.show', ['report' => 'doctor-workload', 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]));

        $response->assertOk()->assertSee('Dr Own Scope')->assertDontSee('Dr Private Scope');
    }

    public function test_patient_role_cannot_open_reports(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $patient = User::factory()->create();
        $patient->assignRole('patient');

        $this->actingAs($patient)->get(route('reports.index'))->assertForbidden();
    }
}
