<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['role' => ['nullable', 'integer']]);
        $roles = Role::query()->where('guard_name', 'web')->withCount('users')->orderBy('name')->get();
        $selectedRole = $roles->firstWhere('id', (int) ($filters['role'] ?? 0)) ?? $roles->first();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
        $allowedPermissionIds = $selectedRole?->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        $deniedPermissionIds = $selectedRole === null
            ? []
            : DB::table('role_permission_denials')->where('role_id', $selectedRole->id)->pluck('permission_id')->map(fn ($id): int => (int) $id)->all();
        $permissionGroups = $permissions->groupBy(fn (Permission $permission): string => Str::before($permission->name, '.'));

        return view('admin.access.index', compact('roles', 'selectedRole', 'permissionGroups', 'allowedPermissionIds', 'deniedPermissionIds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/', Rule::unique('roles', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'web']);

        return redirect()->route('access.index', ['role' => $role->id])->with('success', 'Role created. Configure its access below.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)+$/', Rule::unique('permissions', 'name')->where('guard_name', 'web')],
        ]);

        Permission::query()->create(['name' => $data['name'], 'guard_name' => 'web']);

        return redirect()->route('access.index')->with('success', 'Permission created. Assign it to a role or user and enforce it in the relevant application route or policy.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $data = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', Rule::in(['inherit', 'allow', 'deny'])],
        ]);

        $submitted = $data['permissions'] ?? [];
        $permissionIds = Permission::query()->where('guard_name', 'web')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $invalidIds = array_diff(array_map('intval', array_keys($submitted)), $permissionIds);

        if ($invalidIds !== []) {
            throw ValidationException::withMessages(['permissions' => 'One or more selected permissions are unavailable.']);
        }

        $allowIds = collect($submitted)->filter(fn (string $effect): bool => $effect === 'allow')->keys()->map(fn ($id): int => (int) $id)->all();
        $denyIds = collect($submitted)->filter(fn (string $effect): bool => $effect === 'deny')->keys()->map(fn ($id): int => (int) $id)->all();

        if ($role->name === 'admin') {
            $criticalIds = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', ['users.manage', 'access.manage', 'audit.view'])
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if (array_diff($criticalIds, $allowIds) !== [] || array_intersect($criticalIds, $denyIds) !== []) {
                throw ValidationException::withMessages(['permissions' => 'The administrator role must retain user, access, and audit management permissions.']);
            }
        }

        DB::transaction(function () use ($role, $allowIds, $denyIds): void {
            $role->syncPermissions($allowIds);
            DB::table('role_permission_denials')->where('role_id', $role->id)->delete();

            if ($denyIds !== []) {
                $now = now();
                DB::table('role_permission_denials')->insert(array_map(
                    fn (int $permissionId): array => ['role_id' => $role->id, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now],
                    $denyIds,
                ));
            }
        });

        return redirect()->route('access.index', ['role' => $role->id])->with('success', 'Role access rules updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $protectedRoles = ['admin', 'doctor', 'patient', 'receptionist', 'cashier'];

        if (in_array($role->name, $protectedRoles, true)) {
            throw ValidationException::withMessages(['role' => 'Built-in clinic roles cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Remove this role from its users before deleting it.']);
        }

        $role->delete();

        return redirect()->route('access.index')->with('success', 'Role deleted.');
    }
}
