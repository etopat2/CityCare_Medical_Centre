<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['received_by']);
            $table->unsignedBigInteger('received_by')->nullable()->change();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->string('payment_method', 20)->nullable()->change();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('pesapal');
            $table->string('merchant_reference', 50)->unique();
            $table->string('tracking_id', 80)->nullable()->unique();
            $table->string('redirect_url', 1000)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 80)->nullable();
            $table->string('confirmation_code', 100)->nullable();
            $table->string('payment_account', 100)->nullable();
            $table->text('status_description')->nullable();
            $table->timestamps();
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['received_by']);
            $table->unsignedBigInteger('received_by')->nullable(false)->change();
            $table->foreign('received_by')->references('id')->on('users')->restrictOnDelete();
            DB::table('payments')->whereNull('payment_method')->update(['payment_method' => 'mobile_money']);
            $table->string('payment_method', 20)->nullable(false)->change();
        });
    }
};
