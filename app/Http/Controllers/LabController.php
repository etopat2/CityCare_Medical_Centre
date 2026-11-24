<?php

namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Manages laboratory test pricing, patient orders, specimen workflow, and recorded results.
 */
class LabController extends Controller
{
    /**
     * Builds the lab listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $orders = LabOrder::query()->with(['patient', 'appointment.doctor.user', 'consultation.appointment.doctor', 'consultation.appointment.payment', 'items.test', 'payment'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->user()->hasRole('patient'), fn ($q) => $q->where('patient_id', $request->user()->patient?->id ?? 0))
            ->latest()->paginate(20)->withQueryString();
        $tests = $request->user()->can('lab.manage') ? LabTest::query()->orderBy('name')->get() : collect();

        if ($request->user()->hasRole('patient')) {
            return view('portal.labs', compact('orders'));
        }

        return view('laboratory.index', compact('orders', 'tests'));
    }

    /**
     * Loads the requested lab record and presents its associated details.
     */
    public function show(LabOrder $labOrder): View
    {
        $labOrder->load(['patient', 'appointment.doctor.user', 'consultation.doctor.user', 'consultation.appointment.doctor', 'consultation.appointment.payment', 'items.test', 'payment']);
        if (auth()->user()->hasRole('patient')) {
            abort_unless($labOrder->patient?->user_id === auth()->id(), 404);
            abort_if($labOrder->consultation && ! $labOrder->consultation->appointment?->consultationFeeSettled(), 403, 'Settle the consultation fee to view information from this clinical referral.');
            return view('portal.lab-order', compact('labOrder'));
        }

        return view('laboratory.show', compact('labOrder'));
    }

    /** Loads active laboratory tests and profile details for a patient booking. */
    public function bookingForm(): View
    {
        abort_unless(auth()->user()->can('portal.lab-orders.create'), 403);
        $tests = LabTest::query()->where('is_active', true)->orderBy('name')->get();

        return view('portal.lab-book', compact('tests'));
    }

    /** Creates a patient-initiated laboratory order and its payable bill from the selected active tests. */
    public function book(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('portal.lab-orders.create'), 403);
        $data = $request->validate(['lab_tests' => ['required', 'array', 'min:1', 'max:20'], 'lab_tests.*' => ['required', 'integer', 'distinct', Rule::exists('lab_tests', 'id')->where('is_active', true)]]);
        $patient = Patient::query()->where('user_id', $request->user()->id)->firstOrFail();
        $order = DB::transaction(fn () => $this->createOrder($patient, collect($data['lab_tests'])->map(fn ($id) => (int) $id)->all(), null, null, $request->user()->id));

        return redirect()->route('lab.show', $order)->with('success', 'Lab tests booked. Pay the lab bill before sample collection.');
    }

    /**
     * Validates and applies changes to the lab record, preserving its related business rules.
     */
    public function update(Request $request, LabOrder $labOrder): RedirectResponse
    {
        $data = $request->validate(['items' => ['required', 'array'], 'items.*' => ['required', 'array:result'], 'items.*.result' => ['required', 'string', 'max:10000']]);
        DB::transaction(function () use ($request, $labOrder, $data): void {
            $order = LabOrder::query()->whereKey($labOrder->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('lab_order_id', $order->id)->lockForUpdate()->first();
            abort_unless($payment && $payment->status === 'paid' && $payment->balance <= 0, 402, 'The complete laboratory bill must be paid before testing or clearance.');
            abort_if($order->status === 'cancelled', 409, 'This lab order has been cancelled.');
            $items = $order->items()->lockForUpdate()->get();
            $submitted = collect($data['items'])->mapWithKeys(fn ($row, $id) => [(int) $id => $row['result']]);
            abort_unless($submitted->keys()->sort()->values()->all() === collect($items->modelKeys())->sort()->values()->all(), 422, 'Enter a result for every ordered test.');

            foreach ($items as $item) {
                $item->update(['result' => $submitted[$item->id], 'status' => 'completed', 'performed_by' => $request->user()->id, 'performed_at' => now()]);
            }
            $order->update(['status' => 'completed', 'collected_by' => $order->collected_by ?? $request->user()->id, 'collected_at' => $order->collected_at ?? now(), 'completed_by' => $request->user()->id, 'completed_at' => now()]);
        });

        return redirect()->route('lab.show', $labOrder)->with('success', 'Results saved. The laboratory order is complete.');
    }

    /** Adds a laboratory test to the catalogue with its code, specimen, and starting price. */
    public function storeTest(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40', 'unique:lab_tests,code'], 'name' => ['required', 'string', 'max:150'], 'specimen' => ['nullable', 'string', 'max:100'], 'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99']]);
        LabTest::query()->create($data + ['is_active' => true]);

        return back()->with('success', 'Laboratory test added.');
    }

    /** Updates the laboratory test catalogue fields used by future orders. */
    public function updateTest(Request $request, LabTest $labTest): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'specimen' => ['nullable', 'string', 'max:100'], 'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'], 'is_active' => ['required', 'boolean']]);
        $labTest->update($data);

        return back()->with('success', 'Laboratory test and price updated. Existing orders keep their quoted price.');
    }

    /** @param array<int, int> $testIds */
    public function createOrder(Patient $patient, array $testIds, ?int $consultationId, ?int $appointmentId, ?int $orderedBy): LabOrder
    {
        $tests = LabTest::query()->whereIn('id', $testIds)->where('is_active', true)->get();
        if ($tests->count() !== count(array_unique($testIds)) || $tests->isEmpty()) {
            throw ValidationException::withMessages(['lab_tests' => 'Select at least one active laboratory test.']);
        }
        $total = round((float) $tests->sum(fn ($test) => (float) $test->price), 2);
        $order = LabOrder::query()->create(['patient_id' => $patient->id, 'consultation_id' => $consultationId, 'appointment_id' => $appointmentId, 'ordered_by' => $orderedBy, 'status' => $total > 0 ? 'pending_payment' : 'queued', 'amount' => $total]);
        foreach ($tests as $test) {
            $order->items()->create(['lab_test_id' => $test->id, 'price' => $test->price, 'status' => 'pending']);
        }
        $order->payment()->create([
            'appointment_id' => null, 'medication_order_id' => null, 'billing_type' => 'laboratory', 'patient_id' => $patient->id,
            'amount' => $total, 'amount_paid' => 0, 'payment_method' => null, 'status' => $total > 0 ? 'pending' : 'paid',
            'reference_number' => 'CCLAB-'.Str::upper(Str::random(10)), 'received_by' => null,
            'notes' => 'Laboratory tests'.($consultationId ? ' referred from consultation '.$consultationId : ' booked by patient'),
        ]);

        return $order;
    }
}
