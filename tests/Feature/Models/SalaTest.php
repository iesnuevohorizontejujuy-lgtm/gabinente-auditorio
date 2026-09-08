<?php

use App\Models\Reserva;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts room attributes to their domain types', function () {
    $sala = Sala::factory()->inactiva()->create(['capacidad' => 30]);

    expect($sala->activa)->toBeFalse();
    expect($sala->capacidad)->toBe(30);
});

it('returns only active rooms through the active scope', function () {
    $activa = Sala::factory()->create();
    Sala::factory()->inactiva()->create();

    $salas = Sala::query()->activas()->get();

    expect($salas)->toHaveCount(1);
    expect($salas->first()->is($activa))->toBeTrue();
});

it('has reservations', function () {
    $sala = Sala::factory()->create();
    $reserva = Reserva::factory()->for($sala)->create();

    expect($sala->reservas()->first()->is($reserva))->toBeTrue();
});
