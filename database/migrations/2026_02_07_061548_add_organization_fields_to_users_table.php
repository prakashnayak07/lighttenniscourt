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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete()->comment('NULL for Super Admin');
            $table->string('first_name', 128)->after('email');
            $table->string('last_name', 128)->nullable()->after('first_name');
            $table->string('phone', 32)->nullable()->after('last_name');
            $table->enum('role', ['super_admin', 'admin', 'staff', 'coach', 'customer'])->default('customer')->after('phone');
            $table->enum('status', ['active', 'disabled', 'banned', 'pending'])->default('active')->after('role');
            $table->json('metadata')->nullable()->after('status')->comment('{"ntrp_rating": 4.5, "play_hand": "right"}');
            $table->dateTime('last_login_at')->nullable()->after('metadata');

            // Add composite unique index for email + organization
            $table->dropUnique(['email']);
            $table->unique(['email', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
