<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates clinician profiles linked to user accounts and departments.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('specialization');
            $table->string('license_number')->unique();
            $table->string('phone');
            $table->decimal('consultation_fee', 12, 2)->default(0);
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
