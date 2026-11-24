<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates medicine stock, prescription line, medication order, reservation, and medication billing structures.
return new class extends Migration
{
    /** Applies this migration's schema changes. */
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->string('strength', 80)->nullable();
            $table->string('dosage_form', 80)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('medication_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending_review');
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medication_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();
            $table->index(['medication_order_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('billing_type', 30)->default('appointment')->after('appointment_id');
            $table->foreignId('medication_order_id')->nullable()->after('billing_type')->constrained()->restrictOnDelete();
            $table->unique('medication_order_id');
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
        });
    }

    /** Reverses this migration's schema changes when rolling back. */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['medication_order_id']);
            $table->dropForeign(['medication_order_id']);
            $table->dropColumn(['billing_type', 'medication_order_id']);
        });

        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('medication_orders');
        Schema::dropIfExists('medicines');
    }
};
