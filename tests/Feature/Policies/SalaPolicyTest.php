<?php

use App\Models\Sala;
use App\Models\User;
use App\Policies\SalaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('an active administrator can manage rooms', function () {
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create();
    $policy = new SalaPolicy;

    expect($policy->viewAny($administrator))->toBeTrue()
        ->and($policy->create($administrator))->toBeTrue()
        ->and($policy->update($administrator, $sala))->toBeTrue();
});

test('professors and inactive administrators cannot manage rooms', function () {
    $professor = User::factory()->create();
    $inactiveAdministrator = User::factory()->administrator()->inactive()->create();
    $policy = new SalaPolicy;

    expect($policy->viewAny($professor))->toBeFalse()
        ->and($policy->viewAny($inactiveAdministrator))->toBeFalse();
});
