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

it('returns only approved reservations in the requested calendar range', function () {
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
        ->call('events', '2026-09-07T00:00:00-03:00', '2026-09-14T00:00:00-03:00')
        ->assertReturned(function (array $events) use ($reserva, $user): bool {
            expect($events)->toHaveCount(1)
                ->and($events[0])->toMatchArray([
                    'id' => $reserva->id,
                    'title' => 'Consejo académico',
                    'extendedProps' => [
                        'sala' => 'Sala Auditorio',
                        'profesor' => $user->name,
                        'estado' => 'Aprobada',
                    ],
                ]);

            return true;
        });
});

it('shows today room occupancy within the institutional schedule', function () {
    $this->travelTo('2026-09-10 07:00:00');
    $user = User::factory()->create();
    $sala = Sala::factory()->create(['nombre' => 'Gabinete de Informática']);
    Reserva::factory()
        ->for($sala)
        ->aprobada()
        ->create([
            'inicio' => Carbon::parse('2026-09-10 08:00'),
            'fin' => Carbon::parse('2026-09-10 09:18'),
        ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->assertSee('Ocupación de hoy')
        ->assertSee('10%');
});

it('filters calendar events by room', function () {
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
        ->set('salaId', (string) $selectedRoom->id)
        ->call('events', '2026-09-07', '2026-09-14')
        ->assertReturned(function (array $events) use ($selectedReservation): bool {
            expect($events)->toHaveCount(1)
                ->and($events[0]['id'])->toBe($selectedReservation->id)
                ->and($events[0]['title'])->toBe('Reserva del espacio seleccionado');

            return true;
        });
});

it('prepares a selected FullCalendar interval in the reservation form', function () {
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();

    Livewire::actingAs($professor)
        ->test('pages::calendar.index')
        ->set('salaId', (string) $sala->id)
        ->call('prepareCreateFromCalendar', '2026-09-10T10:00:00-03:00', '2026-09-10T12:00:00-03:00')
        ->assertSet('formSalaId', (string) $sala->id)
        ->assertSet('inicio', '2026-09-10T10:00')
        ->assertSet('fin', '2026-09-10T12:00');
});

it('rejects invalid calendar ranges', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->call('events', '2026-09-14', '2026-09-07')
        ->assertHasErrors(['end']);
});
