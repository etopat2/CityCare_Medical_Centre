<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Payment receipt</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $payment->reference_number }}</h2></div>
            <div class="flex gap-2">
                <a href="{{ route('payments.receipt', $payment) }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa fa-download me-2" aria-hidden="true"></i>Download summary PDF</a>
                @can('update', $payment)@if($payment->billing_type === 'appointment' && $payment->transactions->isEmpty() && ! $payment->exemption)<a href="{{ route('payments.edit', $payment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Edit</a>@endif @endcan
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl py-8">
        <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
            <div><p class="text-sm text-slate-500">Patient</p><p class="mt-1 font-semibold">{{ $payment->patient->first_name }} {{ $payment->patient->last_name }}</p><p class="text-sm text-slate-500">{{ $payment->patient->patient_number }}</p></div>
            <div><p class="text-sm text-slate-500">Charge type</p><p class="mt-1 font-semibold">{{ ['medication' => 'Medication fee', 'laboratory' => 'Laboratory tests'][$payment->billing_type] ?? 'Appointment / consultation fee' }}</p></div>
            <div><p class="text-sm text-slate-500">Appointment</p><p class="mt-1 font-semibold">{{ $payment->visit_appointment?->appointment_date?->format('j M Y') ?? 'Clinic visit' }} | {{ $payment->visit_appointment?->doctor?->user?->name ?? '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Amount charged</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $payment->amount) }}</p></div>
            <div><p class="text-sm text-slate-500">Amount received</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $payment->amount_paid) }}</p></div>
            @if((float) $payment->amount_waived > 0)<div><p class="text-sm text-slate-500">Fee waived</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $payment->amount_waived) }}</p></div>@endif
            <div><p class="text-sm text-slate-500">Balance</p><p class="mt-1 font-semibold">UGX {{ number_format($payment->balance) }}</p></div>
            <div><p class="text-sm text-slate-500">Method / status</p><p class="mt-1 font-semibold capitalize">{{ $payment->payment_method ? str_replace('_', ' ', $payment->payment_method) : 'Awaiting online payment' }} | {{ $payment->status }}</p></div>
            <div class="sm:col-span-2"><p class="text-sm text-slate-500">Received by</p><p class="mt-1">{{ $payment->receiver?->name ?? ($payment->amount_paid > 0 ? 'Pesapal online' : 'Not received') }} | {{ $payment->paid_at?->format('j M Y, g:i A') }}</p></div>
        </section>

        @if($payment->exemption)
            <section class="mt-5 rounded-2xl border border-teal-200 bg-teal-50 p-5 shadow-sm">
                <h3 class="font-semibold text-teal-950">Consultation fee exemption</h3>
                <p class="mt-2 text-sm text-teal-900"><strong>Reason:</strong> {{ \App\Models\PaymentExemption::REASONS[$payment->exemption->reason] ?? ucfirst(str_replace('_', ' ', $payment->exemption->reason)) }}</p>
                <p class="mt-1 text-sm text-teal-900"><strong>Amount waived:</strong> UGX {{ number_format((float) $payment->exemption->amount) }}</p>
                @if($payment->exemption->details)<p class="mt-1 whitespace-pre-line text-sm text-teal-900"><strong>Details:</strong> {{ $payment->exemption->details }}</p>@endif
                <p class="mt-2 text-xs text-teal-800">Approved by {{ $payment->exemption->exemptedBy?->name ?? 'Clinic staff' }} on {{ $payment->exemption->created_at?->format('j M Y, g:i A') }}</p>
            </section>
        @endif

        @if($payment->billing_type === 'medication' && $payment->medicationOrder)
            @php($prescriptionDetailsVisible = ! auth()->user()->hasRole('patient') || ! $payment->medicationOrder->consultation || $payment->medicationOrder->consultation->appointment?->consultationFeeSettled())
            <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Medication bill</h3>
                @if($prescriptionDetailsVisible)
                    <ul class="mt-3 divide-y divide-slate-100">@foreach($payment->medicationOrder->items->whereIn('status', ['ready', 'dispensed']) as $item)<li class="flex justify-between gap-4 py-2 text-sm"><span>{{ $item->medicine->name }} {{ $item->medicine->strength }} · {{ $item->quantity }} × UGX {{ number_format((float) $item->unit_price) }}</span><strong>UGX {{ number_format((float) $item->line_total) }}</strong></li>@endforeach</ul>
                @else
                    <p class="mt-3 text-sm text-amber-800">Prescription details are available after your consultation fee is settled.</p>
                @endif
                @if($payment->balance > 0)
                    @can('payments.create')
                        <form method="POST" action="{{ route('medication-orders.payments.store', $payment->medicationOrder) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">
                            @csrf
                            <label class="text-sm font-medium text-slate-700">Amount received (UGX)<input name="amount_paid" type="number" min="0.01" max="{{ $payment->balance }}" step="0.01" required value="{{ old('amount_paid', $payment->balance) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
                            <label class="text-sm font-medium text-slate-700">Payment method<select name="payment_method" required class="mt-1 w-full rounded-lg border-slate-300">@foreach(\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select></label>
                            <div class="flex items-end"><button class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white">Record medication payment</button></div>
                            <label class="text-sm font-medium text-slate-700 sm:col-span-3">Notes (optional)<input name="notes" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300"></label>
                        </form>
                    @endcan
                @endif
            </section>
        @endif

        @if($payment->billing_type === 'laboratory' && $payment->labOrder)
            @php($labDetailsVisible = ! auth()->user()->hasRole('patient') || ! $payment->labOrder->consultation || $payment->labOrder->consultation->appointment?->consultationFeeSettled())
            <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Laboratory bill</h3>
                @if($labDetailsVisible)<ul class="mt-3 divide-y divide-slate-100">@foreach($payment->labOrder->items as $item)<li class="flex justify-between gap-4 py-2 text-sm"><span>{{ $item->test->name }} · {{ $item->test->specimen }}</span><strong>UGX {{ number_format((float) $item->price) }}</strong></li>@endforeach</ul>@else<p class="mt-3 text-sm text-amber-800">Referral details are available after your consultation fee is settled.</p>@endif
                @if($payment->balance > 0)@can('payments.create')
                    <form method="POST" action="{{ route('lab-orders.payments.store', $payment->labOrder) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">@csrf
                        <label class="text-sm font-medium text-slate-700">Amount received (UGX)<input name="amount_paid" type="number" min="0.01" max="{{ $payment->balance }}" step="0.01" required value="{{ old('amount_paid', $payment->balance) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
                        <label class="text-sm font-medium text-slate-700">Payment method<select name="payment_method" required class="mt-1 w-full rounded-lg border-slate-300">@foreach(\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select></label>
                        <div class="flex items-end"><button class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white">Record lab payment</button></div>
                        <label class="text-sm font-medium text-slate-700 sm:col-span-3">Notes (optional)<input name="notes" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300"></label>
                    </form>
                @endcan @endif
            </section>
        @endif

        @if($payment->transactions->isNotEmpty())
            <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Pesapal transactions</h3>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($payment->transactions as $transaction)
                        <div class="flex flex-wrap justify-between gap-3 py-3 text-sm first:pt-0 last:pb-0">
                            <div><p class="font-medium">{{ $transaction->merchant_reference }} · {{ ucfirst($transaction->status) }}</p><p class="mt-1 text-slate-500">{{ $transaction->status_description ?: 'No additional status details' }}</p>@if($transaction->confirmation_code)<p class="mt-1 text-slate-500">Confirmation: {{ $transaction->confirmation_code }}</p>@endif</div>
                            <div class="text-right"><p class="font-semibold">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</p>@if($transaction->status === 'completed')<a href="{{ route('payments.transaction-receipt', [$payment, $transaction]) }}" class="mt-1 inline-block text-xs font-semibold text-teal-700">Download receipt PDF</a>@endif</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
