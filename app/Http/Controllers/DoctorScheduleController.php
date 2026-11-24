<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages doctor availability schedules used by staff and appointment booking.
 */
class DoctorScheduleController extends Controller
{
    /** Shows the authenticated doctor's own schedule without exposing other doctors' schedules. */
    public function own(Request $request): View
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);
        $doctor->load(['user', 'department']);

        $schedules = $doctor->schedules()
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('doctor-schedules.own', compact('doctor', 'schedules'));
    }

    /**
     * Builds the doctor schedule listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $schedules = DoctorSchedule::query()
            ->with(['doctor.user', 'doctor.department'])
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->integer('doctor_id')))
            ->orderBy('doctor_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();
        $doctors = Doctor::with('user')->where('is_active', true)->orderBy('id')->get();

        return view('doctor-schedules.index', compact('schedules', 'doctors'));
    }

    /**
     * Returns the form or initial state needed to create a doctor schedule record.
     */
    public function create(): View
    {
        $doctors = Doctor::with('user')->where('is_active', true)->orderBy('id')->get();

        return view('doctor-schedules.create', ['doctors' => $doctors, 'schedule' => null]);
    }

    /**
     * Validates and persists a new doctor schedule record, then returns the appropriate response.
     */
    public function store(StoreDoctorScheduleRequest $request): RedirectResponse
    {
        DoctorSchedule::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule added.');
    }

    /**
     * Loads the doctor schedule record and edit data required by its form.
     */
    public function edit(DoctorSchedule $doctorSchedule): View
    {
        $doctors = Doctor::with('user')->where('is_active', true)->orderBy('id')->get();

        return view('doctor-schedules.edit', ['schedule' => $doctorSchedule, 'doctors' => $doctors]);
    }

    /**
     * Validates and applies changes to the doctor schedule record, preserving its related business rules.
     */
    public function update(UpdateDoctorScheduleRequest $request, DoctorSchedule $doctorSchedule): RedirectResponse
    {
        $doctorSchedule->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule updated.');
    }

    /**
     * Applies the deletion or archival rules for the doctor schedule record and reports the result.
     */
    public function destroy(DoctorSchedule $doctorSchedule): RedirectResponse
    {
        abort_unless(request()->user()->can('schedules.manage'), 403);
        $doctorSchedule->delete();

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule removed.');
    }
}
