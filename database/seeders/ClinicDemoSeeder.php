<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ClinicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $receptionist = $this->account('Front Desk Reception', 'reception@citycare.test', '0772000002', 'receptionist');
        $cashier = $this->account('Clinic Cashier', 'cashier@citycare.test', '0772000004', 'cashier');
        $patientPortal = $this->account('Patient Portal', 'patient@citycare.test', '0772000005', 'patient');
        $this->account('Clinic Administrator', 'admin@citycare.test', '0772000001', 'admin');

        $departmentNames = ['General Medicine', 'Pediatrics', 'Maternity', 'Dental Care', 'Laboratory'];
        $departments = collect($departmentNames)->map(fn (string $name): Department => Department::create([
            'name' => $name,
            'description' => $name.' services at CityCare Medical Centre.',
            'is_active' => true,
        ]));

        $patients = Patient::factory(50)->create();
        $patientPortal->patient()->save($patients->first());

        $doctors = collect(range(0, 7))->map(function (int $index) use ($departments): Doctor {
            $user = $this->account(
                $index === 0 ? 'Dr. Sarah Nambi' : 'Doctor '.($index + 1),
                $index === 0 ? 'doctor@citycare.test' : 'doctor'.($index + 1).'@citycare.test',
                '077210'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'doctor',
            );

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'department_id' => $departments[$index % $departments->count()]->id,
                'specialization' => ['General Practitioner', 'Pediatrics', 'Obstetrics', 'Dentistry'][$index % 4],
                'license_number' => 'UG-MD-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'phone' => '077220'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'consultation_fee' => 30000 + ($index * 5000),
                'bio' => 'Experienced clinician serving families in Kampala.',
                'is_active' => true,
            ]);

            foreach (range(1, 5) as $day) {
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'slot_duration_minutes' => 30,
                    'is_active' => true,
                ]);
            }

            return $doctor;
        });

        for ($index = 0; $index < 150; $index++) {
            $appointmentDate = Carbon::today()->subDays(75)->addDays($index);
            $doctor = $doctors[$index % $doctors->count()];
            $patient = $patients[$index % $patients->count()];
            $start = Carbon::parse('09:00')->addMinutes(($index % 8) * 30);
            $isPast = $appointmentDate->lt(Carbon::today());
            $status = $isPast ? ($index % 9 === 0 ? 'no_show' : 'completed') : ($index % 2 === 0 ? 'scheduled' : 'confirmed');

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'department_id' => $doctor->department_id,
                'appointment_date' => $appointmentDate->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $start->copy()->addMinutes(30)->format('H:i:s'),
                'status' => $status,
                'reason' => 'General consultation',
                'booked_by' => $receptionist->id,
            ]);

            if ($status === 'completed') {
                Consultation::create([
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'symptoms' => 'Patient reports fatigue and a mild headache.',
                    'diagnosis' => 'Routine clinical assessment completed.',
                    'treatment_plan' => 'Rest, hydration and follow-up if symptoms continue.',
                    'prescription' => 'Paracetamol as needed.',
                    'notes' => 'Demo record for training and evaluation.',
                ]);
            }

            $fee = (float) $doctor->consultation_fee;
            $paymentStatus = match ($index % 3) {
                0 => 'pending',
                1 => 'partial',
                default => 'paid',
            };
            $amountPaid = match ($paymentStatus) {
                'pending' => 0,
                'partial' => round($fee / 2, 2),
                default => $fee,
            };

            Payment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'amount' => $fee,
                'amount_paid' => $amountPaid,
                'payment_method' => ['cash', 'mobile_money', 'card', 'insurance'][$index % 4],
                'status' => $paymentStatus,
                'reference_number' => 'CCPAY-'.str_pad((string) ($index + 1), 10, '0', STR_PAD_LEFT),
                'paid_at' => $amountPaid > 0 ? $appointmentDate->copy()->setTime(12, 0) : null,
                'received_by' => $cashier->id,
                'notes' => null,
            ]);
        }
    }

    private function account(string $name, string $email, string $phone, string $role): \App\Models\User
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'phone' => $phone, 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_active' => true],
        );
        $user->assignRole($role);

        return $user;
    }
}
