<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-teal-700">{{ $doctor->department->name }}</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">My weekly schedule</h2><p class="mt-1 text-sm text-slate-500">{{ $doctor->user->name }}</p></div></x-slot>
    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        <p class="text-sm text-slate-600">Your active clinic availability and appointment slot lengths.</p>
        @php($dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])
        <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            @foreach($dayNames as $dayNumber => $dayName)
                @php($daySchedules = $schedules->where('day_of_week', $dayNumber))
                <section class="grid gap-3 border-b border-slate-100 p-5 last:border-b-0 sm:grid-cols-[9rem_1fr]">
                    <h3 class="font-semibold text-slate-900">{{ $dayName }}</h3>
                    @if($daySchedules->isNotEmpty())
                        <div class="space-y-2">@foreach($daySchedules as $schedule)<p class="text-sm text-slate-700">{{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('g:i A') }} <span class="text-slate-500">· {{ $schedule->slot_duration_minutes }} minute appointments</span></p>@endforeach</div>
                    @else
                        <p class="text-sm text-slate-500">No clinic hours scheduled.</p>
                    @endif
                </section>
            @endforeach
        </div>
        <a href="{{ route('reports.show', 'doctor-workload') }}" class="inline-flex rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-teal-800">View workload report</a>
    </div>
</x-app-layout>
