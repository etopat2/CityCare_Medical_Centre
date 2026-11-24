<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates storage for per-user permission allow/deny overrides.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('effect', 8);
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
    }
};
