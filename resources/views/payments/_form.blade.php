<form method="POST" action="{{ $payment ? route('payments.update',$payment) : route('payments.store') }}" data-search-url="{{ route('api.patients.search') }}" class="space-y-5 rounded-2xl border bg-white p-6 shadow-sm">
    @csrf @if($payment) @method('PUT') @endif
    @if(!$payment)<div class="relative"><x-input-label for="patient-search" value="Find patient"/><input id="patient-search" type="search" autocomplete="off" placeholder="Search name, patient number or phone" class="mt-1 block w-full rounded-lg border-slate-300" aria-controls="payment-patient-results"><div id="payment-patient-results" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-lg border bg-white shadow-lg"></div><p id="payment-patient-message" class="mt-1 text-xs text-slate-500">Choose the patient before selecting an appointment.</p></div>
    <div><x-input-label for="appointment_id" value="Appointment"/><select id="appointment_id" name="appointment_id" class="mt-1 block w-full rounded-lg border-slate-300" required><option value="">Choose an appointment</option>@foreach($appointments as $appointment)<option value="{{ $appointment->id }}" data-patient="{{ $appointment->patient_id }}" data-balance="{{ (float)$appointment->doctor->consultation_fee-(float)($appointment->payment?->amount_paid ?? 0) }}" @selected(old('appointment_id',$selectedAppointmentId ?? null)===$appointment->id)>{{ $appointment->appointment_date->format('j M Y') }} · {{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }} · {{ $appointment->doctor->user->name }} · balance UGX {{ number_format((float)$appointment->doctor->consultation_fee-(float)($appointment->payment?->amount_paid ?? 0)) }}</option>@endforeach</select><x-input-error :messages="$errors->get('appointment_id')" class="mt-2"/></div>@endif
    <div><x-input-label for="amount_paid" value="{{ $payment ? 'Correct total received (UGX)' : 'Amount received now (UGX)' }}"/><x-text-input id="amount_paid" name="amount_paid" type="number" min="{{ $payment ? '0' : '0.01' }}" step="1" class="mt-1 block w-full" :value="old('amount_paid',$payment?->amount_paid)" required/><p class="mt-1 text-xs text-slate-500">{{ $payment ? 'Set the cumulative amount currently received.' : 'Partial payments are accepted and the remaining balance will be calculated.' }}</p><x-input-error :messages="$errors->get('amount_paid')" class="mt-2"/></div>
    <div><x-input-label for="payment_method" value="Payment method"/><select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-lg border-slate-300" required>@foreach(\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(old('payment_method',$payment?->payment_method)===$method)>{{ ucfirst(str_replace('_',' ',$method)) }}</option>@endforeach</select><x-input-error :messages="$errors->get('payment_method')" class="mt-2"/></div>
    <div><x-input-label for="notes" value="Notes"/><textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('notes',$payment?->notes) }}</textarea><x-input-error :messages="$errors->get('notes')" class="mt-2"/></div>
    <div class="flex gap-3"><button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">{{ $payment ? 'Update payment' : 'Record payment' }}</button><a href="{{ route('payments.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a></div>
</form>
@unless($payment)<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-search-url]');
    const input = document.getElementById('patient-search');
    const results = document.getElementById('payment-patient-results');
    const message = document.getElementById('payment-patient-message');
    const appointment = document.getElementById('appointment_id');
    let timer;
    const filterAppointments = patientId => {
        [...appointment.options].forEach(option => { if (option.value) option.hidden = Boolean(patientId && option.dataset.patient !== String(patientId)); });
        appointment.value = '';
    };
    input.addEventListener('input', () => {
        filterAppointments('');
        clearTimeout(timer);
        if (input.value.trim().length < 2) { results.classList.add('hidden'); return; }
        timer = setTimeout(async () => {
            try {
                const url = new URL(form.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', input.value.trim());
                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Search failed.');
                results.replaceChildren();
                data.patients.forEach(patient => {
                    const button = document.createElement('button'); button.type = 'button'; button.className = 'block w-full px-4 py-3 text-left text-sm hover:bg-teal-50';
                    button.textContent = `${patient.first_name} ${patient.last_name} · ${patient.patient_number} · ${patient.phone}`;
                    button.addEventListener('click', () => { input.value = `${patient.first_name} ${patient.last_name} · ${patient.patient_number}`; filterAppointments(patient.id); message.textContent = 'Now choose one of this patient’s appointments.'; results.classList.add('hidden'); });
                    results.append(button);
                });
                results.classList.toggle('hidden', data.patients.length === 0);
                if (!data.patients.length) message.textContent = 'No patient records found.';
            } catch (error) { message.textContent = error.message; }
        }, 250);
    });
});
</script>@endunless
