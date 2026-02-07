<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->references('id')->on('user_wallets')->cascadeOnDelete();
            $table->integer('amount_cents');
            $table->enum('type', ['deposit', 'booking_payment', 'refund', 'adjustment']);
            $table->string('reference_id', 64)->nullable()->comment('Payment Gateway ID or Booking ID');
            $table->string('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
