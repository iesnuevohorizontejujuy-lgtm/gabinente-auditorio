<?php

use App\Enums\SalaDisponibilidad;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\SalaImagen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts room attributes to their domain types', function () {
    $sala = Sala::factory()->inactiva()->create(['capacidad' => 30]);

    expect($sala->activa)->toBeFalse();
    expect($sala->capacidad)->toBe(30);
    expect($sala->disponibilidad)->toBe(SalaDisponibilidad::Disponible);
});

it('stores an optional image path', function () {
    $sala = Sala::query()->create([
        'nombre' => 'Auditorio',
        'img' => 'salas/auditorio.jpg',
        'capacidad' => 150,
    ]);

    expect($sala->img)->toBe('salas/auditorio.jpg');
});

it('returns only active rooms through the active scope', function () {
    $activa = Sala::factory()->create();
    Sala::factory()->inactiva()->create();

    $salas = Sala::query()->activas()->get();

    expect($salas)->toHaveCount(1);
    expect($salas->first()->is($activa))->toBeTrue();
});

it('reports room availability from its operating schedule', function () {
    $this->travelTo('2026-09-13 10:00:00');
    $disponible = Sala::factory()->create([
        'hora_inicio_operativo' => '08:00:00',
        'hora_fin_operativo' => '21:00:00',
    ]);
    $cerrada = Sala::factory()->create([
        'hora_inicio_operativo' => '11:00:00',
        'hora_fin_operativo' => '21:00:00',
    ]);
    $fueraDeServicio = Sala::factory()->inactiva()->create();
    $ocupada = Sala::factory()->create(['disponibilidad' => SalaDisponibilidad::Ocupada]);

    expect($disponible->disponibilidadActual())->toBe(SalaDisponibilidad::Disponible);
    expect($cerrada->disponibilidadActual())->toBe(SalaDisponibilidad::Cerrada);
    expect($fueraDeServicio->disponibilidadActual())->toBe(SalaDisponibilidad::FueraDeServicio);
    expect($ocupada->disponibilidadActual())->toBe(SalaDisponibilidad::Ocupada);
});

it('has reservations', function () {
    $sala = Sala::factory()->create();
    $reserva = Reserva::factory()->for($sala)->create();

    expect($sala->reservas()->first()->is($reserva))->toBeTrue();
});

it('has images ordered by their position', function () {
    $sala = Sala::factory()->create();
    $secondImage = SalaImagen::factory()->for($sala)->create(['orden' => 2]);
    $firstImage = SalaImagen::factory()->for($sala)->create(['orden' => 1]);

    $imagenes = $sala->imagenes;

    expect($imagenes)->toHaveCount(2);
    expect($imagenes->first()->is($firstImage))->toBeTrue();
    expect($imagenes->last()->is($secondImage))->toBeTrue();
});
