@php($currentPatient = $appointment?->patient ?? $patient)
<form method="POST" action="{{ $appointment ? route('appointments.update',$appointment) : route('appointments.store') }}" data-slots-url="{{ route('api.doctors.available-slots',['doctor'=>0]) }}" data-search-url="{{ route('api.patients.search') }}" data-doctors-url="{{ url('/api/departments') }}" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf @if($appointment) @method('PUT') @endif
    @if($appointment)<input type="hidden" name="appointment_id" value="{{ $appointment->id }}">@endif
    <div class="grid gap-5 md:grid-cols-2">
        <div class="relative md:col-span-2"><x-input-label for="patient-search" value="Find patient"/><input id="patient-search" type="search" autocomplete="off" value="{{ old('patient_search',$currentPatient ? $currentPatient->first_name.' '.$currentPatient->last_name.' · '.$currentPatient->patient_number : '') }}" placeholder="Search name, patient number or phone" class="mt-1 block w-full rounded-lg border-slate-300" aria-controls="patient-results" aria-expanded="false"><input id="patient_id" type="hidden" name="patient_id" value="{{ old('patient_id',$currentPatient?->id) }}"><div id="patient-results" role="listbox" class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"></div><p id="patient-selection" class="mt-1 text-xs text-slate-500">Choose a saved patient record.</p><x-input-error :messages="$errors->get('patient_id')" class="mt-2"/></div>
        <div><x-input-label for="department-filter" value="Department"/><select id="department-filter" class="mt-1 block w-full rounded-lg border-slate-300"><option value="">All departments</option>@foreach($doctors->pluck('department')->unique('id') as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
        <div><x-input-label for="doctor_id" value="Doctor"/><select id="doctor_id" name="doctor_id" class="mt-1 block w-full rounded-lg border-slate-300" required><option value="">Choose a doctor</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" data-department="{{ $doctor->department_id }}" @selected(old('doctor_id',$appointment?->doctor_id)===$doctor->id)>{{ $doctor->user->name }} · {{ $doctor->specialization }}</option>@endforeach</select><x-input-error :messages="$errors->get('doctor_id')" class="mt-2"/></div>
        <div><x-input-label for="appointment_date" value="Appointment date"/><x-text-input id="appointment_date" name="appointment_date" type="date" min="{{ now()->toDateString() }}" class="mt-1 block w-full" :value="old('appointment_date',$appointment?->appointment_date?->format('Y-m-d'))" required/><x-input-error :messages="$errors->get('appointment_date')" class="mt-2"/></div>
        <div><x-input-label for="start_time" value="Available time"/><select id="start_time" name="start_time" class="mt-1 block w-full rounded-lg border-slate-300" required disabled><option value="">Choose a doctor and date first</option></select><p id="availability" aria-live="polite" class="mt-1 text-xs text-slate-500">Select a doctor and date to check availability.</p><x-input-error :messages="$errors->get('start_time')" class="mt-2"/></div>
        <div class="md:col-span-2"><x-input-label for="reason" value="Reason for visit"/><textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('reason',$appointment?->reason) }}</textarea><x-input-error :messages="$errors->get('reason')" class="mt-2"/></div>
    </div>
    <div class="flex gap-3"><button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">{{ $appointment ? 'Save changes' : 'Book appointment' }}</button><a href="{{ route('appointments.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-slots-url]');
    if (!form) return;
    const patientSearch = document.getElementById('patient-search');
    const patientId = document.getElementById('patient_id');
    const patientResults = document.getElementById('patient-results');
    const patientSelection = document.getElementById('patient-selection');
    const doctorSelect = document.getElementById('doctor_id');
    const departmentFilter = document.getElementById('department-filter');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('start_time');
    const availability = document.getElementById('availability');
    const appointmentId = form.querySelector('[name="appointment_id"]')?.value;
    const currentPatient = patientId.value ? patientSearch.value : '';
    let searchTimer;

    const loadSlots = async () => {
        timeSelect.innerHTML = '<option value="">Loading available slots…</option>';
        timeSelect.disabled = true;
        availability.textContent = 'Checking doctor availability…';
        if (!doctorSelect.value || !dateInput.value) {
            timeSelect.innerHTML = '<option value="">Choose a doctor and date first</option>';
            availability.textContent = 'Select a doctor and date to check availability.';
            return;
        }
        const url = new URL(form.dataset.slotsUrl.replace('/0/', `/${doctorSelect.value}/`), window.location.origin);
        url.searchParams.set('date', dateInput.value);
        if (appointmentId) url.searchParams.set('appointment_id', appointmentId);
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not load available times.');
            timeSelect.replaceChildren(new Option(result.slots.length ? 'Choose a time' : 'No times available', ''));
            result.slots.forEach(slot => timeSelect.add(new Option(`${slot.start_time} – ${slot.end_time}`, slot.start_time)));
            timeSelect.disabled = result.slots.length === 0;
            availability.textContent = result.available ? `Doctor available · ${result.free_slots} free slots` : 'Doctor is unavailable for the selected date.';
            availability.className = `mt-1 text-xs ${result.available ? 'text-emerald-700' : 'text-rose-700'}`;
            const desired = @json(old('start_time', $appointment?->start_time ? substr($appointment->start_time,0,5) : ''));
            if (desired && [...timeSelect.options].some(option => option.value === desired)) timeSelect.value = desired;
        } catch (error) {
            timeSelect.innerHTML = '<option value="">Unable to load times</option>';
            availability.textContent = error.message;
            availability.className = 'mt-1 text-xs text-rose-700';
        }
    };

    const renderPatients = patients => {
        patientResults.replaceChildren();
        patients.forEach(patient => {
            const button = document.createElement('button');
            button.type = 'button';
            button.role = 'option';
            button.className = 'block w-full border-b border-slate-100 px-4 py-3 text-left text-sm hover:bg-teal-50';
            button.textContent = `${patient.first_name} ${patient.last_name} · ${patient.patient_number} · ${patient.phone}`;
            button.addEventListener('click', () => {
                patientId.value = patient.id;
                patientSearch.value = `${patient.first_name} ${patient.last_name} · ${patient.patient_number}`;
                patientSelection.textContent = 'Patient record selected.';
                patientResults.classList.add('hidden');
                patientSearch.setAttribute('aria-expanded', 'false');
            });
            patientResults.append(button);
        });
        patientResults.classList.toggle('hidden', patients.length === 0);
        patientSearch.setAttribute('aria-expanded', patients.length ? 'true' : 'false');
    };

    patientSearch.addEventListener('input', () => {
        patientId.value = '';
        patientSelection.textContent = 'Choose a saved patient record.';
        clearTimeout(searchTimer);
        const query = patientSearch.value.trim();
        if (query.length < 2) { patientResults.classList.add('hidden'); return; }
        searchTimer = setTimeout(async () => {
            try {
                const url = new URL(form.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', query);
                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Patient search failed.');
                renderPatients(result.patients);
            } catch (error) { patientSelection.textContent = error.message; }
        }, 250);
    });

    departmentFilter.addEventListener('change', () => {
        [...doctorSelect.options].forEach(option => {
            if (!option.value) return;
            option.hidden = Boolean(departmentFilter.value && option.dataset.department !== departmentFilter.value);
        });
        if (doctorSelect.selectedOptions[0]?.hidden) doctorSelect.value = '';
        loadSlots();
    });
    doctorSelect.addEventListener('change', loadSlots);
    dateInput.addEventListener('change', loadSlots);
    form.addEventListener('submit', event => { if (!patientId.value) { event.preventDefault(); patientSelection.textContent = 'Select a patient from the search results.'; patientSelection.className = 'mt-1 text-xs text-rose-700'; } });
    if (currentPatient) patientSelection.textContent = 'Patient record selected.';
    if (doctorSelect.value && dateInput.value) loadSlots();
});
</script>
