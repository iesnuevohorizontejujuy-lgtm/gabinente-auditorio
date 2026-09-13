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
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('tipo')->nullable();
            $table->string('jurisdiccion')->default('nacional');
            $table->string('fuente');
            $table->timestamps();

            $table->unique(['fecha', 'jurisdiccion', 'nombre']);
            $table->index(['fecha', 'jurisdiccion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feriados');
    }
};
