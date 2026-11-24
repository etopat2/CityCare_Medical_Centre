<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\LabTest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Displays clinic service prices and applies authorized appointment and laboratory price updates.
 */
class PricingController extends Controller
{
    /**
     * Builds the pricing listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $doctorSearch = trim((string) $request->query('q_doctors', ''));
        $labTestSearch = trim((string) $request->query('q_lab_tests', ''));

        $doctors = Doctor::query()
            ->with(['user', 'department'])
            ->when(mb_strlen($doctorSearch) >= 2, function (Builder $query) use ($doctorSearch) {
                $term = '%'.$doctorSearch.'%';
                $query->where(function (Builder $query) use ($term) {
                    $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $term))
                        ->orWhere('specialization', 'like', $term)
                        ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', $term));
                });
            })
            ->orderBy('id')
            ->paginate(20, ['*'], 'doctors_page')
            ->withQueryString();

        $labTests = LabTest::query()
            ->when(mb_strlen($labTestSearch) >= 2, function (Builder $query) use ($labTestSearch) {
                $term = '%'.$labTestSearch.'%';
                $query->where(function (Builder $query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('specimen', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20, ['*'], 'lab_tests_page')
            ->withQueryString();

        return view('pricing.index', compact('doctors', 'labTests', 'doctorSearch', 'labTestSearch'));
    }

    /** Updates a clinician's consultation fee; existing appointments retain their saved fee snapshot. */
    public function updateDoctor(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate(['consultation_fee' => ['required', 'numeric', 'min:0', 'max:9999999999.99']]);
        $doctor->update(['consultation_fee' => $data['consultation_fee']]);

        return back()->with('success', 'Appointment consultation price updated.');
    }

    /** Updates the price for new laboratory orders without changing existing quoted bills. */
    public function updateLabTest(Request $request, LabTest $labTest): RedirectResponse
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'min:0', 'max:9999999999.99']]);
        $labTest->update($data);

        return back()->with('success', 'Laboratory test price updated. Existing orders keep their quoted price.');
    }
}
