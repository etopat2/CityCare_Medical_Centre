<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer'],
            'state' => ['nullable', Rule::in(['active', 'inactive', 'deleted'])],
        ]);

        $users = User::query()
            ->with('roles')
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(fn ($search) => $search
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%'));
            })
            ->when($filters['role'] ?? null, fn ($query, int $roleId) => $query->whereHas('roles', fn ($roles) => $roles->whereKey($roleId)))
            ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(($filters['state'] ?? null) === 'deleted', fn ($query) => $query->onlyTrashed())
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $roles = Role::query()->where('guard_name', 'web')->withCount('users')->orderBy('name')->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
        $permissionOverridesByUser = $users->getCollection()->isEmpty()
            ? collect()
            : DB::table('user_permission_overrides')
                ->whereIn('user_id', $users->getCollection()->modelKeys())
                ->get()
                ->groupBy('user_id')
                ->map(fn ($overrides) => $overrides->pluck('effect', 'permission_id'));
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();
        $userCount = User::query()->count();
        $activeUserCount = User::query()->where('is_active', true)->count();
        $unverifiedUserCount = User::query()->whereNull('email_verified_at')->count();

        return view('admin.users.index', compact('users', 'roles', 'permissions', 'permissionOverridesByUser', 'filters', 'departments', 'userCount', 'activeUserCount', 'unverifiedUserCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->saveUser($request);

        return redirect()->route('users.index')->with('success', 'User account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->trashed(), 404);
        $this->saveUser($request, $user);

        return redirect()->route('users.index', $request->query())->with('success', 'User account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->trashed(), 404);

        if ($user->is(auth()->user())) {
            throw ValidationException::withMessages(['user' => 'You cannot remove your own account.']);
        }

        if ($user->is_active && $user->hasRole('admin') && User::role('admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'Keep at least one active administrator account.']);
        }

        $user->delete();

        return back()->with('success', 'User account removed from active use; its clinical references were retained.');
    }

    public function restore(int $userId): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        return back()->with('success', 'User account restored.');
    }

    private function saveUser(Request $request, ?User $user = null): User
    {
        $roleIds = collect($request->input('roles', []))->filter(fn ($id): bool => is_numeric($id))->map(fn ($id): int => (int) $id)->unique()->values();
        $roleNames = Role::query()->where('guard_name', 'web')->whereKey($roleIds)->pluck('name');
        $needsPatientProfile = $roleNames->contains('patient') && $user?->patient === null;
        $needsDoctorProfile = $roleNames->contains('doctor') && $user?->doctor === null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => [Rule::requiredIf($needsPatientProfile || $needsDoctorProfile), 'nullable', 'string', 'max:30'],
            'password' => [$user === null ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'access' => ['sometimes', 'array'],
            'access.*' => ['required', Rule::in(['inherit', 'allow', 'deny'])],
            'patient_gender' => [Rule::requiredIf($needsPatientProfile), Rule::in(['female', 'male', 'other'])],
            'patient_date_of_birth' => [Rule::requiredIf($needsPatientProfile), 'nullable', 'date', 'before:today'],
            'department_id' => [Rule::requiredIf($needsDoctorProfile), 'nullable', 'integer', Rule::exists('departments', 'id')],
            'specialization' => [Rule::requiredIf($needsDoctorProfile), 'nullable', 'string', 'max:150'],
            'license_number' => [Rule::requiredIf($needsDoctorProfile), 'nullable', 'string', 'max:100', Rule::unique('doctors', 'license_number')->ignore($user?->doctor?->id)],
            'consultation_fee' => [Rule::requiredIf($needsDoctorProfile), 'nullable', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $permissionIds = Permission::query()->where('guard_name', 'web')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $submittedAccess = $data['access'] ?? [];
        $invalidAccessIds = array_diff(array_map('intval', array_keys($submittedAccess)), $permissionIds);

        if ($invalidAccessIds !== []) {
            throw ValidationException::withMessages(['access' => 'One or more selected permissions are unavailable.']);
        }

        $this->protectLastAdministrator($user, $data);

        return DB::transaction(function () use ($user, $data, $roleIds, $submittedAccess): User {
            $user ??= new User;
            $emailChanged = $user->exists && $user->email !== $data['email'];
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'],
                ...(! empty($data['password']) ? ['password' => $data['password']] : []),
                ...($emailChanged ? ['email_verified_at' => null] : []),
            ])->save();

            $user->syncRoles(Role::query()->where('guard_name', 'web')->whereKey($roleIds)->get());
            $this->syncRoleProfiles($user, $data, $roleNames);
            $this->syncPermissionOverrides($user, $submittedAccess);
            $user->forgetCustomPermissionRules();

            return $user;
        });
    }

    private function protectLastAdministrator(?User $user, array $data): void
    {
        if ($user === null || ! $user->is_active || ! $user->hasRole('admin')) {
            return;
        }

        $adminRoleId = Role::findByName('admin', 'web')->getKey();
        $remainsAdmin = in_array($adminRoleId, array_map('intval', $data['roles']), true);
        $remainsActive = (bool) $data['is_active'];
        $deniesManagement = ($data['access'][(string) Permission::findByName('users.manage', 'web')->getKey()] ?? null) === 'deny';

        if ((! $remainsAdmin || ! $remainsActive || $deniesManagement) && User::role('admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'This change would remove the last active administrator.']);
        }
    }

    private function syncRoleProfiles(User $user, array $data, $roleNames): void
    {
        [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');

        if ($roleNames->contains('patient')) {
            $patient = $user->patient;

            if ($patient === null) {
                Patient::query()->create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName ?: $firstName,
                    'gender' => $data['patient_gender'],
                    'date_of_birth' => $data['patient_date_of_birth'],
                    'phone' => $user->phone ?? '',
                    'email' => $user->email,
                ]);
            } else {
                $patient->update(['first_name' => $firstName, 'last_name' => $lastName ?: $firstName, 'phone' => $user->phone ?? '', 'email' => $user->email]);
            }
        }

        if ($roleNames->contains('doctor')) {
            $doctor = $user->doctor;
            $doctorData = [
                'department_id' => $data['department_id'] ?? $doctor?->department_id,
                'specialization' => $data['specialization'] ?? $doctor?->specialization,
                'license_number' => $data['license_number'] ?? $doctor?->license_number,
                'phone' => $user->phone ?? '',
                'consultation_fee' => $data['consultation_fee'] ?? $doctor?->consultation_fee ?? 0,
                'is_active' => $user->is_active,
            ];

            if ($doctor === null) {
                Doctor::query()->create(['user_id' => $user->id, ...$doctorData]);
            } else {
                $doctor->update($doctorData);
            }
        }
    }

    private function syncPermissionOverrides(User $user, array $overrides): void
    {
        DB::table('user_permission_overrides')->where('user_id', $user->id)->delete();

        $rows = collect($overrides)
            ->filter(fn (string $effect): bool => $effect !== 'inherit')
            ->map(fn (string $effect, string|int $permissionId): array => [
                'user_id' => $user->id,
                'permission_id' => (int) $permissionId,
                'effect' => $effect,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('user_permission_overrides')->insert($rows);
        }
    }
}
