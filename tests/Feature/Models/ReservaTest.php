<?php

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts reservation attributes and exposes its relationships', function () {
    $sala = Sala::factory()->create();
    $profesor = User::factory()->create();
    $reserva = Reserva::factory()
        ->for($sala)
        ->for($profesor, 'profesor')
        ->aprobada()
        ->create();

    expect($reserva->estado)->toBe(ReservaEstado::Aprobada);
    expect($reserva->inicio)->toBeInstanceOf(CarbonInterface::class);
    expect($reserva->fin)->toBeInstanceOf(CarbonInterface::class);
    expect($reserva->sala->is($sala))->toBeTrue();
    expect($reserva->profesor->is($profesor))->toBeTrue();
});

it('returns approved reservations that intersect a half-open time range', function () {
    $matching = Reserva::factory()->aprobada()->create([
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);
    Reserva::factory()->create([
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);
    Reserva::factory()->aprobada()->create([
        'inicio' => Carbon::parse('2026-09-10 11:00'),
        'fin' => Carbon::parse('2026-09-10 12:00'),
    ]);

    $reservas = Reserva::query()
        ->aprobadas()
        ->entre(Carbon::parse('2026-09-10 10:00'), Carbon::parse('2026-09-10 11:00'))
        ->get();

    expect($reservas)->toHaveCount(1);
    expect($reservas->first()->is($matching))->toBeTrue();
});
