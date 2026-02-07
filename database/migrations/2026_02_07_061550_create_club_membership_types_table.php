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
        Schema::create('club_membership_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 128);
            $table->integer('price_cents')->default(0);
            $table->enum('billing_cycle', ['one_time', 'monthly', 'yearly'])->default('yearly');
            $table->unsignedInteger('booking_window_days')->default(7);
            $table->unsignedInteger('max_active_bookings')->default(2)->nullable();
            $table->decimal('court_fee_discount_percent', 5, 2)->default(0.00);
            $table->boolean('is_public')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_membership_types');
    }
};
