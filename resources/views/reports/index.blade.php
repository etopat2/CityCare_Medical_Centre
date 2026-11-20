<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold text-slate-900">Clinic reports</h2></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        <p class="text-sm text-slate-600">Choose a report to review its summary and export the filtered results.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($reports as $slug => [$title, $permission])
                <a href="{{ route('reports.show', $slug) }}" class="rounded-2xl border bg-white p-6 shadow-sm transition hover:border-teal-500 hover:shadow">
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-500">View a filtered summary with download options.</p>
                    <span class="mt-5 inline-block text-sm font-semibold text-teal-700">Open report →</span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
