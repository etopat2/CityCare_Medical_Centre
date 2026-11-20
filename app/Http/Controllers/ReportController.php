<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    private const REPORTS = [
        'daily-appointments' => ['Daily appointment list', 'reports.daily.view'],
        'doctor-workload' => ['Doctor schedule and workload', 'reports.view'],
        'payments' => ['Payment report', 'reports.payments.view'],
        'patient-visits' => ['Patient visit report', 'reports.view'],
        'attendance' => ['Attendance trends', 'reports.view'],
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin') || collect(self::REPORTS)->contains(fn (array $report): bool => $request->user()->can($report[1])), 403);

        $reports = collect(self::REPORTS)->filter(fn (array $report): bool => $request->user()->can($report[1]) || $request->user()->hasRole('admin'));

        return view('reports.index', compact('reports'));
    }

    public function show(Request $request, string $report): View|Response
    {
        [$title, $permission] = $this->reportDefinition($request, $report);
        $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'method' => ['nullable', Rule::in(Payment::METHODS)],
            'status' => ['nullable', Rule::in(Payment::STATUSES)],
            'export' => ['nullable', Rule::in(['pdf', 'xlsx', 'csv'])],
        ]);
        $data = $this->data($request, $report);

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->can('reports.export') || $request->user()->hasRole('admin'), 403);

            return Excel::download(new ReportExport($data['headings'], $data['rows']), $report.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($request->query('export') === 'xlsx') {
            abort_unless($request->user()->can('reports.export') || $request->user()->hasRole('admin'), 403);

            return Excel::download(new ReportExport($data['headings'], $data['rows']), $report.'.xlsx');
        }

        if ($request->query('export') === 'pdf') {
            abort_unless($request->user()->can('reports.export') || $request->user()->hasRole('admin'), 403);

            return Pdf::loadView('reports.pdf', ['title' => $title, ...$data, 'generatedBy' => $request->user(), 'generatedAt' => now()])->setPaper('a4', 'landscape')->stream($report.'.pdf');
        }

        $doctors = Doctor::with('user')->orderBy('id')->get();
        $departments = Department::orderBy('name')->get();
        $patients = collect();
        if ($report === 'patient-visits') {
            $patients = Patient::query()->when($request->user()->hasRole('doctor'), fn ($query) => $query->whereHas('consultations', fn ($consultations) => $consultations->where('doctor_id', $request->user()->doctor?->id)))
                ->orderBy('last_name')->orderBy('first_name')->get(['id', 'patient_number', 'first_name', 'last_name']);
        }

        return view('reports.show', compact('title', 'report', 'data', 'doctors', 'patients', 'departments'));
    }

    /** @return array{headings: array<int, string>, rows: array<int, array<int, string|int|float|null>>, summary: array<string, int|float|string>} */
    private function data(Request $request, string $report): array
    {
        $from = Carbon::parse($request->query('date_from', now()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('date_to', now()->toDateString()))->endOfDay();
        abort_if($from->gt($to), 422, 'The start date must be on or before the end date.');
        $doctorId = $request->integer('doctor_id') ?: null;
        if ($request->user()->hasRole('doctor')) {
            $doctorId = $request->user()->doctor?->id ?? 0;
        }

        if ($report === 'daily-appointments') {
            $appointments = Appointment::query()->with(['patient', 'doctor.user', 'department'])
                ->whereDate('appointment_date', $request->query('date', now()->toDateString()))
                ->when($doctorId !== null, fn ($query) => $query->where('doctor_id', $doctorId))
                ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
                ->orderBy('start_time')->get();
            $rows = $appointments->map(fn (Appointment $item): array => [
                $item->appointment_date->format('Y-m-d'), substr($item->start_time, 0, 5), $item->patient->patient_number,
                $item->patient->first_name.' '.$item->patient->last_name, $item->doctor->user->name,
                $item->department->name, $item->status,
            ])->all();

            return ['headings' => ['Date', 'Time', 'Patient number', 'Patient', 'Doctor', 'Department', 'Status'], 'rows' => $rows, 'summary' => ['Appointments' => count($rows)]];
        }

        if ($report === 'doctor-workload') {
            $appointments = Appointment::query()->selectRaw('doctor_id, COUNT(*) as total, SUM(status = ?) as completed, SUM(status = ?) as cancelled, SUM(status = ?) as no_show', ['completed', 'cancelled', 'no_show'])
                ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
                ->when($doctorId !== null, fn ($query) => $query->where('doctor_id', $doctorId))
                ->groupBy('doctor_id')->with('doctor.user')->get();
            $rows = $appointments->map(fn (Appointment $item): array => [$item->doctor->user->name, $item->total, $item->completed, $item->cancelled, $item->no_show])->all();

            return ['headings' => ['Doctor', 'Appointments', 'Completed', 'Cancelled', 'No-show'], 'rows' => $rows, 'summary' => ['Doctors with appointments' => count($rows), 'Appointments' => $appointments->sum('total')]];
        }

        if ($report === 'payments') {
            $payments = Payment::query()->with(['patient', 'appointment.doctor.user', 'appointment.department'])
                ->whereBetween('created_at', [$from, $to])
                ->when($request->filled('method'), fn ($query) => $query->where('payment_method', $request->string('method')->toString()))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($doctorId !== null, fn ($query) => $query->whereHas('appointment', fn ($appointment) => $appointment->where('doctor_id', $doctorId)))
                ->when($request->filled('department_id'), fn ($query) => $query->whereHas('appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id'))))
                ->latest()->get();
            $rows = $payments->map(fn (Payment $item): array => [$item->reference_number, $item->created_at->format('Y-m-d'), $item->patient->patient_number, $item->patient->first_name.' '.$item->patient->last_name, $item->appointment->doctor->user->name, $item->payment_method, $item->amount, $item->amount_paid, $item->balance, $item->status])->all();

            return ['headings' => ['Reference', 'Date', 'Patient number', 'Patient', 'Doctor', 'Method', 'Fee', 'Received', 'Balance', 'Status'], 'rows' => $rows, 'summary' => ['Payments' => $payments->count(), 'Received (UGX)' => $payments->sum('amount_paid'), 'Outstanding (UGX)' => $payments->sum(fn (Payment $payment): float => $payment->balance)]];
        }

        if ($report === 'patient-visits') {
            $patientId = $request->integer('patient_id');
            if ($request->user()->hasRole('doctor')) {
                abort_unless($patientId > 0, 422, 'Select a patient you have treated.');
            }
            $visits = Consultation::query()->with(['patient', 'doctor.user', 'appointment'])
                ->whereBetween('created_at', [$from, $to])
                ->when($patientId, fn ($query) => $query->where('patient_id', $patientId))
                ->when($doctorId !== null, fn ($query) => $query->where('doctor_id', $doctorId))
                ->latest()->get();
            $rows = $visits->map(fn (Consultation $item): array => [$item->appointment->appointment_date->format('Y-m-d'), $item->patient->patient_number, $item->patient->first_name.' '.$item->patient->last_name, $item->doctor->user->name, $item->diagnosis, $item->appointment->status])->all();

            return ['headings' => ['Visit date', 'Patient number', 'Patient', 'Doctor', 'Diagnosis', 'Attendance'], 'rows' => $rows, 'summary' => ['Visits' => count($rows), 'Completed visits' => $visits->filter(fn (Consultation $item): bool => $item->appointment->status === 'completed')->count()]];
        }

        $appointments = Appointment::query()->selectRaw('status, COUNT(*) as total')
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->when($doctorId !== null, fn ($query) => $query->where('doctor_id', $doctorId))
            ->groupBy('status')->get()->keyBy('status');
        $statuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
        $rows = collect($statuses)->map(fn (string $status): array => [ucwords(str_replace('_', ' ', $status)), (int) ($appointments[$status]->total ?? 0)])->all();

        return ['headings' => ['Status', 'Appointments'], 'rows' => $rows, 'summary' => ['Total appointments' => $appointments->sum('total'), 'Completed' => (int) ($appointments['completed']->total ?? 0), 'Cancelled' => (int) ($appointments['cancelled']->total ?? 0), 'No-show' => (int) ($appointments['no_show']->total ?? 0)]];
    }

    /** @return array{0: string, 1: string} */
    private function reportDefinition(Request $request, string $report): array
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$title, $permission] = self::REPORTS[$report];
        abort_unless($request->user()->hasRole('admin') || $request->user()->can($permission), 403);

        return [$title, $permission];
    }
}
