<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Patient portal</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">My laboratory bookings</h2></div>
            <a href="{{ route('portal.lab-tests.book') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Book tests</a>
        </div>
    </x-slot>
    <div class="space-y-4 py-8">
        @forelse($orders as $order)
            @php($clinicalDetailsLocked = $order->consultation && ! $order->consultation->appointment?->consultationFeeSettled())
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase text-teal-700">Lab order #{{ $order->id }}</p>
                        <p class="mt-1 font-semibold capitalize text-slate-900">{{ str_replace('_', ' ', $order->status) }}</p>
                        @if($clinicalDetailsLocked)
                            <p class="mt-2 text-sm text-amber-800">Referral details and results are available after the consultation fee is settled.</p>
                        @else
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                @foreach($order->items as $item)
                                    <li>{{ $item->test->name }} · UGX {{ number_format((float) $item->price) }} @if($item->result)<span class="text-teal-800">· Result available</span>@endif</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase text-slate-500">Bill</p>
                        <p class="font-semibold">UGX {{ number_format((float) $order->amount) }}</p>
                        <p class="text-sm capitalize text-slate-500">{{ $order->payment?->status ?? 'unpaid' }}</p>
                        @unless($clinicalDetailsLocked)<a href="{{ route('lab.show', $order) }}" class="mt-2 inline-block text-sm font-semibold text-teal-700">View order &amp; results</a>@endunless
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border bg-white p-10 text-center text-slate-600">No lab bookings yet. Choose a test to book it.</div>
        @endforelse
        {{ $orders->links() }}
    </div>
</x-app-layout>
