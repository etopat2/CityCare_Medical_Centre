<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Consultation::class);
        $query = Consultation::query()->with(['patient', 'doctor.user', 'appointment']);

        if ($request->user()->hasRole('doctor')) {
            $query->whereHas('doctor', fn ($doctor) => $doctor->where('user_id', $request->user()->id));
        } elseif ($request->user()->hasRole('patient')) {
            $query->whereHas('patient', fn ($patient) => $patient->where('user_id', $request->user()->id));
        }

        $consultations = $query
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->string('date_to')->toString()))
            ->when($request->filled('doctor_id'), fn ($builder) => $builder->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('department_id'), fn ($builder) => $builder->whereHas('appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($builder) => $builder->whereHas('appointment', fn ($appointment) => $appointment->where('status', $request->string('status')->toString())))
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $builder->where(fn ($search) => $search->whereHas('patient', fn ($patient) => $patient->where('patient_number', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term))
                    ->orWhereHas('doctor.user', fn ($doctor) => $doctor->where('name', 'like', $term)));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $doctors = $request->user()->hasRole('doctor') ? collect() : Doctor::with('user')->orderBy('id')->get();
        $departments = Department::orderBy('name')->get();

        return view('consultations.index', compact('consultations', 'doctors', 'departments'));
    }

    public function create(Appointment $appointment): View
    {
        $this->authorize('create', [Consultation::class, $appointment]);

        abort_if($appointment->consultation()->exists(), 409, 'A consultation is already recorded for this appointment.');
        abort_if(in_array($appointment->status, ['cancelled', 'no_show'], true), 422, 'This appointment cannot be completed.');
        $appointment->load(['patient', 'doctor.user']);

        return view('consultations.create', compact('appointment'));
    }

    public function store(StoreConsultationRequest $request, Appointment $appointment): RedirectResponse
    {
        abort_if($appointment->consultation()->exists(), 409, 'A consultation is already recorded for this appointment.');

        DB::transaction(function () use ($request, $appointment): void {
            Consultation::create([
                ...$request->validated(),
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
            ]);
            $appointment->update(['status' => 'completed']);
        });

        return redirect()->route('appointments.show', $appointment)->with('success', 'Consultation saved and appointment marked completed.');
    }

    public function show(Consultation $consultation): View
    {
        $this->authorize('view', $consultation);
        $consultation->load(['appointment', 'doctor.user', 'patient']);

        return view('consultations.show', compact('consultation'));
    }

    public function edit(Consultation $consultation): View
    {
        $this->authorize('update', $consultation);
        $consultation->load(['appointment', 'patient', 'doctor.user']);

        return view('consultations.edit', compact('consultation'));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $consultation->update($request->validated());

        return redirect()->route('consultations.show', $consultation)->with('success', 'Consultation updated.');
    }
}
