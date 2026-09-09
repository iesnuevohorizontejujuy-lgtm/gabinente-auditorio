<?php

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests to login', function () {
    $this->get(route('reservations.index'))->assertRedirect(route('login'));
});

it('shows professors only their own reservations', function () {
    $professor = User::factory()->create();
    $ownReservation = Reserva::factory()->for($professor, 'profesor')->create(['titulo' => 'Reserva propia']);
    Reserva::factory()->create(['titulo' => 'Reserva ajena']);

    Livewire::actingAs($professor)
        ->test('pages::reservations.index')
        ->assertSee($ownReservation->titulo)
        ->assertDontSee('Reserva ajena');
});

it('preselects the room from the dashboard link', function () {
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();

    Livewire::actingAs($professor)
        ->withQueryParams(['sala' => $sala->id])
        ->test('pages::reservations.index')
        ->assertSet('formSalaId', (string) $sala->id);
});

it('creates a reservation with validated data', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $professor = User::factory()->create();
    $sala = Sala::factory()->create(['capacidad' => 25]);

    Livewire::actingAs($professor)
        ->test('pages::reservations.index')
        ->set('formSalaId', (string) $sala->id)
        ->set('titulo', 'Reunión de departamento')
        ->set('descripcion', 'Planificación académica')
        ->set('cantidadAsistentes', 12)
        ->set('inicio', '2026-09-04T10:00')
        ->set('fin', '2026-09-04T11:00')
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reservas', [
        'sala_id' => $sala->id,
        'profesor_id' => $professor->id,
        'titulo' => 'Reunión de departamento',
        'cantidad_asistentes' => 12,
        'estado' => ReservaEstado::Pendiente->value,
    ]);
    $this->assertDatabaseCount('reserva_historials', 1);
});

it('allows an administrator to approve a pending reservation', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $administrator = User::factory()->administrator()->create();
    $reserva = Reserva::factory()->create([
        'cantidad_asistentes' => 5,
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:00:00',
    ]);

    Livewire::actingAs($administrator)
        ->test('pages::reservations.index')
        ->call('approve', $reserva->id)
        ->assertHasNoErrors();

    expect($reserva->refresh()->estado)->toBe(ReservaEstado::Aprobada);
});

it('forbids administrators from creating professor reservations', function () {
    $administrator = User::factory()->administrator()->create();

    Livewire::actingAs($administrator)
        ->test('pages::reservations.index')
        ->call('openCreate')
        ->assertForbidden();
});
