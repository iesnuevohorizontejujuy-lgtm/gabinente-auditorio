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

it('returns only approved reservations in the visible range', function () {
    $user = User::factory()->create();
    $sala = Sala::factory()->create(['nombre' => 'Sala Auditorio']);
    $reserva = Reserva::factory()
        ->for($sala)
        ->aprobada()
        ->create([
            'titulo' => 'Consejo académico',
            'inicio' => Carbon::parse('2026-09-10 10:00'),
            'fin' => Carbon::parse('2026-09-10 11:00'),
        ]);
    Reserva::factory()->for($sala)->create([
        'inicio' => Carbon::parse('2026-09-10 12:00'),
        'fin' => Carbon::parse('2026-09-10 13:00'),
    ]);
    Reserva::factory()->for($sala)->aprobada()->create([
        'inicio' => Carbon::parse('2026-10-10 10:00'),
        'fin' => Carbon::parse('2026-10-10 11:00'),
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->call('events', '2026-09-01T00:00:00Z', '2026-10-01T00:00:00Z')
        ->assertReturned([
            [
                'id' => $reserva->id,
                'title' => 'Consejo académico · Sala Auditorio',
                'start' => $reserva->inicio->toIso8601String(),
                'end' => $reserva->fin->toIso8601String(),
                'backgroundColor' => '#2563eb',
                'borderColor' => '#2563eb',
            ],
        ]);
});

it('filters calendar events by room', function () {
    $user = User::factory()->create();
    $selectedRoom = Sala::factory()->create();
    $otherRoom = Sala::factory()->create();
    $selectedReservation = Reserva::factory()->for($selectedRoom)->aprobada()->create([
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);
    Reserva::factory()->for($otherRoom)->aprobada()->create([
        'inicio' => Carbon::parse('2026-09-10 10:00'),
        'fin' => Carbon::parse('2026-09-10 11:00'),
    ]);

    Livewire::actingAs($user)
        ->test('pages::calendar.index')
        ->set('salaId', (string) $selectedRoom->id)
        ->assertDispatched('calendar-filter-changed')
        ->call('events', '2026-09-01T00:00:00Z', '2026-10-01T00:00:00Z')
        ->assertReturned([
            [
                'id' => $selectedReservation->id,
                'title' => $selectedReservation->titulo.' · '.$selectedRoom->nombre,
                'start' => $selectedReservation->inicio->toIso8601String(),
                'end' => $selectedReservation->fin->toIso8601String(),
                'backgroundColor' => '#2563eb',
                'borderColor' => '#2563eb',
            ],
        ]);
});
