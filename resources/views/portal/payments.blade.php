<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold text-slate-900">My payments</h2></x-slot>
    <div class="space-y-5 py-8">
        @forelse($payments as $payment)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $payment->reference_number }}</p>
                        <h3 class="mt-1 font-semibold text-slate-900">{{ $payment->appointment->appointment_date->format('j M Y') }} · Dr. {{ $payment->appointment->doctor->user->name }}</h3>
                        <p class="mt-1 text-sm capitalize text-slate-600">{{ $payment->payment_method ? str_replace('_', ' ', $payment->payment_method) : 'Awaiting payment method' }} · {{ $payment->status }}</p>
                        <p class="mt-1 text-sm text-slate-500">Paid UGX {{ number_format((float) $payment->amount_paid) }} of UGX {{ number_format((float) $payment->amount) }}</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Balance due</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">UGX {{ number_format($payment->balance) }}</p>
                        @if($payment->balance > 0 && ! in_array($payment->appointment->status, ['cancelled', 'no_show'], true) && filled(config('services.pesapal.ipn_id')))
                            <form method="POST" action="{{ route('portal.appointments.pay', $payment->appointment) }}" class="mt-3">@csrf<button class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white">Pay balance with Pesapal</button></form>
                        @endif
                        @if($payment->amount_paid > 0)<a class="mt-2 inline-block text-sm font-medium text-teal-700" href="{{ route('payments.receipt', $payment) }}">View receipt</a>@endif
                    </div>
                </div>
                @if($payment->transactions->isNotEmpty())
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesapal attempts</h4>
                        <ul class="mt-2 space-y-2 text-sm">
                            @foreach($payment->transactions as $transaction)
                                <li class="flex flex-wrap items-center justify-between gap-2">
                                    <span>{{ $transaction->merchant_reference }} · {{ $transaction->status_description ?: ucfirst($transaction->status) }}</span>
                                    <span class="font-medium">UGX {{ number_format((float) $transaction->amount) }}</span>
                                    @if($transaction->status === 'pending' && filled($transaction->tracking_id))
                                        <form method="POST" action="{{ route('portal.payments.refresh', $transaction) }}">@csrf<button class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700">Check status</button></form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <p class="font-medium text-slate-800">No payments recorded yet</p>
                <p class="mt-1 text-sm text-slate-500">Online and clinic payments will appear here.</p>
            </div>
        @endforelse
        {{ $payments->links() }}
    </div>
</x-app-layout>
