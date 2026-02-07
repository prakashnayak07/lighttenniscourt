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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain', 64)->unique()->nullable()->comment('clubname.yoursaas.com');
            $table->string('logo_url', 512)->nullable();
            $table->string('website')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->enum('billing_status', ['free', 'active', 'past_due', 'cancelled'])->default('free');
            $table->string('stripe_customer_id')->nullable();
            $table->json('settings')->nullable()->comment('Club specific settings (open hours, rules)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
