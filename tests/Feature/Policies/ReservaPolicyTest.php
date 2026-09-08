<?php

use App\Models\Reserva;
use App\Models\User;
use App\Policies\ReservaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('a professor can create and manage an actionable reservation they own', function () {
    $professor = User::factory()->create();
    $reserva = Reserva::factory()->for($professor, 'profesor')->create();
    $policy = new ReservaPolicy;

    expect($policy->create($professor))->toBeTrue()
        ->and($policy->view($professor, $reserva))->toBeTrue()
        ->and($policy->update($professor, $reserva))->toBeTrue()
        ->and($policy->cancel($professor, $reserva))->toBeTrue();
});

test('a professor cannot access another professors reservation', function () {
    $professor = User::factory()->create();
    $reserva = Reserva::factory()->create();
    $policy = new ReservaPolicy;

    expect($policy->view($professor, $reserva))->toBeFalse()
        ->and($policy->update($professor, $reserva))->toBeFalse()
        ->and($policy->cancel($professor, $reserva))->toBeFalse();
});

test('only an active administrator can resolve pending reservations', function () {
    $administrator = User::factory()->administrator()->create();
    $professor = User::factory()->create();
    $reserva = Reserva::factory()->create();
    $policy = new ReservaPolicy;

    expect($policy->approve($administrator, $reserva))->toBeTrue()
        ->and($policy->reject($administrator, $reserva))->toBeTrue()
        ->and($policy->approve($professor, $reserva))->toBeFalse();
});
