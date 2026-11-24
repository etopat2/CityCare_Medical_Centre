<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates storage for hashed, expiring one-time authentication codes.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('auth_otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['user_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::dropIfExists('auth_otp_challenges');
    }
};
