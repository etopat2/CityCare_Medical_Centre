<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_book_a_valid_slot_and_overlapping_booking_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');
        [$doctor, $date] = $this->doctorWithFutureSchedule();
        $firstPatient = Patient::factory()->create();
        $secondPatient = Patient::factory()->create();
        $payload = ['patient_id' => $firstPatient->id, 'doctor_id' => $doctor->id, 'appointment_date' => $date->toDateString(), 'start_time' => '10:00', 'reason' => 'Review'];

        $this->actingAs($receptionist)->post(route('appointments.store'), $payload)
            ->assertRedirect();
        $this->assertDatabaseHas('appointments', ['patient_id' => $firstPatient->id, 'start_time' => '10:00:00']);

        $this->post(route('appointments.store'), [...$payload, 'patient_id' => $secondPatient->id])
            ->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_slot_api_reports_available_slots_and_excludes_a_booked_slot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');
        [$doctor, $date] = $this->doctorWithFutureSchedule();
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'department_id' => $doctor->department_id,
            'appointment_date' => $date->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
        ]);

        $response = $this->actingAs($receptionist)->getJson(route('api.doctors.available-slots', ['doctor' => $doctor, 'date' => $date->toDateString()]));

        $response->assertOk()->assertJsonPath('available', true)->assertJsonMissing(['start_time' => '10:00']);
        $this->assertNotContains('10:00', array_column($response->json('slots'), 'start_time'));
        $this->assertSame($doctor->id, $appointment->doctor_id);
    }

    public function test_cashier_records_partial_payment_and_updates_balance(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');
        $doctor = Doctor::factory()->create(['consultation_fee' => 50000]);
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'department_id' => $doctor->department_id]);
        $payload = ['appointment_id' => $appointment->id, 'amount_paid' => 20000, 'payment_method' => 'cash'];

        $this->actingAs($cashier)->post(route('payments.store'), $payload)->assertRedirect();
        $payment = Payment::sole();
        $this->assertSame('partial', $payment->status);
        $this->assertSame(30000.0, $payment->balance);
        $this->post(route('payments.store'), [...$payload, 'amount_paid' => 30000])->assertRedirect();
        $this->assertSame(1, Payment::count());
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(0.0, $payment->fresh()->balance);
    }

    public function test_patient_cannot_view_another_patients_appointment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('patient');
        $ownPatient = Patient::factory()->create(['user_id' => $user->id]);
        $otherAppointment = Appointment::factory()->create();
        $ownAppointment = Appointment::factory()->create(['patient_id' => $ownPatient->id]);

        $this->actingAs($user)->get(route('appointments.show', $otherAppointment))->assertForbidden();
        $this->get(route('appointments.show', $ownAppointment))->assertOk();
    }

    public function test_patient_consultation_list_is_limited_to_their_own_history(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('patient');
        $ownPatient = Patient::factory()->create(['user_id' => $user->id]);
        $ownAppointment = Appointment::factory()->create(['patient_id' => $ownPatient->id, 'status' => 'completed']);
        $ownAppointment->consultation()->create([
            'doctor_id' => $ownAppointment->doctor_id,
            'patient_id' => $ownPatient->id,
            'symptoms' => 'Own symptoms',
            'diagnosis' => 'Own private diagnosis',
        ]);
        $otherAppointment = Appointment::factory()->create(['status' => 'completed']);
        $otherAppointment->consultation()->create([
            'doctor_id' => $otherAppointment->doctor_id,
            'patient_id' => $otherAppointment->patient_id,
            'symptoms' => 'Other symptoms',
            'diagnosis' => 'Other private diagnosis',
        ]);

        $this->actingAs($user)->get(route('consultations.index'))
            ->assertOk()
            ->assertSee('Own private diagnosis')
            ->assertDontSee('Other private diagnosis');
    }

    public function test_doctor_can_view_only_their_own_weekly_schedule(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $doctorUser = User::factory()->create(['name' => 'Dr Schedule Owner']);
        $doctorUser->assignRole('doctor');
        $ownDoctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
        $otherDoctor = Doctor::factory()->create(['user_id' => User::factory()->create(['name' => 'Dr Other Schedule'])->id]);
        DoctorSchedule::factory()->create(['doctor_id' => $ownDoctor->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '13:00:00']);
        DoctorSchedule::factory()->create(['doctor_id' => $otherDoctor->id, 'day_of_week' => 2, 'start_time' => '10:00:00', 'end_time' => '14:00:00']);

        $this->actingAs($doctorUser)->get(route('doctor-schedule.own'))
            ->assertOk()
            ->assertSee('Dr Schedule Owner')
            ->assertSee('Monday')
            ->assertDontSee('Dr Other Schedule');
    }

    /** @return array{0: Doctor, 1: Carbon} */
    private function doctorWithFutureSchedule(): array
    {
        $date = Carbon::tomorrow()->next(Carbon::MONDAY);
        $department = Department::factory()->create();
        $doctor = Doctor::factory()->create(['department_id' => $department->id]);
        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'slot_duration_minutes' => 30,
        ]);

        return [$doctor, $date];
    }
}
