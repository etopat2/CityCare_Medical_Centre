<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-[.14em] text-teal-700">Administration</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">System users</h1><p class="mt-1 text-sm text-slate-500">Manage clinic accounts, roles, account status, and user-specific access.</p></div>
            <a href="#create-user" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-900"><i class="fa fa-user-plus" aria-hidden="true"></i>Add user</a>
        </div>
    </x-slot>

    <div class="space-y-6 py-7">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="User account summary">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Accounts</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ number_format($userCount) }}</p><p class="mt-1 text-xs text-slate-500">Active user records</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Enabled access</p><p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($activeUserCount) }}</p><p class="mt-1 text-xs text-slate-500">Accounts allowed to sign in</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Email verification</p><p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($unverifiedUserCount) }}</p><p class="mt-1 text-xs text-slate-500">Users verify at first sign-in</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Available roles</p><p class="mt-2 text-3xl font-bold text-teal-800">{{ number_format($roles->count()) }}</p><p class="mt-1 text-xs text-slate-500"><a class="font-semibold text-teal-800 hover:underline" href="{{ route('access.index') }}">Configure role access</a></p></article>
        </section>

        <section id="create-user" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <details>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 sm:px-6"><span class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-teal-50 text-teal-800"><i class="fa fa-user-plus" aria-hidden="true"></i></span><span><span class="block font-semibold text-slate-950">Create an account</span><span class="block text-sm text-slate-500">Assign one or more roles and set its starting access.</span></span></span><i class="fa fa-chevron-down text-slate-400" aria-hidden="true"></i></summary>
                <form method="POST" action="{{ route('users.store') }}" class="space-y-6 border-t border-slate-100 px-5 py-6 sm:px-6">
                    @csrf
                    <div class="rounded-2xl border border-teal-100 bg-teal-50/60 p-4 sm:p-5">
                        <label for="new-account-type" class="text-sm font-semibold text-slate-900">What type of account are you creating?</label>
                        <p class="mt-1 text-sm text-slate-600">Choose a category to show only the roles and profile details needed for that account.</p>
                        <select id="new-account-type" name="account_type" required class="mt-3 w-full max-w-md rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700">
                            <option value="staff" @selected(old('account_type', 'staff') === 'staff')>Staff member</option>
                            <option value="doctor" @selected(old('account_type') === 'doctor')>Doctor / clinician</option>
                            <option value="patient" @selected(old('account_type') === 'patient')>Patient</option>
                            <option value="administrator" @selected(old('account_type') === 'administrator')>Administrator</option>
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div><label for="new-name" class="text-sm font-medium text-slate-700">Full name</label><input id="new-name" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"></div>
                        <div><label for="new-email" class="text-sm font-medium text-slate-700">Email address</label><input id="new-email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"></div>
                        <div><label for="new-phone" class="text-sm font-medium text-slate-700">Phone number <span id="new-phone-required" class="font-normal text-rose-700" hidden>(required)</span></label><input id="new-phone" name="phone" value="{{ old('phone') }}" maxlength="30" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"></div>
                        <div><label for="new-password" class="text-sm font-medium text-slate-700">Temporary password</label><input id="new-password" type="password" name="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"></div>
                        <div><label for="new-password-confirmation" class="text-sm font-medium text-slate-700">Confirm password</label><input id="new-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"></div>
                        <div><label for="new-status" class="text-sm font-medium text-slate-700">Sign-in access</label><select id="new-status" name="is_active" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"><option value="1">Enabled</option><option value="0">Denied</option></select></div>
                    </div>

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold text-slate-900">Choose role(s)</legend>
                        @foreach(['staff' => 'Staff roles', 'doctor' => 'Doctor role', 'patient' => 'Patient role', 'administrator' => 'Administrator role'] as $category => $categoryLabel)
                            <div data-role-category="{{ $category }}" hidden>
                                <p class="mb-2 text-xs font-medium text-slate-500">{{ $categoryLabel }}@if($category === 'staff') · You can assign more than one staff role.@endif</p>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($roles as $role)
                                        @php($roleCategory = $role->name === 'patient' ? 'patient' : ($role->name === 'doctor' ? 'doctor' : ($role->name === 'admin' ? 'administrator' : 'staff')))
                                        @if($roleCategory === $category)
                                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 hover:border-teal-300 hover:bg-teal-50/50"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, (array) old('roles', []), true)) disabled class="rounded border-slate-300 text-teal-700 focus:ring-teal-700"><span class="capitalize">{{ str_replace(['-', '_'], ' ', $role->name) }}</span></label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </fieldset>

                    <fieldset data-profile-category="patient" hidden class="rounded-xl border border-slate-200 bg-white p-4">
                        <legend class="px-1 text-sm font-semibold text-slate-900">Patient details</legend>
                        <p class="mb-4 text-xs text-slate-500">These details create the patient record linked to this account.</p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium text-slate-700">Gender<select name="patient_gender" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"><option value="">Select gender</option><option value="female" @selected(old('patient_gender') === 'female')>Female</option><option value="male" @selected(old('patient_gender') === 'male')>Male</option><option value="other" @selected(old('patient_gender') === 'other')>Other / prefer not to say</option></select></label>
                            <label class="text-sm font-medium text-slate-700">Date of birth<input type="date" name="patient_date_of_birth" value="{{ old('patient_date_of_birth') }}" max="{{ now()->subDay()->toDateString() }}" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"></label>
                        </div>
                    </fieldset>

                    <fieldset data-profile-category="doctor" hidden class="rounded-xl border border-slate-200 bg-white p-4">
                        <legend class="px-1 text-sm font-semibold text-slate-900">Doctor details</legend>
                        <p class="mb-4 text-xs text-slate-500">Only clinician accounts need a department, specialty, license, and consultation fee.</p>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <label class="text-sm font-medium text-slate-700">Department<select name="department_id" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"><option value="">Select department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></label>
                            <label class="text-sm font-medium text-slate-700">Specialization<input name="specialization" value="{{ old('specialization') }}" maxlength="150" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"></label>
                            <label class="text-sm font-medium text-slate-700">Medical license number<input name="license_number" value="{{ old('license_number') }}" maxlength="100" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"></label>
                            <label class="text-sm font-medium text-slate-700">Consultation fee (UGX)<input type="number" min="0" step="0.01" name="consultation_fee" value="{{ old('consultation_fee', '0') }}" data-required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm shadow-sm"><span class="mt-1 block text-xs font-normal text-slate-500">Set 0 if this clinician does not charge a consultation fee.</span></label>
                        </div>
                    </fieldset>

                    <p data-profile-category="staff" hidden class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Staff accounts use their assigned work roles. Patient and doctor profile fields, including consultation fees, do not apply.</p>
                    <p data-profile-category="administrator" hidden class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">Administrator accounts manage clinic settings and access. They do not need a patient or doctor profile.</p>

                    <details class="rounded-xl border border-slate-200"><summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800">User-specific permission overrides</summary><div class="grid gap-3 border-t border-slate-100 p-4 sm:grid-cols-2 xl:grid-cols-3">@foreach($permissions as $permission)<label class="flex items-center justify-between gap-3 text-xs text-slate-600"><span>{{ $permission->name }}</span><select name="access[{{ $permission->id }}]" class="w-28 rounded-lg border-slate-300 py-1.5 text-xs"><option value="inherit">Inherit</option><option value="allow">Allow</option><option value="deny">Deny</option></select></label>@endforeach</div></details>

                    <div class="flex justify-end"><button class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-900"><i class="fa fa-check" aria-hidden="true"></i>Create account</button></div>
                </form>
                <script>
                    (() => {
                        const form = document.querySelector('#create-user form[method="POST"]');
                        if (!form) return;

                        const accountType = form.querySelector('#new-account-type');
                        const phone = form.querySelector('#new-phone');
                        const phoneRequired = form.querySelector('#new-phone-required');
                        const roleGroups = [...form.querySelectorAll('[data-role-category]')];
                        const profileGroups = [...form.querySelectorAll('[data-profile-category]')];

                        const updateAccountType = (resetRoles = false) => {
                            const selectedType = accountType.value;
                            roleGroups.forEach(group => {
                                const isActive = group.dataset.roleCategory === selectedType;
                                group.hidden = !isActive;
                                group.querySelectorAll('input[name="roles[]"]').forEach(role => {
                                    role.disabled = !isActive;
                                    if (resetRoles && !isActive) role.checked = false;
                                    if (resetRoles && isActive && selectedType !== 'staff') role.checked = false;
                                });
                            });

                            if (resetRoles && selectedType !== 'staff') {
                                const firstRole = roleGroups.find(group => group.dataset.roleCategory === selectedType)?.querySelector('input[name="roles[]"]');
                                if (firstRole) firstRole.checked = true;
                            }

                            profileGroups.forEach(group => {
                                const isActive = group.dataset.profileCategory === selectedType;
                                group.hidden = !isActive;
                                group.querySelectorAll('input, select').forEach(field => {
                                    field.disabled = !isActive;
                                    field.required = isActive && field.hasAttribute('data-required');
                                });
                            });

                            const needsPhone = selectedType === 'patient' || selectedType === 'doctor';
                            phone.required = needsPhone;
                            phoneRequired.hidden = !needsPhone;
                        };

                        accountType.addEventListener('change', () => updateAccountType(true));
                        updateAccountType();
                    })();
                </script>
            </details>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="text-lg font-bold text-slate-950">Accounts and access</h2><p class="mt-1 text-sm text-slate-500">Search by name, email, or phone. Expand a user to change roles and explicit permission rules.</p></div><p class="text-xs text-slate-500">{{ $users->total() }} results</p></div>
                <form method="GET" action="{{ route('users.index') }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search users" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700">
                    <select name="role" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) ($filters['role'] ?? '') === (string) $role->id)>{{ str_replace(['-', '_'], ' ', ucfirst($role->name)) }}</option>@endforeach</select>
                    <select name="state" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-700 focus:ring-teal-700"><option value="">All account states</option><option value="active" @selected(($filters['state'] ?? '') === 'active')>Enabled</option><option value="inactive" @selected(($filters['state'] ?? '') === 'inactive')>Access denied</option><option value="deleted" @selected(($filters['state'] ?? '') === 'deleted')>Removed</option></select>
                    <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="fa fa-filter me-1" aria-hidden="true"></i>Apply filters</button>
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($users as $managedUser)
                    @php($permissionOverrides = $permissionOverridesByUser->get($managedUser->id, collect()))
                    <article class="px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 items-center gap-3"><img src="{{ $managedUser->avatarUrl() }}" alt="" class="size-11 rounded-full bg-teal-50 object-cover"><div class="min-w-0"><p class="truncate font-semibold text-slate-950">{{ $managedUser->name }} @if($managedUser->trashed())<span class="ms-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase text-slate-600">Removed</span>@elseif(! $managedUser->is_active)<span class="ms-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] uppercase text-rose-700">Denied</span>@endif</p><p class="truncate text-sm text-slate-500">{{ $managedUser->email }} <span class="mx-1 text-slate-300">·</span>{{ $managedUser->phone ?: 'No phone' }}</p></div></div>
                            <div class="flex flex-wrap items-center gap-2">@foreach($managedUser->roles as $role)<span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium capitalize text-teal-800">{{ str_replace(['-', '_'], ' ', $role->name) }}</span>@endforeach<span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $managedUser->email_verified_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $managedUser->email_verified_at ? 'Email verified' : 'Verify on sign-in' }}</span><span class="text-xs text-slate-400">{{ $managedUser->created_at?->format('d M Y') }}</span></div>
                        </div>

                        <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-700"><span><i class="fa fa-sliders me-2 text-teal-700" aria-hidden="true"></i>Manage account, roles, and permissions</span><i class="fa fa-chevron-down text-xs text-slate-400" aria-hidden="true"></i></summary>
                            @if($managedUser->trashed())
                                <form method="POST" action="{{ route('users.restore', $managedUser->id) }}" class="border-t border-slate-200 p-4">@csrf<button class="rounded-lg bg-teal-800 px-4 py-2 text-sm font-semibold text-white"><i class="fa fa-undo me-1" aria-hidden="true"></i>Restore user</button></form>
                            @else
                                <form method="POST" action="{{ route('users.update', $managedUser) }}" class="space-y-5 border-t border-slate-200 p-4 sm:p-5">
                                    @csrf @method('PATCH')
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div><label for="managed-name-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Full name</label><input id="managed-name-{{ $managedUser->id }}" name="name" value="{{ $managedUser->name }}" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-email-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Email</label><input id="managed-email-{{ $managedUser->id }}" type="email" name="email" value="{{ $managedUser->email }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-phone-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Phone</label><input id="managed-phone-{{ $managedUser->id }}" name="phone" value="{{ $managedUser->phone }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-active-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Sign-in access</label><select id="managed-active-{{ $managedUser->id }}" name="is_active" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="1" @selected($managedUser->is_active)>Enabled</option><option value="0" @selected(! $managedUser->is_active)>Denied</option></select></div><div><label for="managed-password-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">New password (optional)</label><input id="managed-password-{{ $managedUser->id }}" type="password" name="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-password-confirmation-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Confirm new password</label><input id="managed-password-confirmation-{{ $managedUser->id }}" type="password" name="password_confirmation" autocomplete="new-password" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div></div>

                                    <fieldset><legend class="text-sm font-semibold text-slate-800">Assigned roles</legend><div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">@foreach($roles as $role)<label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($managedUser->hasRole($role->name)) class="rounded border-slate-300 text-teal-700 focus:ring-teal-700"><span class="capitalize">{{ str_replace(['-', '_'], ' ', $role->name) }}</span></label>@endforeach</div></fieldset>

                                    <details class="rounded-lg border border-slate-200 bg-white"><summary class="cursor-pointer px-3 py-2.5 text-sm font-semibold text-slate-700">Patient or doctor profile</summary><div class="grid gap-3 border-t border-slate-100 p-3 sm:grid-cols-2 xl:grid-cols-3"><div><label class="text-xs font-medium text-slate-600">Patient gender</label><select name="patient_gender" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Select</option><option value="female" @selected($managedUser->patient?->gender === 'female')>Female</option><option value="male" @selected($managedUser->patient?->gender === 'male')>Male</option><option value="other" @selected($managedUser->patient?->gender === 'other')>Other</option></select></div><div><label for="managed-patient-dob-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Patient date of birth</label><input id="managed-patient-dob-{{ $managedUser->id }}" type="date" name="patient_date_of_birth" value="{{ $managedUser->patient?->date_of_birth?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label class="text-xs font-medium text-slate-600">Doctor department</label><select name="department_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Select</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($managedUser->doctor?->department_id === $department->id)>{{ $department->name }}</option>@endforeach</select></div><div><label for="managed-specialization-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Doctor specialization</label><input id="managed-specialization-{{ $managedUser->id }}" name="specialization" value="{{ $managedUser->doctor?->specialization }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-license-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Medical license number</label><input id="managed-license-{{ $managedUser->id }}" name="license_number" value="{{ $managedUser->doctor?->license_number }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div><div><label for="managed-fee-{{ $managedUser->id }}" class="text-xs font-medium text-slate-600">Consultation fee (UGX)</label><input id="managed-fee-{{ $managedUser->id }}" type="number" min="0" step="0.01" name="consultation_fee" value="{{ $managedUser->doctor?->consultation_fee }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></div></div></details>

                                    <details class="rounded-lg border border-slate-200 bg-white"><summary class="cursor-pointer px-3 py-2.5 text-sm font-semibold text-slate-700">Permission overrides <span class="font-normal text-slate-500">— explicit user rules take precedence over role rules</span></summary><div class="grid gap-x-5 gap-y-2 border-t border-slate-100 p-3 sm:grid-cols-2 xl:grid-cols-3">@foreach($permissions as $permission)<label class="flex items-center justify-between gap-3 text-xs text-slate-600"><span>{{ $permission->name }}</span><select name="access[{{ $permission->id }}]" class="w-28 rounded-lg border-slate-300 py-1.5 text-xs"><option value="inherit" @selected(($permissionOverrides->get($permission->id) ?? 'inherit') === 'inherit')>Inherit</option><option value="allow" @selected(($permissionOverrides->get($permission->id) ?? '') === 'allow')>Allow</option><option value="deny" @selected(($permissionOverrides->get($permission->id) ?? '') === 'deny')>Deny</option></select></label>@endforeach</div></details>

                                    <div class="flex flex-wrap items-center justify-between gap-3"><p class="max-w-xl text-xs leading-5 text-slate-500">Removing an account preserves its linked clinical history. Deactivate access when you want to keep the account available for later restoration.</p><div class="flex gap-2"><button class="rounded-lg bg-teal-800 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-900"><i class="fa fa-save me-1" aria-hidden="true"></i>Save changes</button></div></div>
                                </form>
                                <form method="POST" action="{{ route('users.destroy', $managedUser) }}" data-confirm="Remove this account from active use? Any linked doctor profile will also leave the active doctors list. Appointments and clinical records will be retained." data-confirm-ok="Remove account" class="border-t border-slate-200 px-4 py-3 text-right sm:px-5">@csrf @method('DELETE')<button class="text-xs font-semibold text-rose-700 hover:text-rose-900"><i class="fa fa-user-times me-1" aria-hidden="true"></i>Remove account</button></form>
                            @endif
                        </details>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center"><i class="fa fa-users text-3xl text-slate-300" aria-hidden="true"></i><p class="mt-3 font-semibold text-slate-700">No users match these filters</p><p class="mt-1 text-sm text-slate-500">Change the filters or create an account above.</p></div>
                @endforelse
            </div>
            @if($users->hasPages())<div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $users->links() }}</div>@endif
        </section>
    </div>
</x-app-layout>
