<?php
// ============================================================
// 2026_02_24_000001_create_communes_table.php
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $t) {
            $t->string('code_commune')->primary();
            $t->string('nom');
            $t->string('code_departement', 3);
            $t->string('code_region', 3);
            $t->string('siren')->nullable();
            $t->string('code_epci')->nullable();
            $t->integer('population')->nullable();
            $t->timestamps();

            $t->index('code_departement');
            $t->index('code_region');
        });
    }
    public function down(): void { Schema::dropIfExists('communes'); }
};

