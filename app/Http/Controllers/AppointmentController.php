<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AvailableSlotResource;
use App\Http\Resources\DoctorOptionResource;
use App\Http\Resources\PatientSearchResource;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AppointmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);
        $query = Appointment::query()->with(['patient', 'doctor.user', 'department', 'payment']);

        if ($request->user()->hasRole('doctor')) {
            $query->whereHas('doctor', fn ($doctor) => $doctor->where('user_id', $request->user()->id));
        } elseif ($request->user()->hasRole('patient')) {
            $query->whereHas('patient', fn ($patient) => $patient->where('user_id', $request->user()->id));
        }

        $appointments = $query
            ->when($request->filled('date'), fn ($builder) => $builder->whereDate('appointment_date', $request->string('date')->toString()))
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('appointment_date', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('appointment_date', '<=', $request->string('date_to')->toString()))
            ->when($request->filled('doctor_id'), fn ($builder) => $builder->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('department_id'), fn ($builder) => $builder->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn ($builder) => $builder->whereHas('payment', fn ($payment) => $payment->where('status', $request->string('payment_status')->toString())))
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $builder->where(fn ($search) => $search
                    ->whereHas('patient', fn ($patient) => $patient->where('patient_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('phone', 'like', $term))
                    ->orWhereHas('doctor.user', fn ($doctor) => $doctor->where('name', 'like', $term)));
            })
            ->orderByDesc('appointment_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('appointments.index', compact('appointments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Appointment::class);
        $patient = $request->filled('patient_id') ? Patient::find($request->integer('patient_id')) : null;
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->orderBy('id')->get();

        return view('appointments.create', ['appointment' => null, 'patient' => $patient, 'doctors' => $doctors]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentService $service): RedirectResponse
    {
        $appointment = $service->book($request->validated(), $request->user());

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment booked.');
    }

    public function show(Appointment $appointment): View
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor.user', 'department', 'consultation', 'payment']);

        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        $this->authorize('update', $appointment);
        $appointment->load('patient');
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->orderBy('id')->get();

        return view('appointments.edit', ['appointment' => $appointment, 'patient' => $appointment->patient, 'doctors' => $doctors]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->book($request->validated(), $request->user(), $appointment);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment rescheduled.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:2000']]);

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true)) {
            throw ValidationException::withMessages(['appointment' => 'This appointment can no longer be cancelled.']);
        }

        $appointment->update(['status' => 'cancelled', 'cancellation_reason' => $data['cancellation_reason']]);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment cancelled.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['status' => ['required', Rule::in(['scheduled', 'confirmed', 'no_show'])]]);
        $appointment->update(['status' => $data['status']]);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment status updated.');
    }

    public function availableSlots(Request $request, Doctor $doctor, AppointmentService $service): JsonResponse
    {
        abort_unless($request->user()->can('appointments.create'), 403);
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);
        $ignoreAppointmentId = isset($data['appointment_id']) ? (int) $data['appointment_id'] : null;

        if ($ignoreAppointmentId) {
            $appointment = Appointment::findOrFail($ignoreAppointmentId);
            $this->authorize('update', $appointment);
        } else {
            abort_unless($request->user()->can('appointments.create'), 403);
        }

        $slots = $doctor->is_active ? $service->availableSlots($doctor, $data['date'], $ignoreAppointmentId) : [];

        return response()->json([
            'doctor_id' => $doctor->id,
            'date' => $data['date'],
            'available' => count($slots) > 0,
            'free_slots' => count($slots),
            'slots' => array_map(fn (array $slot): array => (new AvailableSlotResource($slot))->resolve($request), $slots),
        ]);
    }

    public function searchPatients(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('appointments.create') || $request->user()->can('payments.create'), 403);
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = '%'.$data['q'].'%';
        $patients = Patient::query()
            ->where(fn ($query) => $query->where('patient_number', 'like', $term)
                ->orWhere('phone', 'like', $term)
                ->orWhere('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term))
            ->orderBy('first_name')
            ->limit(10)
            ->get(['id', 'patient_number', 'first_name', 'last_name', 'phone']);

        return response()->json(['patients' => $patients->map(fn (Patient $patient): array => (new PatientSearchResource($patient))->resolve($request))->all()]);
    }

    public function doctorsByDepartment(Request $request, Department $department): JsonResponse
    {
        abort_unless($request->user()->can('doctors.view'), 403);
        $doctors = $department->doctors()->with('user')->where('is_active', true)->orderBy('id')->get(['id', 'user_id', 'department_id', 'specialization']);

        return response()->json(['doctors' => $doctors->map(fn (Doctor $doctor): array => (new DoctorOptionResource($doctor))->resolve($request))->all()]);
    }
}
