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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 128)->comment('Court 1, Center Court');
            $table->enum('surface_type', ['clay', 'hard', 'grass', 'carpet', 'synthetic'])->default('hard');
            $table->boolean('is_indoor')->default(false);
            $table->boolean('has_lighting')->default(false);
            $table->enum('status', ['enabled', 'disabled', 'maintenance'])->default('enabled');
            $table->unsignedInteger('priority')->default(0)->comment('Sorting order');

            // Time Configuration
            $table->time('daily_start_time')->default('07:00:00');
            $table->time('daily_end_time')->default('22:00:00');
            $table->unsignedInteger('time_block_minutes')->default(60);

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
