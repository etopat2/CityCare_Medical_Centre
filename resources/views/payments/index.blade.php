<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-2xl font-semibold text-slate-900">Payments</h2>@can('payments.create')<a href="{{ route('payments.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Record payment</a>@endcan</div></x-slot>
    <div class="space-y-5 py-8">
        <form method="GET" class="grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <input name="q" value="{{ request('q') }}" placeholder="Patient name, number, phone or reference" class="rounded-lg border-slate-300 text-sm"><input name="date_from" type="date" value="{{ request('date_from') }}" class="rounded-lg border-slate-300 text-sm"><input name="date_to" type="date" value="{{ request('date_to') }}" class="rounded-lg border-slate-300 text-sm">
            <select name="doctor_id" class="rounded-lg border-slate-300 text-sm"><option value="">All doctors</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected(request('doctor_id') == $doctor->id)>{{ $doctor->user->name }}</option>@endforeach</select>
            <select name="department_id" class="rounded-lg border-slate-300 text-sm"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select>
            <select name="method" class="rounded-lg border-slate-300 text-sm"><option value="">All methods</option>@foreach(\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select>
            <select name="status" class="rounded-lg border-slate-300 text-sm"><option value="">All statuses</option>@foreach(\App\Models\Payment::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <div class="flex gap-2"><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Apply filters</button><a href="{{ route('payments.index') }}" class="rounded-lg border px-4 py-2 text-center text-sm">Clear</a></div>
        </form>
        <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm"><table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Patient</th><th class="px-5 py-3">Charge</th><th class="px-5 py-3">Appointment</th><th class="px-5 py-3">Received</th><th class="px-5 py-3">Balance</th><th class="px-5 py-3">Method / status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr><td class="px-5 py-4 font-medium">{{ $payment->reference_number }}</td><td class="px-5 py-4">{{ $payment->patient->first_name }} {{ $payment->patient->last_name }}<p class="text-xs text-slate-500">{{ $payment->patient->patient_number }}</p></td><td class="px-5 py-4">{{ ['medication' => 'Medication fee', 'laboratory' => 'Laboratory fee'][$payment->billing_type] ?? 'Appointment fee' }}</td><td class="px-5 py-4">{{ $payment->visit_appointment?->appointment_date?->format('j M Y') ?? '—' }} · {{ $payment->visit_appointment?->doctor?->user?->name ?? '—' }}</td><td class="px-5 py-4">UGX {{ number_format((float) $payment->amount_paid) }}@if((float) $payment->amount_waived > 0)<p class="text-xs text-teal-800">UGX {{ number_format((float) $payment->amount_waived) }} waived</p>@endif</td><td class="px-5 py-4">UGX {{ number_format($payment->balance) }}</td><td class="px-5 py-4">{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'Awaiting method' }}<p class="text-xs capitalize text-slate-500">{{ $payment->status }}</p></td><td class="px-5 py-4"><a class="font-medium text-teal-700" href="{{ route('payments.show', $payment) }}">View</a></td></tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">No payments match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        {{ $payments->links() }}
    </div>
</x-app-layout>
