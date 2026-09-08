<?php

use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('forbids professors from opening room administration', function () {
    $professor = User::factory()->create();

    $this->actingAs($professor)
        ->get(route('admin.rooms.index'))
        ->assertForbidden();
});

it('allows an administrator to create a room', function () {
    $administrator = User::factory()->administrator()->create();

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->set('nombre', 'Aula Magna')
        ->set('ubicacion', 'Planta baja')
        ->set('capacidad', 80)
        ->set('activa', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('salas', [
        'nombre' => 'Aula Magna',
        'ubicacion' => 'Planta baja',
        'capacidad' => 80,
        'activa' => true,
    ]);
});

it('updates and disables a room without deleting it', function () {
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create(['nombre' => 'Sala anterior', 'activa' => true]);

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->call('edit', $sala->id)
        ->set('nombre', 'Sala renovada')
        ->call('save')
        ->call('toggleActive', $sala->id)
        ->assertHasNoErrors();

    expect($sala->refresh())
        ->nombre->toBe('Sala renovada')
        ->activa->toBeFalse();
});
