<?php

use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests to login', function () {
    $response = $this->get(route('calendar'));

    $response->assertRedirect(route('login'));
});

it('renders the calendar and its active room filters', function () {
    $user = User::factory()->create();
    $sala = Sala::factory()->create(['nombre' => 'Sala Auditorio']);
    Sala::factory()->inactiva()->create(['nombre' => 'Sala cerrada']);

    $response = $this->actingAs($user)->get(route('calendar'));

    $response
        ->assertOk()
        ->assertSeeLivewire('pages::calendar.index')
        ->assertSee($sala->nombre)
        ->assertDontSee('Sala cerrada');
});

it('preselects the room from a dashboard calendar link', function () {
    $user = User::factory()->create();
    $sala = Sala::factory()->create();

    Livewire::actingAs($user)
        ->withQueryParams(['sala' => $sala->id])
        ->test('pages::calendar.index')
        ->assertSet('salaId', (string) $sala->id);
});

it('defaults an administrator calendar to the gabinete', function () {
    $administrator = User::factory()->administrator()->create();
    $gabinete = Sala::factory()->create(['nombre' => 'Gabinete de Informática']);
    Sala::factory()->create(['nombre' => 'Sala Auditorio']);

    Livewire::actingAs($administrator)
        ->test('pages::calendar.index')
        ->assertSet('salaId', (string) $gabinete->id);
});

it('shows only approved reservations in their weekly time blocks', function () {
    $user = User::factory()->create();
    $sala = Sala::factory()->create(['nombre' => 'Sala Auditorio']);
    $reserva = Reserva::factory()
        ->for($sala)
        ->for($user, 'profesor')
        ->aprobada()
        ->create([
            'titulo' => 'Consejo académico',
            'inicio' => Carbon::parse('2026-09-10 10:00'),
            'fin' => Carbon::parse('2026-09-10 11:00'),
        ]);
    Reserva::factory()->for($sala)->create([
        'titulo' => 'Reserva pendiente',
        'inicio' => Carbon::parse('2026-09-10 12:00'),
        'fin' => Carbon::parse('2026-09-10 13:00'),
    ]);
    Reserva::factory()->for($sala)->aprobada()->create([
        'inicio' => Carbon::parse('2026-10-10 10:00'),
        'fin' => Carbon::parse('2026-10-10 11:00'),
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->set('weekStart', '2026-09-07')
        ->assertSee('Lunes')
        ->assertSee('Viernes')
        ->assertSee('08:00 a 09:20')
        ->assertSee('20:00 a 21:00')
        ->assertSee($reserva->titulo)
        ->assertSee('Prof. '.$user->name)
        ->assertSee('Sin asignar')
        ->assertDontSee('Reserva pendiente');
});

it('filters weekly reservations by room', function () {
    $user = User::factory()->create();
    $selectedRoom = Sala::factory()->create();
    $otherRoom = Sala::factory()->create();
    $selectedReservation = Reserva::factory()->for($selectedRoom)->aprobada()->create([
        'titulo' => 'Reserva del espacio seleccionado',
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);
    Reserva::factory()->for($otherRoom)->aprobada()->create([
        'titulo' => 'Reserva de otro espacio',
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->set('weekStart', '2026-09-07')
        ->set('salaId', (string) $selectedRoom->id)
        ->assertSee($selectedReservation->titulo)
        ->assertDontSee('Reserva de otro espacio');
});

it('prepares the dragged time blocks in the reservation form', function () {
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();

    Livewire::actingAs($professor)
        ->test('pages::calendar.index')
        ->set('weekStart', '2026-09-07')
        ->set('salaId', (string) $sala->id)
        ->dispatch('calendar-slot-selected', date: '2026-09-10', startSlot: 2, endSlot: 3)
        ->assertSet('formSalaId', (string) $sala->id)
        ->assertSet('inicio', '2026-09-10T10:00')
        ->assertSet('fin', '2026-09-10T12:00');
});
