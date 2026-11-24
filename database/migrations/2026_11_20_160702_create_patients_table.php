<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates patient demographics and contact information linked to user accounts.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('patient_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 20);
            $table->date('date_of_birth');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('national_id')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['phone', 'date_of_birth']);
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
