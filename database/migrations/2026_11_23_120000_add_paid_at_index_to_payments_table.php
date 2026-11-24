<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds an index that supports payment date reporting and lookup.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->index('paid_at');
        });
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['paid_at']);
        });
    }
};
