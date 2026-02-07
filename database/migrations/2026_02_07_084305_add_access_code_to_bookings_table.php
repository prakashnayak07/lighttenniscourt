<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('access_code', 50)->nullable()->unique()->after('payment_status');
            $table->timestamp('access_code_used_at')->nullable()->after('access_code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['access_code', 'access_code_used_at']);
        });
    }
};
