<?php

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can be associated with multiple professors', function () {
    $career = Carrera::factory()->create();
    $professors = User::factory()->count(2)->create();

    $career->profesores()->attach($professors);

    expect($career->profesores()->pluck('users.id')->all())
        ->toEqualCanonicalizing($professors->modelKeys());
});
