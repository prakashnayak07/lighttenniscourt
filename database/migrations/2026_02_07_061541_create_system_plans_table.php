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
        Schema::create('system_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('slug', 64)->unique();
            $table->integer('price_cents')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->enum('billing_interval', ['month', 'year'])->default('month');
            $table->json('features_config')->comment('{"max_courts": 3, "allow_api": false}');
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_plans');
    }
};
