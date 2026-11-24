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

/**
 * Builds role-specific dashboards and summary metrics for the signed-in user.
 */
class DashboardController extends Controller
{
    /**
     * Selects the destination appropriate to the current user and request context.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->user()->getRoleNames()->first();

        if ($role === 'pharmacist') {
            return redirect()->route('pharmacy.index');
        }
        if ($role === 'lab-technician') {
            return redirect()->route('lab.index');
        }
        abort_unless($role && in_array($role, ['admin', 'receptionist', 'doctor', 'cashier', 'patient'], true), 403);

        return redirect()->route('dashboard.'.$role);
    }

    /**
     * Loads the requested dashboard record and presents its associated details.
     */
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'user';
        $today = Carbon::today();
        $doctorId = $role === 'doctor' ? ($user->doctor?->id ?? 0) : 0;
        $patientId = $role === 'patient' ? ($user->patient?->id ?? 0) : 0;

        $dashboardAppointments = Appointment::query();
        if ($role === 'doctor') {
            $dashboardAppointments->where('doctor_id', $doctorId);
        } elseif ($role === 'patient') {
            $dashboardAppointments->where('patient_id', $patientId);
        }

        $weeklyStart = $today->copy()->subDays(6)->toDateString();
        $weeklyAppointments = (clone $dashboardAppointments)
            ->whereBetween('appointment_date', [$weeklyStart, $today->toDateString()])
            ->selectRaw('appointment_date, COUNT(*) as total')
            ->groupBy('appointment_date')
            ->pluck('total', 'appointment_date');

        $weeklyDates = collect(range(6, 0))->map(fn (int $daysAgo): Carbon => $today->copy()->subDays($daysAgo));
        $monthlyStatuses = (clone $dashboardAppointments)
            ->whereBetween('appointment_date', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $charts = [
            'appointmentTrend' => [
                'labels' => $weeklyDates->map(fn (Carbon $date): string => $date->format('D j'))->all(),
                'values' => $weeklyDates->map(fn (Carbon $date): int => (int) ($weeklyAppointments[$date->toDateString()] ?? 0))->all(),
            ],
            'appointmentStatuses' => [
                'labels' => collect(Appointment::STATUSES)->map(fn (string $status): string => ucfirst(str_replace('_', ' ', $status)))->all(),
                'values' => collect(Appointment::STATUSES)->map(fn (string $status): int => (int) ($monthlyStatuses[$status] ?? 0))->all(),
            ],
        ];

        $canViewFinancialCharts = in_array($role, ['admin', 'cashier'], true);
        if ($canViewFinancialCharts) {
            $sixMonthsStart = $today->copy()->startOfMonth()->subMonths(5);
            $paymentsByMonth = Payment::query()
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$sixMonthsStart, $today->copy()->endOfMonth()])
                ->selectRaw('SUBSTRING(CAST(paid_at AS CHAR), 1, 7) as month, SUM(amount_paid) as total')
                ->groupBy('month')
                ->pluck('total', 'month');
            $months = collect(range(5, 0))->map(fn (int $monthsAgo): Carbon => $today->copy()->startOfMonth()->subMonths($monthsAgo));

            $charts['monthlyCollections'] = [
                'labels' => $months->map(fn (Carbon $month): string => $month->format('M Y'))->all(),
                'values' => $months->map(fn (Carbon $month): float => (float) ($paymentsByMonth[$month->format('Y-m')] ?? 0))->all(),
            ];
        }

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
        $outstanding = $canViewFinancialCharts
            ? Payment::whereIn('status', ['pending', 'partial'])->sum(DB::raw('amount - amount_paid'))
            : 0;

        $stats = match ($role) {
            'admin' => [
                'Today’s appointments' => Appointment::whereDate('appointment_date', $today)->count(),
                'Registered patients' => Patient::count(),
                'Today’s revenue' => 'UGX '.number_format((float) Payment::whereBetween('paid_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->sum('amount_paid')),
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
                'Payments received today' => 'UGX '.number_format((float) Payment::whereBetween('paid_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->sum('amount_paid')),
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
        $payments = $patient?->payments()->with(['appointment', 'medicationOrder.consultation.appointment'])->latest()->limit(5)->get() ?? collect();

        return view('dashboard', compact('role', 'stats', 'appointments', 'patient', 'visits', 'payments', 'charts', 'canViewFinancialCharts'));
    }
}
