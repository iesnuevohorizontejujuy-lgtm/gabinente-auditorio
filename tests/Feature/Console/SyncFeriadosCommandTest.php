<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('synchronizes the requested year', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.argentinadatos.com/v1/feriados/2026' => Http::response([
            ['fecha' => '2026-01-01', 'tipo' => 'inamovible', 'nombre' => 'Año Nuevo'],
        ]),
    ]);

    $this->artisan('feriados:sincronizar', ['anio' => 2026])
        ->expectsOutput('Se sincronizaron 1 feriados nacionales de 2026.')
        ->assertSuccessful();

    $this->assertDatabaseHas('feriados', ['nombre' => 'Año Nuevo']);
});

it('rejects years outside the supported range', function () {
    $this->artisan('feriados:sincronizar', ['anio' => 2015])
        ->expectsOutput('El año debe estar comprendido entre 2016 y el año actual.')
        ->assertExitCode(2);
});
