<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->user()->getRoleNames()->first();

        abort_unless($role && in_array($role, ['admin', 'receptionist', 'doctor', 'cashier', 'patient'], true), 403);

        return redirect()->route('dashboard.'.$role);
    }

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'user';
        $today = Carbon::today();
        $appointmentsQuery = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->with(['patient', 'doctor.user', 'department'])
            ->orderBy('start_time');

        if ($role === 'doctor') {
            $appointmentsQuery->whereHas('doctor', fn ($query) => $query->where('user_id', $user->id));
        } elseif ($role === 'patient') {
            $appointmentsQuery->where('patient_id', $user->patient?->id ?? 0);
        }

        $appointments = $appointmentsQuery->limit(8)->get();
        $doctorId = $user->doctor?->id ?? 0;
        $patientId = $user->patient?->id ?? 0;
        $outstanding = Payment::whereIn('status', ['pending', 'partial'])->sum(DB::raw('amount - amount_paid'));

        $stats = match ($role) {
            'admin' => [
                'Today’s appointments' => Appointment::whereDate('appointment_date', $today)->count(),
                'Registered patients' => Patient::count(),
                'Today’s revenue' => 'UGX '.number_format((float) Payment::whereDate('paid_at', $today)->sum('amount_paid')),
                'Outstanding balances' => 'UGX '.number_format((float) $outstanding),
            ],
            'receptionist' => [
                'Today’s appointments' => Appointment::whereDate('appointment_date', $today)->count(),
                'Patients registered' => Patient::whereDate('created_at', $today)->count(),
                'Active doctors' => Doctor::where('is_active', true)->count(),
            ],
            'doctor' => [
                'Today’s appointments' => Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', $today)->count(),
                'Upcoming appointments' => Appointment::where('doctor_id', $doctorId)->whereDate('appointment_date', '>', $today)->whereIn('status', ['scheduled', 'confirmed'])->count(),
                'Patients treated' => Patient::whereHas('consultations', fn ($query) => $query->where('doctor_id', $doctorId))->distinct()->count(),
            ],
            'cashier' => [
                'Unpaid balances' => 'UGX '.number_format((float) $outstanding),
                'Payments received today' => 'UGX '.number_format((float) Payment::whereDate('paid_at', $today)->sum('amount_paid')),
                'Payments to reconcile' => Payment::whereIn('status', ['pending', 'partial'])->count(),
            ],
            default => [
                'Upcoming appointments' => Appointment::where('patient_id', $patientId)->whereDate('appointment_date', '>=', $today)->whereIn('status', ['scheduled', 'confirmed'])->count(),
                'Completed visits' => Appointment::where('patient_id', $patientId)->where('status', 'completed')->count(),
                'Open payments' => Payment::where('patient_id', $patientId)->whereIn('status', ['pending', 'partial'])->count(),
            ],
        };

        $patient = $role === 'patient' ? $user->patient : null;
        $visits = $patient?->consultations()->with(['doctor.user', 'appointment'])->latest()->limit(5)->get() ?? collect();
        $payments = $patient?->payments()->with('appointment')->latest()->limit(5)->get() ?? collect();

        return view('dashboard', compact('role', 'stats', 'appointments', 'patient', 'visits', 'payments'));
    }
}
