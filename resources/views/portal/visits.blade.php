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
                    @if($visit->follow_up_date)
                        <div class="rounded-xl bg-teal-50 px-3 py-2 text-sm text-teal-900">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-teal-700">Follow-up</span>
                            <span class="mt-0.5 block font-medium">{{ $visit->follow_up_date->format('j M Y') }}</span>
                        </div>
                    @endif
                </div>

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
