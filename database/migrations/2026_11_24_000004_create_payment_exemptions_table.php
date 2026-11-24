<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Records consultation-fee waivers separately from collected payments and grants the exemption permission.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('amount_waived', 12, 2)->default(0)->after('amount_paid');
        });

        Schema::create('payment_exemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 60);
            $table->text('details')->nullable();
            $table->foreignId('exempted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('permissions')->updateOrInsert(
            ['name' => 'payments.exempt', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );

        $permissionId = DB::table('permissions')->where('name', 'payments.exempt')->where('guard_name', 'web')->value('id');
        $roleIds = DB::table('roles')->whereIn('name', ['admin', 'doctor'])->where('guard_name', 'web')->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'payments.exempt')->where('guard_name', 'web')->value('id');

        if ($permissionId !== null) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('role_permission_denials')->where('permission_id', $permissionId)->delete();
            DB::table('user_permission_overrides')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('payment_exemptions');
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('amount_waived');
        });
    }
};
