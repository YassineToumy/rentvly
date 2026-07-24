<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimations', function (Blueprint $table) {
            $table->string('listing_id', 64)->nullable()->after('user_id');
            $table->foreignId('parent_id')->nullable()->after('listing_id')
                ->constrained('estimations')->cascadeOnDelete();

            $table->index(['user_id', 'listing_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimations', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['user_id', 'listing_id']);
            $table->dropColumn(['listing_id', 'parent_id']);
        });
    }
};
