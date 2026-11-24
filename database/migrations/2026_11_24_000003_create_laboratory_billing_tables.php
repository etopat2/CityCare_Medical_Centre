<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates the laboratory test catalogue, order lines, workflow fields, and order-linked billing records.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('specimen', 100)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('lab_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending_payment');
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('lab_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->text('result')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();
            $table->unique(['lab_order_id', 'lab_test_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('lab_order_id')->nullable()->after('medication_order_id')->constrained()->restrictOnDelete();
            $table->unique('lab_order_id');
        });
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['lab_order_id']);
            $table->dropForeign(['lab_order_id']);
            $table->dropColumn('lab_order_id');
        });
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
    }
};
