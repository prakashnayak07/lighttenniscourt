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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained()->cascadeOnDelete()->comment('NULL = All courts');
            $table->string('name', 128)->nullable()->comment('e.g. Weekend Rate');

            $table->unsignedTinyInteger('day_of_week_start')->default(1);
            $table->unsignedTinyInteger('day_of_week_end')->default(7);
            $table->time('time_start')->default('00:00:00');
            $table->time('time_end')->default('23:59:59');

            $table->integer('price_cents');
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
