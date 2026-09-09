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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sala_id')->constrained('salas')->restrictOnDelete();
            $table->foreignId('profesor_id')->constrained('users')->restrictOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('cantidad_asistentes')->default(1);
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->string('estado')->default('pendiente');
            $table->text('motivo_resolucion')->nullable();
            $table->foreignId('resuelta_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resuelta_at')->nullable();
            $table->timestamps();

            $table->index(['sala_id', 'inicio', 'fin']);
            $table->index(['profesor_id', 'inicio']);
            $table->index(['estado', 'inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
