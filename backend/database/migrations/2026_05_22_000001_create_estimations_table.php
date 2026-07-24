<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('city')->default('');
            $table->string('postal_code', 10)->default('');
            $table->string('district_name')->nullable();
            $table->string('property_type', 20)->default('flat');
            $table->unsignedSmallInteger('surface_area')->nullable();
            $table->unsignedTinyInteger('rooms')->default(1);

            $table->decimal('predicted_rent', 10, 2);
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('net_yield', 5, 2)->nullable();

            $table->jsonb('form_data');
            $table->jsonb('prediction');
            $table->jsonb('rentability')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};
