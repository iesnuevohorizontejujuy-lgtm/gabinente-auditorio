<?php

use App\Enums\ReservaEstado;
use App\Models\Carrera;
use App\Models\Feriado;
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
        ->assertDontSee('Todos los espacios')
        ->assertDontSee('Sala cerrada');
});

it('defaults a professor calendar to the first active room', function () {
    $professor = User::factory()->create();
    Sala::factory()->create(['nombre' => 'Sala B']);
    $firstRoom = Sala::factory()->create(['nombre' => 'Aula A']);

    Livewire::actingAs($professor)
        ->test('pages::calendar.index')
        ->assertSet('salaId', (string) $firstRoom->id);
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

it('returns approved and own pending reservations with their status colors', function () {
    $user = User::factory()->create();
    $career = Carrera::factory()->create(['nombre' => 'Enfermería']);
    $user->carreras()->attach($career);
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
    $pendingReservation = Reserva::factory()->for($sala)->for($user, 'profesor')->create([
        'titulo' => 'Reserva pendiente propia',
        'inicio' => Carbon::parse('2026-09-10 12:00'),
        'fin' => Carbon::parse('2026-09-10 13:00'),
    ]);
    Reserva::factory()->for($sala)->create([
        'titulo' => 'Reserva pendiente ajena',
        'inicio' => Carbon::parse('2026-09-10 14:00'),
        'fin' => Carbon::parse('2026-09-10 15:00'),
    ]);
    Reserva::factory()->for($sala)->aprobada()->create([
        'inicio' => Carbon::parse('2026-10-10 10:00'),
        'fin' => Carbon::parse('2026-10-10 11:00'),
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->call('events', '2026-09-07T00:00:00-03:00', '2026-09-14T00:00:00-03:00')
        ->assertReturned(function (array $events) use ($reserva, $pendingReservation, $user): bool {
            expect($events)->toHaveCount(2)
                ->and($events[0])->toMatchArray([
                    'id' => $reserva->id,
                    'title' => 'Consejo académico',
                    'backgroundColor' => '#059669',
                    'extendedProps' => [
                        'kind' => 'reservation',
                        'sala' => 'Sala Auditorio',
                        'profesor' => $user->name,
                        'carreras' => ['Enfermería'],
                        'estado' => 'Aprobada',
                        'canEdit' => false,
                        'canCancel' => true,
                    ],
                ])
                ->and($events[1])->toMatchArray([
                    'id' => $pendingReservation->id,
                    'title' => 'Reserva pendiente propia',
                    'backgroundColor' => '#f59e0b',
                    'extendedProps' => [
                        'kind' => 'reservation',
                        'sala' => 'Sala Auditorio',
                        'profesor' => $user->name,
                        'carreras' => ['Enfermería'],
                        'estado' => 'Pendiente',
                        'canEdit' => true,
                        'canCancel' => true,
                    ],
                ]);

            return true;
        });
});

it('shows persisted holidays in every room calendar', function () {
    $user = User::factory()->create();
    $sala = Sala::factory()->create();
    $feriado = Feriado::factory()->create([
        'fecha' => '2026-09-12',
        'nombre' => 'Feriado provincial',
        'tipo' => 'inamovible',
        'jurisdiccion' => 'jujuy',
        'fuente' => 'Boletín Oficial de Jujuy',
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->set('salaId', (string) $sala->id)
        ->call('events', '2026-09-07', '2026-09-14')
        ->assertReturned(function (array $events) use ($feriado): bool {
            expect($events)->toHaveCount(1)
                ->and($events[0])->toMatchArray([
                    'id' => "feriado-{$feriado->id}",
                    'title' => 'Feriado provincial',
                    'start' => '2026-09-12',
                    'allDay' => true,
                    'classNames' => ['calendar-holiday-event'],
                    'extendedProps' => [
                        'kind' => 'holiday',
                        'jurisdiccion' => 'jujuy',
                        'tipo' => 'inamovible',
                        'fuente' => 'Boletín Oficial de Jujuy',
                        'canEdit' => false,
                        'canCancel' => false,
                    ],
                ]);

            return true;
        });
});

it('shows administrators every pending reservation for the selected room', function () {
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create(['nombre' => 'Gabinete de Informática']);
    $pendingReservation = Reserva::factory()->for($sala)->create([
        'inicio' => Carbon::parse('2026-09-10 08:00'),
        'fin' => Carbon::parse('2026-09-10 09:00'),
    ]);

    Livewire::actingAs($administrator)
        ->test('pages::calendar.index')
        ->call('events', '2026-09-07', '2026-09-14')
        ->assertReturned(fn (array $events): bool => $events[0]['id'] === $pendingReservation->id
            && $events[0]['backgroundColor'] === '#f59e0b');
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

it('opens and updates an owned pending reservation from the calendar', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();
    $reserva = Reserva::factory()->for($sala)->for($professor, 'profesor')->create([
        'titulo' => 'Actividad original',
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:00:00',
    ]);

    Livewire::actingAs($professor)
        ->test('pages::calendar.index')
        ->set('salaId', (string) $sala->id)
        ->call('openReservation', $reserva->id)
        ->assertSet('editTitulo', 'Actividad original')
        ->set('editTitulo', 'Actividad actualizada')
        ->set('editInicio', '2026-09-04T11:00')
        ->set('editFin', '2026-09-04T12:00')
        ->call('updateReservation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reservas', [
        'id' => $reserva->id,
        'titulo' => 'Actividad actualizada',
        'inicio' => '2026-09-04 11:00:00',
        'fin' => '2026-09-04 12:00:00',
    ]);
    $this->assertDatabaseHas('reserva_historials', [
        'reserva_id' => $reserva->id,
        'motivo' => 'Solicitud actualizada.',
    ]);
});

it('cancels an actionable reservation from the calendar', function () {
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();
    $reserva = Reserva::factory()->for($sala)->for($professor, 'profesor')->create();

    Livewire::actingAs($professor)
        ->test('pages::calendar.index')
        ->set('salaId', (string) $sala->id)
        ->call('openReservation', $reserva->id)
        ->set('motivo', 'Cambio de planificación.')
        ->call('cancelReservation')
        ->assertHasNoErrors();

    expect($reserva->refresh())
        ->estado->toBe(ReservaEstado::Cancelada)
        ->motivo_resolucion->toBe('Cambio de planificación.');
});

it('rejects invalid calendar ranges', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->call('events', '2026-09-14', '2026-09-07')
        ->assertHasErrors(['end']);
});
