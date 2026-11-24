<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Adds a saved consultation fee to appointments so later price changes do not alter existing bookings.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->decimal('consultation_fee', 12, 2)->nullable()->after('doctor_id');
        });

        DB::table('appointments')->whereNull('consultation_fee')->update([
            'consultation_fee' => DB::raw('(select consultation_fee from doctors where doctors.id = appointments.doctor_id)'),
        ]);
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('consultation_fee');
        });
    }
};
