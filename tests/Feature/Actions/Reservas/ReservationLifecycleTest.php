<?php

use App\Actions\Reservas\ApproveReserva;
use App\Actions\Reservas\CancelReserva;
use App\Actions\Reservas\CreateReserva;
use App\Actions\Reservas\RejectReserva;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a pending request and records its history', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();

    $reserva = app(CreateReserva::class)->handle($professor, [
        'sala_id' => $sala->id,
        'titulo' => 'Clase especial',
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:30:00',
    ]);

    expect($reserva->estado)->toBe(ReservaEstado::Pendiente)
        ->and($reserva->profesor_id)->toBe($professor->id)
        ->and($reserva->historial)->toHaveCount(1)
        ->and($reserva->historial->first()->estado_nuevo)->toBe(ReservaEstado::Pendiente);
});

it('rejects requests longer than eight hours', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $professor = User::factory()->create();
    $sala = Sala::factory()->create();

    expect(fn () => app(CreateReserva::class)->handle($professor, [
        'sala_id' => $sala->id,
        'titulo' => 'Actividad extensa',
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 18:30:00',
    ]))->toThrow(ValidationException::class, 'La reserva debe durar entre 30 minutos y 8 horas.');

    $this->assertDatabaseEmpty('reservas');
});

it('approves an available request and blocks an overlapping approval', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create();
    Reserva::factory()->for($sala)->aprobada()->create([
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:00:00',
    ]);
    $overlapping = Reserva::factory()->for($sala)->create([
        'inicio' => '2026-09-04 10:30:00',
        'fin' => '2026-09-04 11:30:00',
    ]);

    expect(fn () => app(ApproveReserva::class)->handle($overlapping, $administrator))
        ->toThrow(ValidationException::class, 'El espacio ya tiene una reserva aprobada en ese horario.');

    expect($overlapping->refresh()->estado)->toBe(ReservaEstado::Pendiente)
        ->and($overlapping->historial)->toBeEmpty();
});

it('records rejection and cancellation decisions', function () {
    $administrator = User::factory()->administrator()->create();
    $toReject = Reserva::factory()->create();
    $toCancel = Reserva::factory()->aprobada()->create();

    $rejected = app(RejectReserva::class)->handle($toReject, $administrator, 'El espacio estará en mantenimiento.');
    $cancelled = app(CancelReserva::class)->handle($toCancel, $administrator, 'Actividad suspendida.');

    expect($rejected->estado)->toBe(ReservaEstado::Rechazada)
        ->and($rejected->motivo_resolucion)->toBe('El espacio estará en mantenimiento.')
        ->and($rejected->historial)->toHaveCount(1)
        ->and($cancelled->estado)->toBe(ReservaEstado::Cancelada)
        ->and($cancelled->motivo_resolucion)->toBe('Actividad suspendida.')
        ->and($cancelled->historial)->toHaveCount(1);
});
