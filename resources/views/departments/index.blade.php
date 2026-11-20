<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-2xl font-semibold text-slate-900">Departments</h2><a href="{{ route('departments.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Add department</a></div></x-slot>
    <div class="space-y-5 py-8">
        <form method="GET" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <input name="q" value="{{ request('q') }}" placeholder="Search departments" class="min-w-56 flex-1 rounded-lg border-slate-300 text-sm">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Search</button><a href="{{ route('departments.index') }}" class="rounded-lg border px-4 py-2 text-sm">Clear</a>
        </form>
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Department</th><th class="px-5 py-3">Doctors</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($departments as $department)
                    <tr><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $department->name }}</p><p class="mt-1 text-slate-500">{{ $department->description }}</p></td><td class="px-5 py-4">{{ $department->doctors_count }}</td><td class="px-5 py-4">{{ $department->is_active ? 'Active' : 'Inactive' }}</td><td class="px-5 py-4"><div class="flex gap-3"><a class="font-medium text-teal-700" href="{{ route('departments.edit', $department) }}">Edit</a><form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Remove this department?')">@csrf @method('DELETE')<button class="font-medium text-rose-700">Remove</button></form></div></td></tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No departments found.</td></tr>
                @endforelse
            </tbody></table>
        </div>
        {{ $departments->links() }}
    </div>
</x-app-layout>
