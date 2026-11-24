<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\PaymentExemption;
use App\Models\PaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records clinic payments, tracks balances, and presents downloadable receipts and payment history.
 */
class PaymentController extends Controller
{
    /**
     * Builds the payment listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);
        $query = Payment::query()->with(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver', 'medicationOrder.consultation.appointment.doctor.user', 'medicationOrder.consultation.appointment.department', 'labOrder.items.test', 'labOrder.appointment.doctor.user']);

        if ($request->user()->hasRole('patient')) {
            $query->whereHas('patient', fn ($patient) => $patient->where('user_id', $request->user()->id));
        }

        $payments = $query
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->string('date_to')->toString()))
            ->when($request->filled('method'), fn ($builder) => $builder->where('payment_method', $request->string('method')->toString()))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')->toString()))
            ->when($request->filled('doctor_id'), fn ($builder) => $builder->where(fn ($payments) => $payments
                ->whereHas('appointment', fn ($appointment) => $appointment->where('doctor_id', $request->integer('doctor_id')))
                ->orWhereHas('medicationOrder.consultation.appointment', fn ($appointment) => $appointment->where('doctor_id', $request->integer('doctor_id')))
                ->orWhereHas('labOrder.appointment', fn ($appointment) => $appointment->where('doctor_id', $request->integer('doctor_id')))))
            ->when($request->filled('department_id'), fn ($builder) => $builder->where(fn ($payments) => $payments
                ->whereHas('appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id')))
                ->orWhereHas('medicationOrder.consultation.appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id')))
                ->orWhereHas('labOrder.appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id')))))
            ->when($request->filled('q'), function ($builder) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $builder->where(fn ($search) => $search->where('reference_number', 'like', $term)
                    ->orWhereHas('patient', fn ($patient) => $patient->where('patient_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $doctors = Doctor::with('user')->orderBy('id')->get();
        $departments = Department::orderBy('name')->get();

        return view('payments.index', compact('payments', 'doctors', 'departments'));
    }

    /**
     * Returns the form or initial state needed to create a payment record.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);
        $appointments = Appointment::query()
            ->with(['patient', 'doctor.user', 'payment'])
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->orderByDesc('appointment_date')
            ->limit(500)
            ->get();

        return view('payments.create', ['appointments' => $appointments, 'selectedAppointmentId' => $request->integer('appointment_id') ?: null]);
    }

    /**
     * Validates and persists a new payment record, then returns the appropriate response.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $payment = DB::transaction(function () use ($data, $request): Payment {
            $appointment = Appointment::query()->with('doctor')->whereKey($data['appointment_id'])->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('appointment_id', $appointment->id)->where('billing_type', 'appointment')->lockForUpdate()->first();
            $payment ??= new Payment(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);
            $amountPaid = round((float) $payment->amount_paid + (float) $data['amount_paid'], 2);
            $amount = (float) ($appointment->consultation_fee ?? $appointment->doctor->consultation_fee);

            if ($amountPaid + (float) $payment->amount_waived > $amount) {
                throw ValidationException::withMessages(['amount_paid' => 'The payment would exceed the appointment balance.']);
            }

            $payment->fill([
                'billing_type' => 'appointment',
                'amount' => $amount,
                'amount_paid' => $amountPaid,
                'payment_method' => $data['payment_method'],
                'status' => $amountPaid + (float) $payment->amount_waived >= $amount ? 'paid' : 'partial',
                'reference_number' => $payment->exists ? $payment->reference_number : 'CCPAY-'.Str::upper(Str::random(10)),
                'paid_at' => now(),
                'received_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $payment->transactions()->create([
                'provider' => 'clinic',
                'merchant_reference' => 'CC-RCPT-'.Str::upper(Str::random(12)),
                'amount' => $data['amount_paid'],
                'currency' => 'UGX',
                'status' => 'completed',
                'payment_method' => $data['payment_method'],
                'status_description' => 'Payment received at the clinic.',
            ]);

            return $payment->refresh();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded.');
    }

    /** Waives the remaining appointment consultation fee and records who approved the offer and why. */
    public function exemptAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()->can('payments.exempt'), 403);
        abort_unless($request->user()->hasRole('admin') || $request->user()->hasRole('doctor'), 403);

        $data = $request->validate([
            'reason' => ['required', \Illuminate\Validation\Rule::in(array_keys(PaymentExemption::REASONS))],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = DB::transaction(function () use ($appointment, $data, $request): Payment {
            $lockedAppointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $doctorUserId = $lockedAppointment->doctor()->value('user_id');

            if ($request->user()->hasRole('doctor')) {
                abort_unless((int) $doctorUserId === (int) $request->user()->id, 403);
            }

            $fee = (float) ($lockedAppointment->consultation_fee ?? $lockedAppointment->doctor()->value('consultation_fee') ?? 0);
            abort_if($fee <= 0, 422, 'This appointment does not have a consultation fee to exempt.');
            abort_if(in_array($lockedAppointment->status, ['cancelled', 'no_show'], true), 409, 'A cancelled or missed appointment fee cannot be exempted here.');

            $payment = Payment::query()
                ->where('appointment_id', $lockedAppointment->id)
                ->where('billing_type', 'appointment')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $payment = Payment::query()->create([
                    'appointment_id' => $lockedAppointment->id,
                    'patient_id' => $lockedAppointment->patient_id,
                    'billing_type' => 'appointment',
                    'amount' => $fee,
                    'amount_paid' => 0,
                    'amount_waived' => 0,
                    'payment_method' => null,
                    'status' => 'pending',
                    'reference_number' => 'CCPAY-'.Str::upper(Str::random(10)),
                    'received_by' => null,
                ]);
            }

            abort_if($payment->status === 'refunded', 409, 'A refunded consultation fee cannot be exempted.');
            abort_if($payment->balance <= 0, 409, 'This consultation fee is already settled.');
            abort_if($payment->transactions()->whereIn('status', ['pending', 'review'])->exists(), 409, 'Resolve the in-progress online payment before applying an exemption.');

            $waivedAmount = $payment->balance;
            PaymentExemption::query()->create([
                'payment_id' => $payment->id,
                'amount' => $waivedAmount,
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'exempted_by' => $request->user()->id,
            ]);

            $payment->amount_waived = round((float) $payment->amount_waived + $waivedAmount, 2);
            $payment->status = 'paid';
            $payment->save();

            return $payment;
        });

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Consultation fee exempted and recorded. The patient can now view the consultation information.');
    }

    /**
     * Loads the requested payment record and presents its associated details.
     */
    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);
        $payment->load(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver', 'exemption.exemptedBy', 'medicationOrder.items.medicine', 'medicationOrder.consultation.appointment.doctor.user', 'medicationOrder.consultation.appointment.department', 'labOrder.items.test', 'labOrder.appointment.doctor.user', 'labOrder.consultation.appointment.payment', 'transactions' => fn ($query) => $query->latest()]);

        return view('payments.show', compact('payment'));
    }

    /**
     * Loads the payment record and edit data required by its form.
     */
    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);
        abort_unless($payment->billing_type === 'appointment', 404);
        $payment->load(['patient', 'appointment.doctor.user']);

        return view('payments.edit', compact('payment'));
    }

    /**
     * Validates and applies changes to the payment record, preserving its related business rules.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->billing_type === 'appointment', 404);
        $data = $request->validated();
        $amountPaid = (float) $data['amount_paid'];

        DB::transaction(function () use ($payment, $data, $amountPaid, $request): void {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedPayment->transactions()->exists() || $lockedPayment->exemption()->exists(), 409, 'This payment already has a receipt or exemption audit record. Record a separate payment or contact an administrator to correct the ledger.');

            if ($amountPaid + (float) $lockedPayment->amount_waived > (float) $lockedPayment->amount) {
                throw ValidationException::withMessages(['amount_paid' => 'The payment cannot exceed the appointment fee.']);
            }

            $lockedPayment->update([
                'amount_paid' => $amountPaid,
                'payment_method' => $amountPaid > 0 ? $data['payment_method'] : null,
                'status' => $amountPaid + (float) $lockedPayment->amount_waived >= (float) $lockedPayment->amount ? 'paid' : ($amountPaid > 0 ? 'partial' : 'pending'),
                'paid_at' => $amountPaid > 0 ? now() : null,
                'received_by' => $amountPaid > 0 ? $request->user()->id : null,
                'notes' => $data['notes'],
            ]);

            if ($amountPaid > 0) {
                $lockedPayment->transactions()->create([
                    'provider' => 'clinic',
                    'merchant_reference' => 'CC-RCPT-'.Str::upper(Str::random(12)),
                    'amount' => $amountPaid,
                    'currency' => 'UGX',
                    'status' => 'completed',
                    'payment_method' => $data['payment_method'],
                    'status_description' => 'Payment recorded at the clinic.',
                ]);
            }
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated.');
    }

    /** Authorizes access and streams a PDF receipt showing the payment's current balance and history. */
    public function receipt(Payment $payment): Response
    {
        $this->authorize('view', $payment);
        $payment->load(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver', 'exemption.exemptedBy', 'medicationOrder.items.medicine', 'medicationOrder.consultation.appointment.doctor.user', 'medicationOrder.consultation.appointment.department', 'labOrder.items.test', 'labOrder.appointment.doctor.user', 'labOrder.consultation.appointment.payment']);

        return Pdf::loadView('payments.receipt', ['payment' => $payment, 'receiptTransaction' => null])->setPaper('a4')->download('receipt-'.$payment->reference_number.'.pdf');
    }

    /** Streams a PDF receipt for one completed transaction after confirming it belongs to this payment. */
    public function transactionReceipt(Payment $payment, PaymentTransaction $paymentTransaction): Response
    {
        $this->authorize('view', $payment);
        abort_unless($paymentTransaction->payment_id === $payment->id && $paymentTransaction->status === 'completed', 404);
        $payment->load(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver', 'medicationOrder.items.medicine', 'medicationOrder.consultation.appointment.doctor.user', 'medicationOrder.consultation.appointment.department', 'labOrder.items.test', 'labOrder.appointment.doctor.user', 'labOrder.consultation.appointment.payment']);

        return Pdf::loadView('payments.receipt', ['payment' => $payment, 'receiptTransaction' => $paymentTransaction])->setPaper('a4')->download('receipt-'.$paymentTransaction->merchant_reference.'.pdf');
    }

    /** Records an in-clinic medication payment atomically and adds a completed receipt transaction. */
    public function storeMedication(Request $request, \App\Models\MedicationOrder $medicationOrder): RedirectResponse
    {
        $this->authorize('create', Payment::class);
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payment_method' => ['required', \Illuminate\Validation\Rule::in(Payment::METHODS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = DB::transaction(function () use ($data, $request, $medicationOrder): Payment {
            $order = \App\Models\MedicationOrder::query()->whereKey($medicationOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->reviewed_at, 409, 'The pharmacist must review this prescription before payment can be recorded.');
            $payment = Payment::query()->where('medication_order_id', $order->id)->lockForUpdate()->firstOrFail();
            $paid = round((float) $payment->amount_paid + (float) $data['amount_paid'], 2);
            if ($paid + (float) $payment->amount_waived > (float) $payment->amount) {
                throw ValidationException::withMessages(['amount_paid' => 'The payment would exceed the medication balance.']);
            }

            $payment->update([
                'amount_paid' => $paid,
                'payment_method' => $data['payment_method'],
                'status' => $paid + (float) $payment->amount_waived >= (float) $payment->amount ? 'paid' : 'partial',
                'paid_at' => now(),
                'received_by' => $request->user()->id,
                'notes' => $data['notes'] ?? $payment->notes,
            ]);
            $payment->transactions()->create([
                'provider' => 'clinic',
                'merchant_reference' => 'CC-RCPT-'.Str::upper(Str::random(12)),
                'amount' => $data['amount_paid'],
                'currency' => 'UGX',
                'status' => 'completed',
                'payment_method' => $data['payment_method'],
                'status_description' => 'Medication payment received at the clinic.',
            ]);

            return $payment->refresh();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Medication payment recorded. Download its receipt below.');
    }

    /** Records an in-clinic laboratory payment and queues the order once its balance is fully settled. */
    public function storeLab(Request $request, \App\Models\LabOrder $labOrder): RedirectResponse
    {
        $this->authorize('create', Payment::class);
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', \Illuminate\Validation\Rule::in(Payment::METHODS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = DB::transaction(function () use ($data, $request, $labOrder): Payment {
            $order = \App\Models\LabOrder::query()->whereKey($labOrder->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('lab_order_id', $order->id)->lockForUpdate()->firstOrFail();
            $paid = round((float) $payment->amount_paid + (float) $data['amount_paid'], 2);
            if ($paid + (float) $payment->amount_waived > (float) $payment->amount) {
                throw ValidationException::withMessages(['amount_paid' => 'The payment would exceed the laboratory balance.']);
            }
            $payment->update(['amount_paid' => $paid, 'payment_method' => $data['payment_method'], 'status' => $paid + (float) $payment->amount_waived >= (float) $payment->amount ? 'paid' : 'partial', 'paid_at' => now(), 'received_by' => $request->user()->id, 'notes' => $data['notes'] ?? $payment->notes]);
            $payment->transactions()->create(['provider' => 'clinic', 'merchant_reference' => 'CC-RCPT-'.Str::upper(Str::random(12)), 'amount' => $data['amount_paid'], 'currency' => 'UGX', 'status' => 'completed', 'payment_method' => $data['payment_method'], 'status_description' => 'Laboratory payment received at the clinic.']);
            if ($payment->status === 'paid') {
                $order->update(['status' => 'queued']);
            }

            return $payment->refresh();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Laboratory payment recorded.');
    }
}
