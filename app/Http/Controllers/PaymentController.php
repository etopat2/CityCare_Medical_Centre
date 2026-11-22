<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);
        $query = Payment::query()->with(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver']);

        if ($request->user()->hasRole('patient')) {
            $query->whereHas('patient', fn ($patient) => $patient->where('user_id', $request->user()->id));
        }

        $payments = $query
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->string('date_to')->toString()))
            ->when($request->filled('method'), fn ($builder) => $builder->where('payment_method', $request->string('method')->toString()))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')->toString()))
            ->when($request->filled('doctor_id'), fn ($builder) => $builder->whereHas('appointment', fn ($appointment) => $appointment->where('doctor_id', $request->integer('doctor_id'))))
            ->when($request->filled('department_id'), fn ($builder) => $builder->whereHas('appointment', fn ($appointment) => $appointment->where('department_id', $request->integer('department_id'))))
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

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $payment = DB::transaction(function () use ($data, $request): Payment {
            $appointment = Appointment::query()->with('doctor')->whereKey($data['appointment_id'])->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('appointment_id', $appointment->id)->lockForUpdate()->first();
            $payment ??= new Payment(['appointment_id' => $appointment->id, 'patient_id' => $appointment->patient_id]);
            $amountPaid = (float) $payment->amount_paid + (float) $data['amount_paid'];
            $amount = (float) $appointment->doctor->consultation_fee;

            if ($amountPaid > $amount) {
                throw ValidationException::withMessages(['amount_paid' => 'The payment would exceed the appointment balance.']);
            }

            $payment->fill([
                'amount' => $amount,
                'amount_paid' => $amountPaid,
                'payment_method' => $data['payment_method'],
                'status' => $amountPaid >= $amount ? 'paid' : 'partial',
                'reference_number' => $payment->exists ? $payment->reference_number : 'CCPAY-'.Str::upper(Str::random(10)),
                'paid_at' => now(),
                'received_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ])->save();

            return $payment->refresh();
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);
        $payment->load(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver', 'transactions' => fn ($query) => $query->latest()]);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);
        $payment->load(['patient', 'appointment.doctor.user']);

        return view('payments.edit', compact('payment'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $amountPaid = (float) $request->validated('amount_paid');

        if ($amountPaid > (float) $payment->amount) {
            throw ValidationException::withMessages(['amount_paid' => 'The payment cannot exceed the appointment fee.']);
        }

        $payment->update([
            'amount_paid' => $amountPaid,
            'payment_method' => $request->validated('payment_method'),
            'status' => $amountPaid === 0.0 ? 'pending' : ($amountPaid >= (float) $payment->amount ? 'paid' : 'partial'),
            'paid_at' => $amountPaid > 0 ? now() : null,
            'received_by' => $request->user()->id,
            'notes' => $request->validated('notes'),
        ]);

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function receipt(Payment $payment): Response
    {
        $this->authorize('view', $payment);
        $payment->load(['patient', 'appointment.doctor.user', 'appointment.department', 'receiver']);

        return Pdf::loadView('payments.receipt', compact('payment'))->setPaper('a4')->stream('receipt-'.$payment->reference_number.'.pdf');
    }
}
