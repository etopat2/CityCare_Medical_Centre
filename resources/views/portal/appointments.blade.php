<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold text-slate-900">My appointments</h2></x-slot>
    <div class="space-y-5 py-8">
        @forelse($appointments as $appointment)
            @php
                $balance = $appointment->payment
                    ? $appointment->payment->balance
                    : (float) $appointment->doctor->consultation_fee;
                $canPay = $balance > 0 && ! in_array($appointment->status, ['cancelled', 'no_show'], true);
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $appointment->appointment_date->format('l, j F Y') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $appointment->department->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Dr. {{ $appointment->doctor->user->name }} · {{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                        <p class="mt-2 text-sm capitalize text-slate-500">Appointment: {{ str_replace('_', ' ', $appointment->status) }} · Payment: {{ ucfirst($appointment->payment?->status ?? 'unpaid') }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Balance</p>
                        <p class="mt-1 text-xl font-semibold text-slate-900">UGX {{ number_format($balance) }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 sm:justify-end">
                            <a href="{{ route('appointments.show', $appointment) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">Details</a>
                            @if($canPay && filled(config('services.pesapal.ipn_id')))
                                <form method="POST" action="{{ route('portal.appointments.pay', $appointment) }}">@csrf<button class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800">Pay with Pesapal</button></form>
                            @endif
                        </div>
                        @if($canPay && blank(config('services.pesapal.ipn_id')))<p class="mt-2 text-xs text-amber-700">Online payment is not configured yet.</p>@endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <p class="font-medium text-slate-800">No appointments yet</p>
                <p class="mt-1 text-sm text-slate-500">Your appointments will appear here once booked.</p>
            </div>
        @endforelse
        {{ $appointments->links() }}
    </div>
</x-app-layout>
