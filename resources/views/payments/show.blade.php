<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Payment receipt</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $payment->reference_number }}</h2></div>
            <div class="flex gap-2">
                <a href="{{ route('payments.receipt', $payment) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa fa-external-link me-2" aria-hidden="true"></i>Preview receipt</a>
                @can('update', $payment)<a href="{{ route('payments.edit', $payment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Edit</a>@endcan
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl py-8">
        <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
            <div><p class="text-sm text-slate-500">Patient</p><p class="mt-1 font-semibold">{{ $payment->patient->first_name }} {{ $payment->patient->last_name }}</p><p class="text-sm text-slate-500">{{ $payment->patient->patient_number }}</p></div>
            <div><p class="text-sm text-slate-500">Appointment</p><p class="mt-1 font-semibold">{{ $payment->appointment->appointment_date->format('j M Y') }} | {{ $payment->appointment->doctor->user->name }}</p></div>
            <div><p class="text-sm text-slate-500">Amount charged</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $payment->amount) }}</p></div>
            <div><p class="text-sm text-slate-500">Amount received</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $payment->amount_paid) }}</p></div>
            <div><p class="text-sm text-slate-500">Balance</p><p class="mt-1 font-semibold">UGX {{ number_format($payment->balance) }}</p></div>
            <div><p class="text-sm text-slate-500">Method / status</p><p class="mt-1 font-semibold capitalize">{{ $payment->payment_method ? str_replace('_', ' ', $payment->payment_method) : 'Awaiting online payment' }} | {{ $payment->status }}</p></div>
            <div class="sm:col-span-2"><p class="text-sm text-slate-500">Received by</p><p class="mt-1">{{ $payment->receiver?->name ?? ($payment->amount_paid > 0 ? 'Pesapal online' : 'Not received') }} | {{ $payment->paid_at?->format('j M Y, g:i A') }}</p></div>
        </section>

        @if($payment->transactions->isNotEmpty())
            <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Pesapal transactions</h3>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($payment->transactions as $transaction)
                        <div class="flex flex-wrap justify-between gap-3 py-3 text-sm first:pt-0 last:pb-0">
                            <div><p class="font-medium">{{ $transaction->merchant_reference }} · {{ ucfirst($transaction->status) }}</p><p class="mt-1 text-slate-500">{{ $transaction->status_description ?: 'No additional status details' }}</p>@if($transaction->confirmation_code)<p class="mt-1 text-slate-500">Confirmation: {{ $transaction->confirmation_code }}</p>@endif</div>
                            <p class="font-semibold">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
