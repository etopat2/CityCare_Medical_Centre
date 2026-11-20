<form method="POST" action="{{ $department ? route('departments.update', $department) : route('departments.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($department) @method('PUT') @endif
    <div><x-input-label for="name" value="Department name"/><x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $department?->name)" required/><x-input-error :messages="$errors->get('name')" class="mt-2"/></div>
    <div><x-input-label for="description" value="Description"/><textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300">{{ old('description', $department?->description) }}</textarea><x-input-error :messages="$errors->get('description')" class="mt-2"/></div>
    <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department?->is_active ?? true)) class="rounded border-slate-300 text-teal-700">Active</label>
    <div class="flex gap-3"><button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Save department</button><a href="{{ route('departments.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a></div>
</form>
