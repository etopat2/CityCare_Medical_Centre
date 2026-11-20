<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Patient::class);
        $query = Patient::query()->with('user');

        if ($request->user()->hasRole('doctor')) {
            $query->whereHas('consultations', fn ($consultations) => $consultations->where('doctor_id', $request->user()->doctor?->id ?? 0));
        } elseif ($request->user()->hasRole('patient')) {
            $query->where('user_id', $request->user()->id);
        }

        $patients = $query
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $builder->where(fn ($search) => $search->where('patient_number', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term));
            })
            ->when($request->filled('gender'), fn ($builder) => $builder->where('gender', $request->string('gender')->toString()))
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', compact('patients'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Patient::class);
        $duplicates = collect();

        if ($request->filled(['phone', 'date_of_birth'])) {
            $duplicates = Patient::query()
                ->where('phone', $request->string('phone')->toString())
                ->orWhere(fn ($query) => $query->where('first_name', $request->string('first_name')->toString())
                    ->where('last_name', $request->string('last_name')->toString())
                    ->whereDate('date_of_birth', $request->date('date_of_birth')))
                ->limit(5)
                ->get();
        }

        return view('patients.create', compact('duplicates'));
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $patient = DB::transaction(function () use ($data): Patient {
            $user = null;

            if (! empty($data['portal_account'])) {
                $user = User::create([
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['portal_password'],
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                $user->assignRole('patient');
            }

            return Patient::create([
                ...collect($data)->except(['portal_account', 'portal_password'])->all(),
                'user_id' => $user?->id,
                'patient_number' => null,
            ]);
        });

        return redirect()->route('patients.show', $patient)->with('success', 'Patient record created.');
    }

    public function show(Patient $patient): View
    {
        $this->authorize('view', $patient);
        $patient->load(['consultations' => fn ($query) => $query->with(['doctor.user', 'appointment'])->latest(), 'appointments.doctor.user', 'payments']);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        $this->authorize('update', $patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();
        $patient->update($data);

        if ($patient->user) {
            $patient->user->update([
                'name' => $patient->first_name.' '.$patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone,
            ]);
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Patient record updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);
        $patient->delete();
        $patient->user?->update(['is_active' => false]);

        return redirect()->route('patients.index')->with('success', 'Patient record archived.');
    }
}
