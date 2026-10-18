<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $t) {
            $t->id();
            $t->string('external_id')->unique();
            $t->string('title');
            $t->text('description')->nullable();

            $t->string('property_type');
            $t->boolean('is_new_property')->default(false);

            $t->integer('price');
            $t->decimal('price_per_sqm', 10, 2)->nullable();
            $t->boolean('price_has_decreased')->default(false);
            $t->boolean('reduced_vat')->default(false);

            $t->decimal('surface_area', 8, 2)->nullable();
            $t->integer('rooms_quantity')->nullable();
            $t->decimal('surface_per_room', 8, 2)->nullable();

            $t->jsonb('interior_features')->nullable();
            $t->jsonb('exterior_features')->nullable();
            $t->jsonb('other_features')->nullable();
            $t->integer('equipment_score')->default(0);

            $t->string('city');
            $t->string('postal_code', 5);
            $t->string('department_code', 3);
            $t->string('district_name')->nullable();
            $t->string('code_insee', 5)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();

            $t->string('owner_type')->nullable();
            $t->string('owner_name')->nullable();
            $t->boolean('is_pro')->default(false);

            $t->jsonb('photos')->nullable();
            $t->integer('photos_count')->default(0);

            $t->timestamp('publication_date')->nullable();
            $t->timestamp('modification_date')->nullable();
            $t->date('delivery_date')->nullable();
            $t->timestamp('scraped_at')->nullable();
            $t->timestamp('cleaned_at')->nullable();
            $t->timestamps();

            $t->index('city');
            $t->index('postal_code');
            $t->index('department_code');
            $t->index('property_type');
            $t->index('price');
            $t->index('is_new_property');
            $t->index(['department_code', 'property_type', 'price']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX ventes_interior_gin ON ventes USING GIN (interior_features)');
            DB::statement('CREATE INDEX ventes_exterior_gin ON ventes USING GIN (exterior_features)');
            DB::statement('CREATE INDEX ventes_other_gin ON ventes USING GIN (other_features)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
