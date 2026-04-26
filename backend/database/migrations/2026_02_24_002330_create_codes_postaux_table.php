<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('codes_postaux', function (Blueprint $t) {
            $t->id();
            $t->string('code_postal', 5);
            $t->string('code_commune');
            $t->timestamps();

            $t->foreign('code_commune')->references('code_commune')->on('communes');
            $t->index('code_postal');
            $t->index('code_commune');
        });
    }
    public function down(): void { Schema::dropIfExists('codes_postaux'); }
};