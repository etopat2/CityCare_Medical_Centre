<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wider text-teal-700">Patient records</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Register patient</h2>
        </div>
    </x-slot>

    <div class="space-y-5 py-8">
        @if ($duplicates->isNotEmpty())
            <script>
                document.addEventListener('DOMContentLoaded', () => window.CityCareToast(
                    @json('Possible duplicate records: '.$duplicates->map(fn ($patient) => $patient->patient_number.' / '.$patient->first_name.' '.$patient->last_name.' / '.$patient->phone)->implode('; ')),
                    'warning',
                ));
            </script>
        @endif

        @include('patients._form', ['patient' => null])
    </div>
</x-app-layout>
