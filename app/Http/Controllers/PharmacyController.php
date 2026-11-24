<?php

namespace App\Http\Controllers;

use App\Models\MedicationOrder;
use App\Models\LabOrder;
use App\Models\Medicine;
use App\Models\Payment;
use App\Models\PrescriptionItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Reviews medication orders, dispenses paid prescriptions, manages stock deductions, and clears eligible orders.
 */
class PharmacyController extends Controller
{
    /**
     * Builds the pharmacy listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $orders = MedicationOrder::query()
            ->with(['patient', 'consultation.appointment.doctor.user', 'payment'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pharmacy.index', compact('orders'));
    }

    /**
     * Loads the requested pharmacy record and presents its associated details.
     */
    public function show(MedicationOrder $medicationOrder): View
    {
        $medicationOrder->load(['patient', 'consultation.appointment.department', 'consultation.appointment.doctor.user', 'items.medicine', 'payment.transactions']);

        return view('pharmacy.show', compact('medicationOrder'));
    }

    /** Reviews every prescription line, reserves eligible stock, and prepares the separate medication bill. */
    public function review(Request $request, MedicationOrder $medicationOrder): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['required', 'array:status'],
            'items.*.status' => ['required', Rule::in(['ready', 'not_dispensed', 'out_of_stock'])],
        ]);

        $medicationOrder->load('items.medicine', 'consultation.appointment');
        abort_if($medicationOrder->cleared_at, 409, 'This patient has already been cleared.');
        if ($medicationOrder->payment && ($medicationOrder->payment->amount_paid > 0 || $medicationOrder->payment->transactions()->whereIn('status', ['pending', 'completed', 'review'])->exists())) {
            throw ValidationException::withMessages(['items' => 'This medication bill has payment activity and can no longer be changed.']);
        }

        $submitted = collect($data['items'])->mapWithKeys(fn (array $row, string|int $id): array => [(int) $id => $row['status']]);
        $itemIds = $medicationOrder->items->modelKeys();
        if ($submitted->keys()->sort()->values()->all() !== collect($itemIds)->sort()->values()->all()) {
            throw ValidationException::withMessages(['items' => 'Review every prescribed item before sending the bill to the cashier.']);
        }

        DB::transaction(function () use ($medicationOrder, $submitted): void {
            $order = MedicationOrder::query()->whereKey($medicationOrder->id)->lockForUpdate()->firstOrFail();
            $paymentActivity = Payment::query()->where('medication_order_id', $order->id)->lockForUpdate()->first();
            if ($paymentActivity && ($paymentActivity->amount_paid > 0 || $paymentActivity->transactions()->whereIn('status', ['pending', 'completed', 'review'])->exists())) {
                throw ValidationException::withMessages(['items' => 'This medication bill has payment activity and can no longer be changed.']);
            }
            $items = $order->items()->with('medicine')->lockForUpdate()->get();
            foreach ($items->where('status', 'ready') as $item) {
                $medicine = Medicine::query()->whereKey($item->medicine_id)->lockForUpdate()->firstOrFail();
                $medicine->decrement('reserved_quantity', $item->quantity);
            }

            $total = 0.0;
            foreach ($items as $item) {
                $status = $submitted[$item->id];
                if ($status === 'ready') {
                    $medicine = Medicine::query()->whereKey($item->medicine_id)->lockForUpdate()->firstOrFail();
                    if (! $medicine->is_active || $medicine->available_quantity < $item->quantity) {
                        throw ValidationException::withMessages(['items' => $medicine->name.' does not have enough available stock. Mark it out of stock or not dispensed.']);
                    }
                    $medicine->increment('reserved_quantity', $item->quantity);
                    $total += (float) $item->line_total;
                }
                $item->update(['status' => $status]);
            }

            $order->update([
                'amount' => $total,
                'status' => $total > 0 ? 'pending_payment' : 'ready_to_dispense',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            if ($total > 0) {
                $payment = Payment::query()->firstOrNew(['medication_order_id' => $order->id]);
                $payment->fill([
                    // Appointment payments have a unique appointment_id. Medication bills
                    // link through medication_order_id to support a second invoice per visit.
                    'appointment_id' => null,
                    'patient_id' => $order->patient_id,
                    'billing_type' => 'medication',
                    'amount' => $total,
                    'amount_paid' => 0,
                    'payment_method' => null,
                    'status' => 'pending',
                    'reference_number' => $payment->exists ? $payment->reference_number : 'CCMED-'.Str::upper(Str::random(10)),
                    'received_by' => null,
                    'notes' => 'Medication charge for consultation '.$order->consultation_id,
                ])->save();
            } elseif ($order->payment) {
                $order->payment->update(['amount' => 0, 'amount_paid' => 0, 'status' => 'paid', 'paid_at' => null]);
            }
        });

        return redirect()->route('pharmacy.show', $medicationOrder)->with('success', 'Prescription reviewed. Only medicines selected for dispensing have been added to the medication bill.');
    }

    /** Dispenses a reserved medicine only after its bill is fully paid, then decrements physical stock. */
    public function dispense(Request $request, PrescriptionItem $prescriptionItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $prescriptionItem): void {
            $item = PrescriptionItem::query()->whereKey($prescriptionItem->id)->lockForUpdate()->firstOrFail();
            $order = MedicationOrder::query()->whereKey($item->medication_order_id)->lockForUpdate()->firstOrFail();
            $payment = $order->payment()->lockForUpdate()->first();
            abort_unless($order->reviewed_at, 409, 'A pharmacist must review this prescription first.');
            abort_unless($order->amount <= 0 || ($payment && $payment->status === 'paid' && $payment->balance <= 0), 402, 'The medication fee must be paid in full before dispensing.');
            abort_unless($item->status === 'ready', 409, 'Only reviewed medicines marked for dispensing can be dispensed.');

            $medicine = Medicine::query()->whereKey($item->medicine_id)->lockForUpdate()->firstOrFail();
            if ($medicine->stock_quantity < $item->quantity || $medicine->reserved_quantity < $item->quantity) {
                throw ValidationException::withMessages(['stock' => 'Reserved stock is no longer available. Review the prescription and bill before dispensing.']);
            }
            $medicine->decrement('stock_quantity', $item->quantity);
            $medicine->decrement('reserved_quantity', $item->quantity);
            $item->update(['status' => 'dispensed', 'dispensed_by' => $request->user()->id, 'dispensed_at' => now()]);

            if (! $order->items()->whereNotIn('status', ['dispensed', 'not_dispensed', 'out_of_stock'])->exists()) {
                $order->update(['status' => 'dispensed']);
            }
        });

        return back()->with('success', 'Medicine dispensed and stock updated.');
    }

    /** Clears the patient only after each item is resolved and every consultation, lab, and medicine balance is paid. */
    public function clear(Request $request, MedicationOrder $medicationOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $medicationOrder): void {
            $order = MedicationOrder::query()->whereKey($medicationOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($order->cleared_at, 409, 'This patient has already been cleared.');
            abort_unless($order->reviewed_at, 409, 'Review the prescription before clearing the patient.');
            abort_if($order->items()->whereNotIn('status', ['dispensed', 'not_dispensed', 'out_of_stock'])->exists(), 409, 'Dispense or record a reason for every prescribed item before clearing the patient.');

            $payment = Payment::query()->where('medication_order_id', $order->id)->lockForUpdate()->first();
            abort_unless($order->amount <= 0 || ($payment && $payment->status === 'paid' && $payment->balance <= 0), 402, 'The full medication fee must be paid before the patient can be cleared.');
            $appointmentId = $order->consultation()->value('appointment_id');
            $pendingLab = $appointmentId ? LabOrder::query()->where('appointment_id', $appointmentId)->where('status', '!=', 'completed')->exists() : false;
            abort_unless(! $pendingLab, 409, 'Complete all referred laboratory tests after full payment before clearing this patient.');
            $unpaidVisitBills = false;
            if ($appointmentId) {
                $visitLabIds = LabOrder::query()->where('appointment_id', $appointmentId)->pluck('id');
                $unpaidVisitBills = Payment::query()
                    ->where(fn ($q) => $q->where('appointment_id', $appointmentId)->orWhereIn('lab_order_id', $visitLabIds))
                    ->whereRaw('(amount - amount_paid - amount_waived) > 0')
                    ->exists();
            }
            abort_unless(! $unpaidVisitBills, 402, 'All consultation, medication, and laboratory balances for this visit must be paid before clearance.');
            $order->update(['status' => 'cleared', 'cleared_by' => $request->user()->id, 'cleared_at' => now()]);
        });

        return redirect()->route('pharmacy.show', $medicationOrder)->with('success', 'Patient cleared after pharmacy review.');
    }
}
