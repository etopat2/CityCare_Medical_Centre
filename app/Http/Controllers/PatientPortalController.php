<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Provides signed-in patients with their appointment, visit, payment, and profile views.
 */
class PatientPortalController extends Controller
{
    /** Shows the signed-in patient's own contact and demographic details. */
    public function profile(Request $request): View
    {
        $patient = $request->user()->patient;

        abort_unless($patient, 404);

        return view('portal.profile', compact('patient'));
    }

    /** Lists the signed-in patient's appointments with their doctor, status, and payment information. */
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

    /** Lists the patient's consultation and laboratory history while preserving clinical record access rules. */
    public function visits(Request $request): View
    {
        $visits = Consultation::query()
            ->where('patient_id', $request->user()->patient?->id ?? 0)
            ->with(['appointment.department', 'appointment.doctor', 'appointment.payment.exemption', 'doctor.user', 'labOrder.items.test', 'labOrder.payment'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.visits', compact('visits'));
    }

    /** Lists the patient's balances, transactions, and receipt actions across appointment, medicine, and lab bills. */
    public function payments(Request $request): View
    {
        $payments = Payment::query()
            ->where('patient_id', $request->user()->patient?->id ?? 0)
            ->with(['appointment.doctor.user', 'medicationOrder.items.medicine', 'medicationOrder.consultation.appointment.doctor.user', 'medicationOrder.consultation.appointment.payment', 'labOrder.items.test', 'labOrder.appointment.doctor.user', 'labOrder.consultation.appointment.payment', 'transactions' => fn ($query) => $query->latest()])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.payments', compact('payments'));
    }
}
