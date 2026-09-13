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
        Schema::create('salas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->string('img')->nullable();
            $table->string('ubicacion')->nullable();
            $table->unsignedInteger('capacidad');
            $table->time('hora_inicio_operativo')->default('08:00:00');
            $table->time('hora_fin_operativo')->default('21:00:00');
            $table->string('disponibilidad')->default('disponible');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salas');
    }
};
