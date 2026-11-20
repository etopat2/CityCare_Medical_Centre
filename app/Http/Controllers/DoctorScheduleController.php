<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
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

    public function create(): View
    {
        $doctors = Doctor::with('user')->where('is_active', true)->orderBy('id')->get();

        return view('doctor-schedules.create', ['doctors' => $doctors, 'schedule' => null]);
    }

    public function store(StoreDoctorScheduleRequest $request): RedirectResponse
    {
        DoctorSchedule::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule added.');
    }

    public function edit(DoctorSchedule $doctorSchedule): View
    {
        $doctors = Doctor::with('user')->where('is_active', true)->orderBy('id')->get();

        return view('doctor-schedules.edit', ['schedule' => $doctorSchedule, 'doctors' => $doctors]);
    }

    public function update(UpdateDoctorScheduleRequest $request, DoctorSchedule $doctorSchedule): RedirectResponse
    {
        $doctorSchedule->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule updated.');
    }

    public function destroy(DoctorSchedule $doctorSchedule): RedirectResponse
    {
        abort_unless(request()->user()->can('schedules.manage'), 403);
        $doctorSchedule->delete();

        return redirect()->route('doctor-schedules.index')->with('success', 'Doctor schedule removed.');
    }
}
