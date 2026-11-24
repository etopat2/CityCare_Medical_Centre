<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Pharmacy</p><h2 class="mt-1 text-2xl font-semibold text-slate-900">Medicine catalogue & stock</h2></div>
            @can('medicines.manage')<a href="#add-medicine" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Add medicine</a>@endcan
        </div>
    </x-slot>

    <div class="space-y-6 py-8">
        <form method="GET" class="grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <label class="text-sm font-medium text-slate-700">Search medicines<input type="search" name="q" value="{{ request('q') }}" placeholder="Name, SKU or strength" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" autocomplete="off"></label>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Search</button><a href="{{ route('medicines.index') }}" class="rounded-lg border px-4 py-2 text-center text-sm">Clear</a>
        </form>

        @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>@endif

        @can('medicines.manage')
            <form id="add-medicine" method="POST" action="{{ route('medicines.store') }}" class="grid gap-4 rounded-2xl border border-teal-100 bg-teal-50 p-5 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div class="sm:col-span-2 lg:col-span-4"><h3 class="font-semibold text-slate-900">Add a medicine</h3><p class="mt-1 text-sm text-slate-600">Enter the medicine identity, selling price per unit and counted opening stock.</p></div>
                <label class="text-sm font-medium text-slate-700">SKU / stock code<input name="sku" value="{{ old('sku') }}" required maxlength="50" placeholder="e.g. PARA500-TAB" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Must be unique in this catalogue.</span></label>
                <label class="text-sm font-medium text-slate-700">Medicine name<input name="name" value="{{ old('name') }}" required maxlength="150" placeholder="Generic or brand name" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="text-sm font-medium text-slate-700">Strength<input name="strength" value="{{ old('strength') }}" maxlength="80" placeholder="e.g. 500 mg" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="text-sm font-medium text-slate-700">Dosage form / pack<input name="dosage_form" value="{{ old('dosage_form') }}" maxlength="80" placeholder="Tablet, bottle, sachet, pack..." class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="text-sm font-medium text-slate-700">Selling price per unit (UGX)<input name="unit_price" type="number" min="0" step="0.01" value="{{ old('unit_price', 0) }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Use the same unit used for prescription quantities.</span></label>
                <label class="text-sm font-medium text-slate-700">Opening stock quantity<input name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', 0) }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="text-sm font-medium text-slate-700">Low-stock alert quantity<input name="reorder_level" type="number" min="0" step="1" value="{{ old('reorder_level', 0) }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="flex items-center gap-2 self-end rounded-lg border border-teal-200 bg-white p-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-teal-700">Available for prescribing</label>
                <button class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white sm:col-span-2 lg:col-span-4">Save medicine</button>
            </form>
        @endcan

        <div class="space-y-4">
            @forelse($medicines as $medicine)
                <article class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div><p class="text-xs font-medium uppercase text-slate-500">Medicine</p><p class="mt-1 font-semibold text-slate-900">{{ $medicine->name }} {{ $medicine->strength }}</p><p class="mt-1 text-xs text-slate-500">{{ $medicine->sku }} · {{ $medicine->dosage_form }}</p></div>
                        <div><p class="text-xs font-medium uppercase text-slate-500">On hand / reserved</p><p class="mt-1 font-semibold">{{ $medicine->stock_quantity }} / {{ $medicine->reserved_quantity }} <span class="font-normal text-slate-500">({{ $medicine->available_quantity }} available)</span></p></div>
                        <div><p class="text-xs font-medium uppercase text-slate-500">Unit price</p><p class="mt-1 font-semibold">UGX {{ number_format((float) $medicine->unit_price, 2) }}</p></div>
                        <div><p class="text-xs font-medium uppercase text-slate-500">Stock state</p><p class="mt-1 font-semibold {{ $medicine->available_quantity <= $medicine->reorder_level ? 'text-amber-700' : 'text-emerald-700' }}">{{ $medicine->available_quantity <= $medicine->reorder_level ? 'Reorder soon' : 'In stock' }}</p></div>
                    </div>
                    @can('medicines.manage')
                        <details class="mt-4 border-t pt-4"><summary class="cursor-pointer text-sm font-semibold text-teal-800">Edit medicine, price or stock</summary>
                            <form method="POST" action="{{ route('medicines.update', $medicine) }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                @csrf @method('PUT')
                                <label class="text-sm font-medium text-slate-700">SKU<input name="sku" value="{{ $medicine->sku }}" required maxlength="50" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Medicine name<input name="name" value="{{ $medicine->name }}" required maxlength="150" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Strength<input name="strength" value="{{ $medicine->strength }}" maxlength="80" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Dosage form / pack<input name="dosage_form" value="{{ $medicine->dosage_form }}" maxlength="80" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Selling price per unit (UGX)<input name="unit_price" type="number" min="0" step="0.01" value="{{ $medicine->unit_price }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="text-sm font-medium text-slate-700">Stock on hand<input name="stock_quantity" type="number" min="{{ $medicine->reserved_quantity }}" step="1" value="{{ $medicine->stock_quantity }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Cannot be lower than {{ $medicine->reserved_quantity }} reserved units.</span></label>
                                <label class="text-sm font-medium text-slate-700">Low-stock alert quantity<input name="reorder_level" type="number" min="0" step="1" value="{{ $medicine->reorder_level }}" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                                <label class="flex items-center gap-2 self-end rounded-lg border p-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($medicine->is_active) class="rounded border-slate-300 text-teal-700">Available for prescribing</label>
                                <button class="rounded-lg border border-teal-300 px-4 py-2.5 text-sm font-semibold text-teal-800 sm:col-span-2 lg:col-span-4">Save changes</button>
                            </form>
                        </details>
                    @endcan
                </article>
            @empty
                <div class="rounded-2xl border bg-white p-10 text-center text-slate-500">No medicines match this search.</div>
            @endforelse
        </div>
        {{ $medicines->links() }}
    </div>
</x-app-layout>
