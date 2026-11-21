<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">Reports</a>
                <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ $title }}</h2>
            </div>
            @can('reports.export')
                <div class="flex flex-wrap gap-2">
                    @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'csv' => 'CSV'] as $format => $label)
                        <a href="{{ route('reports.show', [$report, ...request()->query(), 'export' => $format]) }}"
                            @if ($format === 'pdf') target="_blank" rel="noopener noreferrer" aria-label="Preview {{ $title }} PDF in a new tab" @endif
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-teal-600 hover:text-teal-800">
                            <i class="fa {{ $format === 'pdf' ? 'fa-file-pdf-o' : ($format === 'xlsx' ? 'fa-file-excel-o' : 'fa-file-text-o') }}" aria-hidden="true"></i>
                            Export {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
            @if ($report === 'daily-appointments')
                <label class="text-sm text-slate-600">Date<input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
            @else
                <label class="text-sm text-slate-600">From<input type="date" name="date_from" value="{{ request('date_from', now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
                <label class="text-sm text-slate-600">To<input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
            @endif

            @if (! auth()->user()->hasRole('doctor') && $report !== 'patient-visits')
                <label class="text-sm text-slate-600">Doctor<select name="doctor_id" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All doctors</option>@foreach ($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((string) request('doctor_id') === (string) $doctor->id)>{{ $doctor->user->name }}</option>@endforeach</select></label>
            @endif

            @if (in_array($report, ['daily-appointments', 'payments'], true))
                <label class="text-sm text-slate-600">Department<select name="department_id" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All departments</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></label>
            @endif

            @if ($report === 'payments')
                <label class="text-sm text-slate-600">Method<select name="method" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All methods</option>@foreach (\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select></label>
                <label class="text-sm text-slate-600">Status<select name="status" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All statuses</option>@foreach (\App\Models\Payment::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            @endif

            @if ($report === 'patient-visits')
                <label class="text-sm text-slate-600">Patient<select name="patient_id" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All treated patients</option>@foreach ($patients as $patient)<option value="{{ $patient->id }}" @selected((string) request('patient_id') === (string) $patient->id)>{{ $patient->patient_number }} | {{ $patient->first_name }} {{ $patient->last_name }}</option>@endforeach</select></label>
            @endif

            <div class="flex items-end gap-2"><button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"><i class="fa fa-filter me-2" aria-hidden="true"></i>Apply filters</button><a href="{{ route('reports.show', $report) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700">Clear</a></div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($data['summary'] as $label => $value)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ is_numeric($value) && str_contains($label, '(UGX)') ? 'UGX '.number_format((float) $value) : number_format((float) $value) }}</p></article>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr>@foreach ($data['headings'] as $heading)<th class="whitespace-nowrap px-5 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($data['rows'] as $row)
                        <tr>@foreach ($row as $value)<td class="whitespace-nowrap px-5 py-3">{{ $value }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($data['headings']) }}" class="px-5 py-12 text-center text-slate-500">No records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
