<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PatientPortalController extends Controller
{
    public function profile(Request $request): View
    {
        $patient = $request->user()->patient;

        abort_unless($patient, 404);

        return view('portal.profile', compact('patient'));
    }

    public function appointments(Request $request): View
    {
        $patientId = $request->user()->patient?->id ?? 0;
        $appointments = Appointment::query()
            ->where('patient_id', $patientId)
            ->with(['doctor.user', 'department', 'payment'])
            ->orderByDesc('appointment_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('portal.appointments', compact('appointments'));
    }

    public function visits(Request $request): View
    {
        $visits = Consultation::query()
            ->where('patient_id', $request->user()->patient?->id ?? 0)
            ->with(['appointment.department', 'doctor.user'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.visits', compact('visits'));
    }

    public function payments(Request $request): View
    {
        $payments = Payment::query()
            ->where('patient_id', $request->user()->patient?->id ?? 0)
            ->with(['appointment.doctor.user', 'transactions' => fn ($query) => $query->latest()])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.payments', compact('payments'));
    }
}
