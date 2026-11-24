<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the permission required to export application logs.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => 'system-logs.export', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );

        $permissionId = DB::table('permissions')->where('name', 'system-logs.export')->where('guard_name', 'web')->value('id');
        $adminRoleId = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');

        if ($adminRoleId !== null) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $adminRoleId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'system-logs.export')->where('guard_name', 'web')->value('id');

        if ($permissionId !== null) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('role_permission_denials')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission_overrides')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
