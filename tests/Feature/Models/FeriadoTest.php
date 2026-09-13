<?php

use App\Models\Feriado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('filters holidays using an end-exclusive calendar range', function () {
    $included = Feriado::factory()->create(['fecha' => '2026-09-12']);
    Feriado::factory()->create(['fecha' => '2026-09-14']);

    $holidays = Feriado::query()
        ->entre(Carbon::parse('2026-09-07'), Carbon::parse('2026-09-14'))
        ->get();

    expect($holidays->modelKeys())->toBe([$included->id]);
});
