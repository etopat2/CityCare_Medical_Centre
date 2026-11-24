<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates the append-only audit event store used to track state-changing requests.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('event', 160)->index();
            $table->string('subject_type', 120)->nullable();
            $table->string('subject_id', 80)->nullable();
            $table->string('subject_name')->nullable();
            $table->string('method', 10);
            $table->string('route_name', 190)->nullable();
            $table->string('path', 500);
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['actor_user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
