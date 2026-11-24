<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Manages doctor profiles and their linked user accounts and departments.
 */
class DoctorController extends Controller
{
    /**
     * Builds the doctor listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $doctors = Doctor::query()
            ->with(['user', 'department'])
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($builder) => $builder->where('specialization', 'like', $term)
                    ->orWhere('license_number', 'like', $term)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term)));
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('doctors.index', compact('doctors', 'departments'));
    }

    /**
     * Returns the form or initial state needed to create a doctor record.
     */
    public function create(): View
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('doctors.create', compact('departments'));
    }

    /**
     * Validates and persists a new doctor record, then returns the appropriate response.
     */
    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $doctor = DB::transaction(function () use ($data): Doctor {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'] ?? Str::password(16),
                'email_verified_at' => now(),
                'is_active' => $data['is_active'] ?? true,
            ]);
            $user->assignRole('doctor');

            return Doctor::create([
                'user_id' => $user->id,
                'department_id' => $data['department_id'],
                'specialization' => $data['specialization'],
                'license_number' => $data['license_number'],
                'phone' => $data['phone'],
                'consultation_fee' => $data['consultation_fee'],
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });

        return redirect()->route('doctors.create')->with('success', 'Doctor account created. You can add another doctor now.');
    }

    /**
     * Loads the requested doctor record and presents its associated details.
     */
    public function show(Doctor $doctor): View
    {
        $doctor->load(['user', 'department', 'schedules']);

        return view('doctors.show', compact('doctor'));
    }

    /**
     * Loads the doctor record and edit data required by its form.
     */
    public function edit(Doctor $doctor): View
    {
        $doctor->load('user');
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('doctors.edit', compact('doctor', 'departments'));
    }

    /**
     * Validates and applies changes to the doctor record, preserving its related business rules.
     */
    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $doctor): void {
            $doctor->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_active' => $data['is_active'] ?? false,
                ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);
            $doctor->update([
                'department_id' => $data['department_id'],
                'specialization' => $data['specialization'],
                'license_number' => $data['license_number'],
                'phone' => $data['phone'],
                'consultation_fee' => $data['consultation_fee'],
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);
        });

        return redirect()->route('doctors.index')->with('success', 'Doctor updated.');
    }

    /**
     * Applies the deletion or archival rules for the doctor record and reports the result.
     */
    public function destroy(Doctor $doctor): RedirectResponse
    {
        abort_unless(request()->user()->can('doctors.manage'), 403);
        $doctor->update(['is_active' => false]);
        $doctor->user->update(['is_active' => false]);

        return redirect()->route('doctors.index')->with('success', 'Doctor account deactivated.');
    }
}
