<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $t) {
            if (!Schema::hasColumn('ventes', 'code_commune')) {
                $t->string('code_commune')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('ventes', 'code_region')) {
                $t->string('code_region')->nullable()->after('department_code');
            }

            $t->foreign('department_code')->references('code_departement')->on('departements')->nullOnDelete();
            $t->foreign('code_region')->references('code_region')->on('regions')->nullOnDelete();
            $t->foreign('code_commune')->references('code_commune')->on('communes')->nullOnDelete();
        });

        // Backfill code_region from departments
        DB::statement("
            UPDATE ventes v
            SET code_region = d.code_region
            FROM departements d
            WHERE v.department_code = d.code_departement
            AND v.code_region IS NULL
        ");

        // Backfill code_commune from codes_postaux
        DB::statement("
            UPDATE ventes v
            SET code_commune = cp.code_commune
            FROM codes_postaux cp
            WHERE v.postal_code = cp.code_postal
            AND v.code_commune IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $t) {
            $t->dropForeign(['department_code']);
            $t->dropForeign(['code_region']);
            $t->dropForeign(['code_commune']);
        });
    }
};