<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the administration capabilities needed by existing role records.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        $permissionIds = [];
        $now = now();

        foreach (['access.manage', 'audit.view', 'audit.export', 'system-logs.view'] as $permissionName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
            $permissionIds[] = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->value('id');
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');

        if ($adminRoleId !== null) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $adminRoleId,
                ]);
            }
        }
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', ['access.manage', 'audit.view', 'audit.export', 'system-logs.view'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('role_permission_denials')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permission_overrides')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
