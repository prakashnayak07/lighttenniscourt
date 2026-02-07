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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete()->comment('The person who booked');
            $table->foreignId('resource_id')->constrained()->restrictOnDelete();

            $table->enum('status', ['confirmed', 'pending', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded'])->default('pending');
            $table->enum('visibility', ['public', 'private'])->default('private');

            $table->text('notes')->nullable();
            $table->dateTime('check_in_at')->nullable();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['resource_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
