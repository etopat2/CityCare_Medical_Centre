<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Administration</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-900">Clinic pricing</h2>
            <p class="mt-1 text-sm text-slate-500">Consultation fees are set per doctor. Medicine prices are managed in the stock catalogue.</p>
        </div>
    </x-slot>

    <div class="space-y-6 py-8">
        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="border-b p-5">
                <h3 class="font-semibold">Appointment consultation fees</h3>
                <p class="mt-1 text-sm text-slate-500">Admins can update these prices at any time. Changes apply to new bookings; booked appointments keep their original fee.</p>
                <form method="GET" action="{{ route('pricing.index') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="doctor-fee-search" class="sr-only">Search doctors by name, specialty, or department</label>
                    <input id="doctor-fee-search" type="search" name="q_doctors" value="{{ $doctorSearch }}" placeholder="Search doctors, specialty, or department" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm" autocomplete="off">
                    <p class="text-xs text-slate-500" aria-live="polite">Results update from 2 characters.</p>
                    <button class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700">Search</button>
                    <a href="{{ route('pricing.index', request()->except(['q_doctors', 'doctors_page'])) }}" class="rounded-lg border px-4 py-2 text-center text-sm">Clear</a>
                </form>
            </div>
            <div class="divide-y">
                @forelse($doctors as $doctor)
                    <form method="POST" action="{{ route('pricing.doctors.update', $doctor) }}" class="grid items-center gap-3 p-4 sm:grid-cols-[1fr_12rem_auto]">
                        @csrf @method('PUT')
                        <div><p class="font-medium">{{ $doctor->user->name }}</p><p class="text-xs text-slate-500">{{ $doctor->specialization }} · {{ $doctor->department->name }}</p></div>
                        <label class="text-xs font-semibold text-slate-600">Consultation fee (UGX)<input name="consultation_fee" type="number" min="0" step="0.01" value="{{ $doctor->consultation_fee }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><span class="mt-1 block font-normal text-slate-500">Enter any amount, including decimals.</span></label>
                        <button class="rounded-lg border border-teal-300 px-4 py-2 text-sm font-semibold text-teal-800">Update fee</button>
                    </form>
                @empty
                    <div class="p-8 text-center text-slate-500">No doctors match this search.</div>
                @endforelse
            </div>
            @if($doctors->hasPages())<div class="border-t p-4">{{ $doctors->links() }}</div>@endif
        </section>

        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="border-b p-5">
                <h3 class="font-semibold">Laboratory test prices</h3>
                <p class="mt-1 text-sm text-slate-500">Updated prices apply to new orders. Existing orders retain their quoted amount.</p>
                <form method="GET" action="{{ route('pricing.index') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="lab-price-search" class="sr-only">Search laboratory tests by name, code, or specimen</label>
                    <input id="lab-price-search" type="search" name="q_lab_tests" value="{{ $labTestSearch }}" placeholder="Search tests, codes, or specimen" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm" autocomplete="off">
                    <p class="text-xs text-slate-500" aria-live="polite">Results update from 2 characters.</p>
                    <button class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700">Search</button>
                    <a href="{{ route('pricing.index', request()->except(['q_lab_tests', 'lab_tests_page'])) }}" class="rounded-lg border px-4 py-2 text-center text-sm">Clear</a>
                </form>
            </div>
            <div class="divide-y">
                @forelse($labTests as $test)
                    <form method="POST" action="{{ route('pricing.lab-tests.update', $test) }}" class="grid items-center gap-3 p-4 sm:grid-cols-[1fr_12rem_auto]">
                        @csrf @method('PUT')
                        <div><p class="font-medium">{{ $test->name }}</p><p class="text-xs text-slate-500">{{ $test->code }} · {{ $test->specimen }}</p></div>
                        <label class="text-xs font-semibold text-slate-600">Price (UGX)<input name="price" type="number" min="0" step="0.01" value="{{ $test->price }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                        <button class="rounded-lg border border-teal-300 px-4 py-2 text-sm font-semibold text-teal-800">Update price</button>
                    </form>
                @empty
                    <div class="p-8 text-center text-slate-500">No tests match this search.</div>
                @endforelse
            </div>
            @if($labTests->hasPages())<div class="border-t p-4">{{ $labTests->links() }}</div>@endif
        </section>

        <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-teal-100 bg-teal-50 p-5">
            <div><h3 class="font-semibold text-teal-950">Drug pricing and stock</h3><p class="mt-1 text-sm text-teal-900">Manage medicine prices, availability, and reorder levels.</p></div>
            <a href="{{ route('medicines.index') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Open medicine catalogue</a>
            <a href="{{ route('lab.index') }}" class="rounded-lg border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-800">Manage test catalogue</a>
        </section>
    </div>
</x-app-layout>
