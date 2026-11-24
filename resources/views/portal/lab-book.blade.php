<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Patient portal</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">Book laboratory tests</h2><p class="mt-1 text-sm text-slate-500">Choose one or more tests. The tests are added to a lab order with a separate bill.</p></div></x-slot>
    <div class="mx-auto max-w-4xl py-8">
        <form method="POST" action="{{ route('portal.lab-orders.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
            <fieldset><legend class="font-semibold text-slate-900">Available tests</legend><p class="mt-1 text-sm text-slate-500">Select the tests you want to book. Sample collection and results are handled by the laboratory after payment.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($tests as $test)<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:border-teal-300"><input type="checkbox" name="lab_tests[]" value="{{ $test->id }}" @checked(in_array((string) $test->id, (array) old('lab_tests', []), true)) class="mt-1 rounded border-slate-300 text-teal-700"><span class="min-w-0 flex-1"><span class="block font-semibold text-slate-900">{{ $test->name }}</span><span class="mt-1 block text-xs text-slate-500">Specimen: {{ $test->specimen ?: 'Confirm with laboratory' }}</span></span><span class="whitespace-nowrap text-sm font-semibold text-teal-800">UGX {{ number_format((float) $test->price) }}</span></label>@endforeach</div>
            </fieldset>
            <x-input-error :messages="$errors->get('lab_tests')"/>
            <div class="flex flex-wrap gap-3"><button class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white">Book selected tests</button><a href="{{ route('lab.index') }}" class="rounded-lg border px-5 py-2.5 text-sm">My lab bookings</a></div>
        </form>
    </div>
</x-app-layout>
