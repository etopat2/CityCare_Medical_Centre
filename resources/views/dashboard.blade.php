<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.18em] text-teal-700">{{ now()->format('l, j F Y') }}</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Here is your {{ $role }} workspace overview for today.</p>
        </div>
    </x-slot>

    <div class="space-y-8 py-8">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Summary">
            @foreach ($stats as $label => $value)
                @php
                    $lowerLabel = strtolower($label);
                    $icon = str_contains($lowerLabel, 'appoint') ? 'calendar-check-o' : (str_contains($lowerLabel, 'patient') || str_contains($lowerLabel, 'visit') ? 'users' : (str_contains($lowerLabel, 'revenue') || str_contains($lowerLabel, 'payment') || str_contains($lowerLabel, 'balance') ? 'credit-card' : 'user-md'));
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><span class="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-700"><i class="fa fa-{{ $icon }}" aria-hidden="true"></i></span></div>
                    <p class="mt-4 text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-2 {{ $canViewFinancialCharts ? '2xl:grid-cols-3' : '' }}" aria-label="Clinic charts">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div><h3 class="font-semibold text-slate-900">Appointment activity</h3><p class="mt-1 text-sm text-slate-500">Last seven days</p></div>
                    <span class="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-700"><i class="fa fa-line-chart" aria-hidden="true"></i></span>
                </div>
                <div class="relative mt-5 h-64"><canvas id="appointment-trend-chart" role="img" aria-label="Appointment activity over the last seven days"><span>Appointment activity chart</span></canvas></div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div><h3 class="font-semibold text-slate-900">Appointment status</h3><p class="mt-1 text-sm text-slate-500">This month</p></div>
                    <span class="grid size-10 place-items-center rounded-xl bg-cyan-50 text-cyan-700"><i class="fa fa-pie-chart" aria-hidden="true"></i></span>
                </div>
                <div class="relative mx-auto mt-5 h-64 max-w-sm"><canvas id="appointment-status-chart" role="img" aria-label="Appointment status totals for this month"><span>Appointment status chart</span></canvas></div>
            </article>

            @if ($canViewFinancialCharts)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div><h3 class="font-semibold text-slate-900">Payments received</h3><p class="mt-1 text-sm text-slate-500">Monthly totals for the last six months (UGX)</p></div>
                        <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><i class="fa fa-money" aria-hidden="true"></i></span>
                    </div>
                    <div class="relative mt-5 h-64"><canvas id="monthly-collections-chart" role="img" aria-label="Monthly payment totals for the last six months"><span>Monthly collections chart</span></canvas></div>
                </article>
            @endif
        </section>

        <script>
            window.CityCareDashboard = {{ \Illuminate\Support\Js::from($charts) }};
        </script>

        @if ($role === 'patient' && $patient)
            <section class="rounded-2xl border border-teal-100 bg-teal-50 p-5 sm:p-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">Patient profile</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-3">
                    <p><span class="font-medium">Patient number:</span> {{ $patient->patient_number }}</p>
                    <p><span class="font-medium">Phone:</span> {{ $patient->phone }}</p>
                    <p><span class="font-medium">Blood group:</span> {{ $patient->blood_group ?? 'Not recorded' }}</p>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h3 class="font-semibold text-slate-900">Today’s appointments</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ now()->format('l, j F Y') }}</p>
                </div>
                @if (Route::has('appointments.create'))
                    @can('appointments.create')
                    <a href="{{ route('appointments.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Book appointment</a>
                    @endcan
                @endif
            </div>
            @if ($appointments->isEmpty())
                <div class="px-6 py-10 text-center">
                    <p class="font-medium text-slate-800">No appointments scheduled for today</p>
                    <p class="mt-1 text-sm text-slate-500">New appointments will appear here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Time</th>
                                <th class="px-5 py-3">Patient</th>
                                <th class="px-5 py-3">Doctor</th>
                                <th class="px-5 py-3">Department</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($appointments as $appointment)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 font-medium">{{ \Illuminate\Support\Carbon::parse($appointment->start_time)->format('g:i A') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $appointment->doctor->user->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $appointment->department->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                            'bg-emerald-100 text-emerald-800' => $appointment->status === 'completed',
                                            'bg-rose-100 text-rose-800' => in_array($appointment->status, ['cancelled', 'no_show'], true),
                                            'bg-amber-100 text-amber-800' => in_array($appointment->status, ['scheduled', 'confirmed'], true),
                                        ])>{{ str_replace('_', ' ', $appointment->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($role === 'patient')
            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="font-semibold text-slate-900">Recent visit history</h3>
                    <div class="mt-4 space-y-4">
                        @forelse ($visits as $visit)
                            <article class="border-s-2 border-teal-200 ps-4">
                                <p class="font-medium text-slate-800">{{ $visit->diagnosis }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $visit->appointment->appointment_date->format('j M Y') }} · {{ $visit->doctor->user->name }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-slate-500">Your completed visits will appear here.</p>
                        @endforelse
                    </div>
                </section>
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="font-semibold text-slate-900">Payment status</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($payments as $payment)
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 text-sm last:border-0">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $payment->reference_number }}</p>
                                    <p class="text-slate-500">{{ $payment->visit_appointment?->appointment_date?->format('j M Y') ?? 'Clinic visit' }} · {{ $payment->billing_type === 'medication' ? 'Medication' : 'Appointment' }}</p>
                                </div>
                                <p class="font-semibold text-slate-800">UGX {{ number_format($payment->balance) }} due</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No payment records yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-app-layout>
