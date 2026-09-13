<?php

use App\Actions\Feriados\SyncFeriadosNacionales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('persists national holidays returned by ArgentinaDatos', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.argentinadatos.com/v1/feriados/2026' => Http::response([
            ['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Año Nuevo'],
            ['fecha' => '2026-03-24', 'tipo' => 'inamovible', 'nombre' => 'Día Nacional de la Memoria'],
        ]),
    ]);

    $count = app(SyncFeriadosNacionales::class)->handle(2026);

    expect($count)->toBe(2);
    $this->assertDatabaseHas('feriados', [
        'fecha' => '2026-01-01 00:00:00',
        'nombre' => 'Año Nuevo',
        'jurisdiccion' => 'nacional',
        'fuente' => 'ArgentinaDatos',
    ]);
    Http::assertSentCount(1);
});

it('rejects an invalid holiday payload without persisting partial data', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.argentinadatos.com/v1/feriados/2026' => Http::response([
            ['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Año Nuevo'],
            ['fecha' => 'fecha-invalida', 'tipo' => 'inamovible', 'nombre' => 'Dato inválido'],
        ]),
    ]);

    expect(fn () => app(SyncFeriadosNacionales::class)->handle(2026))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('feriados', 0);
});
