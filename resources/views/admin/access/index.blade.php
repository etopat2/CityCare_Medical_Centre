<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-semibold uppercase tracking-[.14em] text-teal-700">Security administration</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Roles and permissions</h1><p class="mt-1 text-sm text-slate-500">Group capabilities into roles, then allow, deny, or inherit each permission explicitly.</p></div>
    </x-slot>

    <div class="space-y-6 py-7">
        <section class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('access.roles.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-800"><i class="fa fa-users" aria-hidden="true"></i></span><div><h2 class="font-bold text-slate-950">Create a role</h2><p class="mt-1 text-sm text-slate-500">Use a short lowercase identifier. You can configure its permissions after creating it.</p></div></div>
                <div class="mt-4 flex gap-2"><input name="name" value="{{ old('name') }}" required maxlength="100" pattern="[a-z][a-z0-9._-]*" placeholder="clinic-manager" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"><button class="rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900"><i class="fa fa-plus me-1" aria-hidden="true"></i>Create</button></div>
            </form>
            <form method="POST" action="{{ route('access.permissions.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><i class="fa fa-key" aria-hidden="true"></i></span><div><h2 class="font-bold text-slate-950">Add a permission</h2><p class="mt-1 text-sm text-slate-500">Use a capability name such as <code class="rounded bg-slate-100 px-1.5 py-0.5">inventory.view</code>. Enforce new permissions in a route or policy.</p></div></div>
                <div class="mt-4 flex gap-2"><input name="name" value="{{ old('name') }}" required maxlength="120" pattern="[a-z][a-z0-9]+([._-][a-z0-9]+)+" placeholder="records.export" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"><button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa fa-plus me-1" aria-hidden="true"></i>Add</button></div>
            </form>
        </section>

        <div class="grid gap-6 xl:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between px-2 pb-3"><h2 class="font-bold text-slate-900">Clinic roles</h2><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $roles->count() }}</span></div>
                <nav class="space-y-1" aria-label="Select role">@foreach($roles as $role)<a href="{{ route('access.index', ['role' => $role->id]) }}" class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm transition {{ $selectedRole?->is($role) ? 'bg-teal-50 font-semibold text-teal-900 ring-1 ring-teal-100' : 'text-slate-600 hover:bg-slate-50' }}"><span class="truncate capitalize">{{ str_replace(['-', '_'], ' ', $role->name) }}</span><span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500">{{ $role->users_count }}</span></a>@endforeach</nav>
                @if($selectedRole && ! in_array($selectedRole->name, ['admin', 'doctor', 'patient', 'receptionist', 'cashier'], true) && $selectedRole->users_count === 0)
                    <form method="POST" action="{{ route('access.roles.destroy', $selectedRole) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm('Delete this role?');">@csrf @method('DELETE')<button class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50"><i class="fa fa-trash me-2" aria-hidden="true"></i>Delete unused role</button></form>
                @endif
            </aside>

            @if($selectedRole)
                <form method="POST" action="{{ route('access.roles.update', $selectedRole) }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @csrf @method('PUT')
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"><div><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Role access matrix</p><h2 class="mt-1 text-xl font-bold capitalize text-slate-950">{{ str_replace(['-', '_'], ' ', $selectedRole->name) }}</h2><p class="mt-1 text-sm text-slate-500">{{ $selectedRole->users_count }} assigned user{{ $selectedRole->users_count === 1 ? '' : 's' }} · {{ count($allowedPermissionIds) }} explicit grants · {{ count($deniedPermissionIds) }} denials</p></div><button class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900"><i class="fa fa-save" aria-hidden="true"></i>Save role access</button></div>

                    @if($selectedRole->name === 'admin')<div class="flex gap-3 border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm leading-5 text-amber-900 sm:px-6"><i class="fa fa-shield mt-0.5" aria-hidden="true"></i><p>Administrator access to user, role, and audit management is protected to reduce accidental lockouts.</p></div>@endif

                    <div class="divide-y divide-slate-100">
                        @foreach($permissionGroups as $group => $groupPermissions)
                            <section class="px-5 py-5 sm:px-6"><div class="mb-4 flex items-center justify-between"><h3 class="font-semibold capitalize text-slate-900">{{ str_replace(['-', '_'], ' ', $group) }}</h3><span class="text-xs text-slate-400">{{ $groupPermissions->count() }} capabilities</span></div>
                                <div class="space-y-2">
                                    @foreach($groupPermissions as $permission)
                                        @php($currentRule = in_array($permission->id, $deniedPermissionIds, true) ? 'deny' : (in_array($permission->id, $allowedPermissionIds, true) ? 'allow' : 'inherit'))
                                        <fieldset class="grid gap-3 rounded-xl border border-slate-100 px-3 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"><legend class="sr-only">{{ $permission->name }}</legend><div><p class="font-mono text-sm font-medium text-slate-800">{{ $permission->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ str_replace(['.', '_', '-'], ' ', ucfirst($permission->name)) }}</p></div><div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Access for {{ $permission->name }}">@foreach(['inherit' => 'Inherit', 'allow' => 'Allow', 'deny' => 'Deny'] as $value => $label)<label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs {{ $currentRule === $value ? ($value === 'deny' ? 'border-rose-200 bg-rose-50 text-rose-800' : ($value === 'allow' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-300 bg-slate-100 text-slate-800')) : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50' }}"><input type="radio" name="permissions[{{ $permission->id }}]" value="{{ $value }}" @checked($currentRule === $value) class="size-3.5 border-slate-300 text-teal-700 focus:ring-teal-700"><span>{{ $label }}</span></label>@endforeach</div></fieldset>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                    <div class="flex justify-end border-t border-slate-100 px-5 py-4 sm:px-6"><button class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-900"><i class="fa fa-save" aria-hidden="true"></i>Save role access</button></div>
                </form>
            @else
                <section class="grid place-items-center rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><i class="fa fa-key text-3xl text-slate-300" aria-hidden="true"></i><p class="mt-3 font-semibold text-slate-700">Create a role to configure its access</p></section>
            @endif
        </div>
    </div>
</x-app-layout>
