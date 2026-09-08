<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('casts institutional attributes to their domain types', function () {
    $user = User::factory()->administrator()->inactive()->create();

    expect($user->rol)->toBe(UserRole::Administrador);
    expect($user->activo)->toBeFalse();
});

it('creates professors as active users by default', function () {
    $user = User::factory()->create();

    expect($user->rol)->toBe(UserRole::Profesor);
    expect($user->activo)->toBeTrue();
    expect($user->apellido)->not->toBeEmpty();
});
