<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Patient portal</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">My visit history</h2>
        </div>
    </x-slot>

    <div class="space-y-5 py-8">
        @forelse($visits as $visit)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $visit->appointment->appointment_date->format('l, j F Y') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $visit->appointment->department->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Dr. {{ $visit->doctor->user->name }}</p>
                    </div>
                    @if($visit->appointment->consultationFeeSettled() && $visit->follow_up_date)
                        <div class="rounded-xl bg-teal-50 px-3 py-2 text-sm text-teal-900">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-teal-700">Follow-up</span>
                            <span class="mt-0.5 block font-medium">{{ $visit->follow_up_date->format('j M Y') }}</span>
                        </div>
                    @endif
                </div>

                @if($visit->appointment->consultationFeeSettled())
                <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Symptoms</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $visit->symptoms }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnosis</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $visit->diagnosis }}</dd>
                    </div>
                    @if($visit->treatment_plan)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Treatment plan</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $visit->treatment_plan }}</dd>
                        </div>
                    @endif
                    @if($visit->prescription)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prescription</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $visit->prescription }}</dd>
                        </div>
                    @endif
                    @if($visit->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visit notes</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $visit->notes }}</dd>
                        </div>
                    @endif
                </dl>
                @if($visit->labOrder)<section class="mt-5 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4"><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-slate-900">Laboratory referral</h3><span class="text-sm capitalize text-slate-600">{{ str_replace('_', ' ', $visit->labOrder->status) }} · {{ ucfirst($visit->labOrder->payment?->status ?? 'unpaid') }}</span></div><ul class="mt-2 space-y-2">@foreach($visit->labOrder->items as $item)<li class="text-sm"><span class="font-medium">{{ $item->test->name }}</span>@if($item->result && $visit->labOrder->payment?->isSettled())<p class="mt-1 whitespace-pre-wrap text-slate-700">{{ $item->result }}</p>@elseif($item->result)<p class="mt-1 text-amber-800">Results are available after the laboratory bill is settled.</p>@endif</li>@endforeach</ul><a href="{{ route('lab.show', $visit->labOrder) }}" class="mt-3 inline-block text-sm font-semibold text-teal-800">View lab order & payment</a></section>@endif
                @else
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold">Consultation information is locked</p><p class="mt-1">Your symptoms, diagnosis, treatment plan, prescription, follow-up instructions, and consultation-linked referrals become available when the consultation fee is settled in full.</p><a href="{{ route('portal.payments') }}" class="mt-2 inline-block font-semibold text-teal-800 underline">Review your payments</a></div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700" aria-hidden="true"><i class="fa fa-heartbeat text-xl"></i></span>
                <p class="mt-4 font-semibold text-slate-900">No visits recorded yet</p>
                <p class="mt-1 text-sm text-slate-500">Your completed visit notes will appear here.</p>
            </div>
        @endforelse

        {{ $visits->links() }}
    </div>
</x-app-layout>
