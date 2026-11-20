<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /** @return array<int, array{start_time: string, end_time: string}> */
    public function availableSlots(Doctor $doctor, string $date, ?int $ignoreAppointmentId = null): array
    {
        $appointmentDate = Carbon::parse($date)->startOfDay();
        $slots = [];

        foreach ($doctor->schedules()->where('day_of_week', $appointmentDate->dayOfWeek)->where('is_active', true)->orderBy('start_time')->get() as $schedule) {
            $cursor = Carbon::parse($date.' '.$schedule->start_time);
            $windowEnd = Carbon::parse($date.' '.$schedule->end_time);

            while ($cursor->copy()->addMinutes($schedule->slot_duration_minutes)->lte($windowEnd)) {
                $end = $cursor->copy()->addMinutes($schedule->slot_duration_minutes);
                $startTime = $cursor->format('H:i:s');
                $endTime = $end->format('H:i:s');

                if ($cursor->gt(now()) && ! $this->hasDoctorConflict($doctor->id, $date, $startTime, $endTime, $ignoreAppointmentId)) {
                    $slots[] = ['start_time' => $cursor->format('H:i'), 'end_time' => $end->format('H:i')];
                }

                $cursor->addMinutes($schedule->slot_duration_minutes);
            }
        }

        return $slots;
    }

    public function slotError(int $doctorId, int $patientId, string $date, string $startTime, ?int $ignoreAppointmentId = null): ?string
    {
        $doctor = Doctor::query()->find($doctorId);

        if (! $doctor || ! $doctor->is_active) {
            return 'Choose an active doctor.';
        }

        $appointmentDate = Carbon::parse($date)->startOfDay();
        $start = Carbon::parse($date.' '.$startTime);

        if ($appointmentDate->lt(Carbon::today()) || ($appointmentDate->isToday() && $start->lte(now()))) {
            return 'Appointments must be booked for a future time.';
        }

        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $appointmentDate->dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $start->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        if (! $schedule) {
            return 'The doctor is not scheduled to see patients on this date.';
        }

        $end = $start->copy()->addMinutes($schedule->slot_duration_minutes);
        $scheduleStart = Carbon::parse($date.' '.$schedule->start_time);
        $scheduleEnd = Carbon::parse($date.' '.$schedule->end_time);
        $minutesFromStart = (int) $scheduleStart->diffInMinutes($start);

        if ($start->lt($scheduleStart) || $end->gt($scheduleEnd) || $minutesFromStart % $schedule->slot_duration_minutes !== 0) {
            return 'Choose a time that matches an available slot in the doctor’s schedule.';
        }

        if ($this->hasDoctorConflict($doctorId, $date, $start->format('H:i:s'), $end->format('H:i:s'), $ignoreAppointmentId)) {
            return 'That time has just been booked. Choose another available slot.';
        }

        $patientConflict = Appointment::query()
            ->where('patient_id', $patientId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $end->format('H:i:s'))
            ->where('end_time', '>', $start->format('H:i:s'))
            ->when($ignoreAppointmentId, fn ($query) => $query->where('id', '!=', $ignoreAppointmentId))
            ->exists();

        return $patientConflict ? 'This patient already has an overlapping appointment.' : null;
    }

    public function book(array $data, User $bookedBy, ?Appointment $appointment = null): Appointment
    {
        $doctorId = (int) ($data['doctor_id'] ?? $appointment?->doctor_id);
        $patientId = (int) $data['patient_id'];
        $date = (string) $data['appointment_date'];
        $lockKeys = [
            'appointment:doctor:'.$doctorId.':'.$date,
            'appointment:patient:'.$patientId.':'.$date,
        ];
        sort($lockKeys);

        try {
            return Cache::lock($lockKeys[0], 30)->block(5, function () use ($lockKeys, $data, $bookedBy, $appointment, $doctorId, $patientId, $date): Appointment {
                return Cache::lock($lockKeys[1], 30)->block(5, function () use ($data, $bookedBy, $appointment, $doctorId, $patientId, $date): Appointment {
                    return DB::transaction(function () use ($data, $bookedBy, $appointment, $doctorId, $patientId, $date): Appointment {
                        $doctor = Doctor::query()->whereKey($doctorId)->lockForUpdate()->firstOrFail();
                        $startTime = Carbon::parse($date.' '.$data['start_time'])->format('H:i:s');
                        $error = $this->slotError($doctorId, $patientId, $date, $startTime, $appointment?->id);

                        if ($error !== null) {
                            throw ValidationException::withMessages(['start_time' => $error]);
                        }

                        $schedule = $doctor->schedules()
                            ->where('day_of_week', Carbon::parse($date)->dayOfWeek)
                            ->where('is_active', true)
                            ->where('start_time', '<=', $startTime)
                            ->orderBy('start_time')
                            ->firstOrFail();
                        $endTime = Carbon::parse($date.' '.$startTime)->addMinutes($schedule->slot_duration_minutes)->format('H:i:s');

                        $appointment ??= new Appointment;
                        $appointment->fill([
                            'patient_id' => $patientId,
                            'doctor_id' => $doctor->id,
                            'department_id' => $doctor->department_id,
                            'appointment_date' => $date,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'status' => $appointment->exists ? ($data['status'] ?? $appointment->status) : 'scheduled',
                            'reason' => $data['reason'] ?? null,
                            'booked_by' => $appointment->exists ? $appointment->booked_by : $bookedBy->id,
                        ])->save();

                        return $appointment->refresh();
                    });
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['start_time' => 'This time is being booked. Wait a moment and choose again.']);
        }
    }

    private function hasDoctorConflict(int $doctorId, string $date, string $startTime, string $endTime, ?int $ignoreAppointmentId = null): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreAppointmentId, fn ($query) => $query->where('id', '!=', $ignoreAppointmentId))
            ->exists();
    }
}
