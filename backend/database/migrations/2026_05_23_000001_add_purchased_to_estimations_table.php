<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimations', function (Blueprint $table) {
            $table->boolean('is_purchased')->default(false)->after('net_yield');
            $table->timestamp('purchased_at')->nullable()->after('is_purchased');
        });
    }

    public function down(): void
    {
        Schema::table('estimations', function (Blueprint $table) {
            $table->dropColumn(['is_purchased', 'purchased_at']);
        });
    }
};
