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
    $sala = Sala::factory()->create(['capacidad' => 30]);

    $reserva = app(CreateReserva::class)->handle($professor, [
        'sala_id' => $sala->id,
        'titulo' => 'Clase especial',
        'descripcion' => null,
        'cantidad_asistentes' => 20,
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:30:00',
    ]);

    expect($reserva->estado)->toBe(ReservaEstado::Pendiente)
        ->and($reserva->profesor_id)->toBe($professor->id)
        ->and($reserva->historial)->toHaveCount(1)
        ->and($reserva->historial->first()->estado_nuevo)->toBe(ReservaEstado::Pendiente);
});

it('rejects requests that exceed room capacity', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $professor = User::factory()->create();
    $sala = Sala::factory()->create(['capacidad' => 10]);

    expect(fn () => app(CreateReserva::class)->handle($professor, [
        'sala_id' => $sala->id,
        'titulo' => 'Actividad masiva',
        'descripcion' => null,
        'cantidad_asistentes' => 11,
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:00:00',
    ]))->toThrow(ValidationException::class, 'La capacidad máxima de este espacio es de 10 personas.');

    $this->assertDatabaseEmpty('reservas');
});

it('approves an available request and blocks an overlapping approval', function () {
    $this->travelTo('2026-09-03 09:00:00');
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create(['capacidad' => 30]);
    Reserva::factory()->for($sala)->aprobada()->create([
        'inicio' => '2026-09-04 10:00:00',
        'fin' => '2026-09-04 11:00:00',
    ]);
    $overlapping = Reserva::factory()->for($sala)->create([
        'cantidad_asistentes' => 5,
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
    $toCancel = Reserva::factory()->aprobada()->create(['cantidad_asistentes' => 5]);

    $rejected = app(RejectReserva::class)->handle($toReject, $administrator, 'El espacio estará en mantenimiento.');
    $cancelled = app(CancelReserva::class)->handle($toCancel, $administrator, 'Actividad suspendida.');

    expect($rejected->estado)->toBe(ReservaEstado::Rechazada)
        ->and($rejected->historial)->toHaveCount(1)
        ->and($cancelled->estado)->toBe(ReservaEstado::Cancelada)
        ->and($cancelled->historial)->toHaveCount(1);
});
