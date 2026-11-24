<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Adds soft deletion to doctor profiles so clinical history can retain removed clinicians.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            $table->softDeletes();
        });

        $deletedDoctors = DB::table('doctors')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->whereNotNull('users.deleted_at')
            ->get(['doctors.id', 'users.deleted_at']);

        foreach ($deletedDoctors as $doctor) {
            DB::table('doctors')->where('id', $doctor->id)->update(['deleted_at' => $doctor->deleted_at]);
        }
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
