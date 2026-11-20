<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold text-slate-900">My clinic profile</h2></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8"><div class="rounded-2xl border bg-white p-6 shadow-sm"><div class="grid gap-5 sm:grid-cols-2">
        @foreach(['Patient number' => $patient->patient_number, 'Name' => $patient->first_name.' '.$patient->last_name, 'Gender' => ucfirst($patient->gender), 'Date of birth' => $patient->date_of_birth?->format('j M Y'), 'Phone' => $patient->phone, 'Email' => $patient->email, 'Address' => $patient->address, 'Blood group' => $patient->blood_group, 'Allergies' => $patient->allergies] as $label => $value)
            <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-1 text-slate-900">{{ $value ?: 'Not recorded' }}</p></div>
        @endforeach
    </div></div></div>
</x-app-layout>
