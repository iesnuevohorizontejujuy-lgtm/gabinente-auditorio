<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('an active administrator can create users', function () {
    $administrator = User::factory()->administrator()->create();
    $policy = new UserPolicy;

    expect($policy->viewAny($administrator))->toBeTrue()
        ->and($policy->create($administrator))->toBeTrue();
});

test('professors and inactive administrators cannot create users', function () {
    $professor = User::factory()->create();
    $inactiveAdministrator = User::factory()->administrator()->inactive()->create();
    $policy = new UserPolicy;

    expect($policy->viewAny($professor))->toBeFalse()
        ->and($policy->create($professor))->toBeFalse()
        ->and($policy->viewAny($inactiveAdministrator))->toBeFalse()
        ->and($policy->create($inactiveAdministrator))->toBeFalse();
});
