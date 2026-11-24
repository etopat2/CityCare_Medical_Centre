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

/**
 * Coordinates appointment listing, booking, rescheduling, cancellation, and availability responses.
 */
class AppointmentController extends Controller
{
    /**
     * Builds the appointment listing with the applicable filters, summary data, and pagination.
     */
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

        $doctors = Doctor::query()->with('user')->orderBy('id')->get();
        $departments = Department::query()->orderBy('name')->get();

        return view('appointments.index', compact('appointments', 'doctors', 'departments'));
    }

    /**
     * Returns the form or initial state needed to create a appointment record.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Appointment::class);
        $patient = $request->user()->hasRole('patient') ? $request->user()->patient : ($request->filled('patient_id') ? Patient::find($request->integer('patient_id')) : null);
        abort_if($request->user()->hasRole('patient') && ! $patient, 404);
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->orderBy('id')->get();

        return view('appointments.create', ['appointment' => null, 'patient' => $patient, 'doctors' => $doctors]);
    }

    /**
     * Validates and persists a new appointment record, then returns the appropriate response.
     */
    public function store(StoreAppointmentRequest $request, AppointmentService $service): RedirectResponse
    {
        $appointment = $service->book($request->validated(), $request->user());

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment booked.');
    }

    /**
     * Loads the requested appointment record and presents its associated details.
     */
    public function show(Appointment $appointment): View
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor.user', 'department', 'consultation', 'payment.exemption']);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Loads the appointment record and edit data required by its form.
     */
    public function edit(Appointment $appointment): View
    {
        $this->authorize('update', $appointment);
        $appointment->load('patient');
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->orderBy('id')->get();

        return view('appointments.edit', ['appointment' => $appointment, 'patient' => $appointment->patient, 'doctors' => $doctors]);
    }

    /**
     * Validates and applies changes to the appointment record, preserving its related business rules.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->book($request->validated(), $request->user(), $appointment);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment rescheduled.');
    }

    /** Cancels an appointment with role-aware authorization and the appropriate cancellation reason. */
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

    /** Changes an appointment status after enforcing valid transitions and patient access boundaries. */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['status' => ['required', Rule::in(['scheduled', 'confirmed', 'no_show'])]]);
        $appointment->update(['status' => $data['status']]);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment status updated.');
    }

    /** Returns JSON time slots for the selected doctor and date, excluding elapsed or already-booked times. */
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

    /**
     * Returns matching patient options by name, patient number, or phone for authorized appointment forms.
     */
    public function searchPatients(Request $request): JsonResponse
    {
        abort_unless(! $request->user()->hasRole('patient') && ($request->user()->can('appointments.create') || $request->user()->can('payments.create')), 403);
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

    /** Returns active doctors for the selected department as options for appointment forms. */
    public function doctorsByDepartment(Request $request, Department $department): JsonResponse
    {
        abort_unless($request->user()->can('doctors.view'), 403);
        $doctors = $department->doctors()->with('user')->where('is_active', true)->orderBy('id')->get(['id', 'user_id', 'department_id', 'specialization']);

        return response()->json(['doctors' => $doctors->map(fn (Doctor $doctor): array => (new DoctorOptionResource($doctor))->resolve($request))->all()]);
    }
}
