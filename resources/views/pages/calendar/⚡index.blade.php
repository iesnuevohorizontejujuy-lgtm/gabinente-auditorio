<?php

use App\Actions\Reservas\CancelReserva;
use App\Actions\Reservas\CreateReserva;
use App\Actions\Reservas\UpdateReserva;
use App\Enums\ReservaEstado;
use App\Models\Feriado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Calendario de reservas')] class extends Component
{
    #[Url(as: 'sala')]
    public string $salaId = '';

    public string $formSalaId = '';
    public string $titulo = '';
    public string $inicio = '';
    public string $fin = '';

    public ?int $selectedReservationId = null;
    public string $editTitulo = '';
    public string $editInicio = '';
    public string $editFin = '';
    public string $motivo = '';

    public function mount(): void
    {
        $user = Auth::user();

        $roomExists = $this->salaId !== '' && Sala::query()
            ->activas()
            ->whereKey($this->salaId)
            ->exists();

        if (! $roomExists) {
            $preferredRoomId = $user instanceof User && $user->isAdministrator()
                ? Sala::query()->activas()->where('nombre', 'Gabinete de Informática')->value('id')
                : null;

            $this->salaId = (string) ($preferredRoomId ?? Sala::query()->activas()->orderBy('nombre')->value('id'));
        }
    }

    /** @return Collection<int, Sala> */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->activas()->orderBy('nombre')->get();
    }

    #[Computed]
    public function selectedRoom(): ?Sala
    {
        return $this->salas->firstWhere('id', (int) $this->salaId);
    }

    public function updatedSalaId(): void
    {
        $this->validateRoomFilter();
        $this->resetReservationSelection();
        $this->dispatch('calendar-filter-changed');
    }

    #[Computed]
    public function selectedReservation(): ?Reserva
    {
        if ($this->selectedReservationId === null) {
            return null;
        }

        $user = $this->user();

        return Reserva::query()
            ->with(['sala', 'profesor.carreras'])
            ->where('sala_id', $this->salaId)
            ->when($user->isProfessor(), fn ($query) => $query->whereBelongsTo($user, 'profesor'))
            ->find($this->selectedReservationId);
    }

    /** @return list<array<string, mixed>> */
    public function events(string $start, string $end): array
    {
        $range = Validator::make(
            ['start' => $start, 'end' => $end],
            [
                'start' => ['required', 'date'],
                'end' => ['required', 'date', 'after:start'],
            ],
        )->validate();

        $this->validateRoomFilter();
        $user = $this->user();
        $rangeStart = Carbon::parse($range['start']);
        $rangeEnd = Carbon::parse($range['end']);

        $reservationEvents = Reserva::query()
            ->with(['sala', 'profesor.carreras'])
            ->where('sala_id', $this->salaId)
            ->entre($rangeStart, $rangeEnd)
            ->where(function ($query) use ($user): void {
                $query->where('estado', ReservaEstado::Aprobada)
                    ->when(
                        $user->isAdministrator(),
                        fn ($query) => $query->orWhere('estado', ReservaEstado::Pendiente),
                        fn ($query) => $query->orWhere(function ($query) use ($user): void {
                            $query
                                ->where('estado', ReservaEstado::Pendiente)
                                ->whereBelongsTo($user, 'profesor');
                        }),
                    );
            })
            ->orderBy('inicio')
            ->get()
            ->map(function (Reserva $reserva) use ($user): array {
                $isPending = $reserva->estado === ReservaEstado::Pendiente;
                $color = $isPending ? '#f59e0b' : '#059669';

                return [
                    'id' => $reserva->id,
                    'title' => $reserva->titulo,
                    'start' => $reserva->inicio->toIso8601String(),
                    'end' => $reserva->fin->toIso8601String(),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => $isPending ? '#451a03' : '#ffffff',
                    'extendedProps' => [
                        'kind' => 'reservation',
                        'sala' => $reserva->sala->nombre,
                        'profesor' => $reserva->profesor->name,
                        'carreras' => $reserva->profesor->carreras->pluck('nombre')->all(),
                        'estado' => $reserva->estado->label(),
                        'canEdit' => Gate::forUser($user)->allows('update', $reserva),
                        'canCancel' => Gate::forUser($user)->allows('cancel', $reserva),
                    ],
                ];
            });

        $holidayEvents = Feriado::query()
            ->entre($rangeStart, $rangeEnd)
            ->orderBy('fecha')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Feriado $feriado): array => [
                'id' => "feriado-{$feriado->id}",
                'title' => $feriado->nombre,
                'start' => $feriado->fecha->toDateString(),
                'allDay' => true,
                'classNames' => ['calendar-holiday-event'],
                'editable' => false,
                'extendedProps' => [
                    'kind' => 'holiday',
                    'jurisdiccion' => $feriado->jurisdiccion,
                    'tipo' => $feriado->tipo,
                    'fuente' => $feriado->fuente,
                    'canEdit' => false,
                    'canCancel' => false,
                ],
            ]);

        return $reservationEvents
            ->concat($holidayEvents)
            ->sortBy('start')
            ->values()
            ->all();
    }

    public function prepareCreateFromCalendar(string $start, string $end): void
    {
        Gate::authorize('create', Reserva::class);

        $range = Validator::make(
            ['start' => $start, 'end' => $end],
            [
                'start' => ['required', 'date'],
                'end' => ['required', 'date', 'after:start'],
            ],
        )->validate();

        $this->reset(['titulo']);
        $this->formSalaId = $this->salaId;
        $this->inicio = Carbon::parse($range['start'])->format('Y-m-d\TH:i');
        $this->fin = Carbon::parse($range['end'])->format('Y-m-d\TH:i');
        $this->resetValidation();

        Flux::modal('create-calendar-reservation')->show();
    }

    public function create(CreateReserva $action): void
    {
        Gate::authorize('create', Reserva::class);

        $validated = $this->validate([
            'formSalaId' => ['required', 'integer', Rule::in([$this->salaId])],
            'titulo' => ['required', 'string', 'max:255'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:inicio'],
        ]);

        $action->handle($this->user(), [
            'sala_id' => (int) $validated['formSalaId'],
            'titulo' => $validated['titulo'],
            'inicio' => $validated['inicio'],
            'fin' => $validated['fin'],
        ]);

        Flux::modal('create-calendar-reservation')->close();
        Flux::toast(variant: 'success', text: 'Solicitud de reserva creada.');
        $this->dispatch('calendar-events-changed');
    }

    public function openReservation(int $reservationId): void
    {
        $reserva = Reserva::query()->with(['sala', 'profesor.carreras'])->findOrFail($reservationId);
        Gate::authorize('view', $reserva);
        abort_unless($reserva->sala_id === (int) $this->salaId, 404);

        $this->selectedReservationId = $reserva->id;
        $this->editTitulo = $reserva->titulo;
        $this->editInicio = $reserva->inicio->format('Y-m-d\TH:i');
        $this->editFin = $reserva->fin->format('Y-m-d\TH:i');
        $this->motivo = '';
        $this->resetValidation();
        unset($this->selectedReservation);

        Flux::modal('calendar-reservation-details')->show();
    }

    public function updateReservation(UpdateReserva $action): void
    {
        $validated = $this->validate([
            'selectedReservationId' => ['required', 'integer', 'exists:reservas,id'],
            'editTitulo' => ['required', 'string', 'max:255'],
            'editInicio' => ['required', 'date'],
            'editFin' => ['required', 'date', 'after:editInicio'],
        ]);

        $reserva = Reserva::query()->findOrFail($validated['selectedReservationId']);
        abort_unless($reserva->sala_id === (int) $this->salaId, 404);

        $action->handle($reserva, $this->user(), [
            'titulo' => $validated['editTitulo'],
            'inicio' => $validated['editInicio'],
            'fin' => $validated['editFin'],
        ]);

        Flux::modal('calendar-reservation-details')->close();
        Flux::toast(variant: 'success', text: 'Reserva actualizada.');
        $this->resetReservationSelection();
        $this->dispatch('calendar-events-changed');
    }

    public function cancelReservation(CancelReserva $action): void
    {
        $validated = $this->validate([
            'selectedReservationId' => ['required', 'integer', 'exists:reservas,id'],
            'motivo' => ['nullable', 'string', 'max:2000'],
        ]);

        $reserva = Reserva::query()->findOrFail($validated['selectedReservationId']);
        abort_unless($reserva->sala_id === (int) $this->salaId, 404);

        $action->handle($reserva, $this->user(), $validated['motivo'] ?: null);

        Flux::modal('calendar-reservation-details')->close();
        Flux::toast(variant: 'success', text: 'Reserva cancelada.');
        $this->resetReservationSelection();
        $this->dispatch('calendar-events-changed');
    }

    private function validateRoomFilter(): void
    {
        Validator::make(
            ['sala_id' => $this->salaId],
            ['sala_id' => ['required', 'integer', Rule::exists('salas', 'id')->where('activa', true)]],
        )->validate();
    }

    private function resetReservationSelection(): void
    {
        $this->reset(['selectedReservationId', 'editTitulo', 'editInicio', 'editFin', 'motivo']);
        unset($this->selectedReservation);
        $this->resetValidation();
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
};
?>

<div
    class="app-page calendar-page"
    x-data="reservationCalendar"
    data-can-create="{{ auth()->user()->can('create', Reserva::class) ? 'true' : 'false' }}"
    @calendar-filter-changed.window="calendar?.refetchEvents()"
    @calendar-events-changed.window="calendar?.refetchEvents()">
    <div class="calendar-room-picker" aria-label="Seleccionar espacio">
        @foreach ($this->salas as $sala)
            <button
                type="button"
                wire:key="calendar-room-pill-{{ $sala->id }}"
                wire:click="$set('salaId', '{{ $sala->id }}')"
                @class(['calendar-filter-pill', 'calendar-filter-pill-active' => $salaId === (string) $sala->id])
                aria-pressed="{{ $salaId === (string) $sala->id ? 'true' : 'false' }}"
            >
                {{ $sala->nombre }}
            </button>
        @endforeach
    </div>

    <flux:card class="app-panel calendar-shell relative overflow-hidden p-3 sm:p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-700">
            <div>
                <p class="app-eyebrow">Calendario del espacio</p>
                <flux:heading size="lg">{{ $this->selectedRoom?->nombre }}</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-600 dark:text-zinc-300">
                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full bg-amber-500"></span>Pendiente</span>
                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full bg-emerald-600"></span>Aprobada</span>
                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full bg-rose-600"></span>Feriado</span>
            </div>
        </div>
        <div wire:loading.flex wire:target="events,salaId" class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-xs dark:bg-zinc-900/70">
            <flux:icon.loading class="size-6" />
        </div>
        <div wire:ignore>
            <div x-ref="calendar" class="reservation-calendar min-h-96"></div>
        </div>
    </flux:card>

    <flux:modal name="create-calendar-reservation" class="md:w-[36rem]">
        <form wire:submit="create" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva reserva</flux:heading>
                <flux:text class="mt-2">Completá los datos para solicitar el horario seleccionado.</flux:text>
            </div>

            <flux:input :value="$this->selectedRoom?->nombre" label="Espacio" readonly />
            <flux:input wire:model="titulo" label="Actividad" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="inicio" type="datetime-local" label="Inicio" readonly />
                <flux:input wire:model="fin" type="datetime-local" label="Finalización" readonly />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">Enviar solicitud</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="calendar-reservation-details" class="md:w-[36rem]">
        @if ($this->selectedReservation)
            <form wire:submit="updateReservation" class="space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">Detalle de la reserva</flux:heading>
                        <flux:text class="mt-2">
                            {{ $this->selectedReservation->sala->nombre }} · Profesor/a {{ $this->selectedReservation->profesor->name }}
                        </flux:text>
                        <flux:text class="mt-1 text-sm">
                            {{ $this->selectedReservation->profesor->carreras->pluck('nombre')->implode(' · ') ?: 'Sin carrera informada' }}
                        </flux:text>
                    </div>
                    <flux:badge :color="$this->selectedReservation->estado->color()">
                        {{ $this->selectedReservation->estado->label() }}
                    </flux:badge>
                </div>

                @can('update', $this->selectedReservation)
                    <flux:input wire:model="editTitulo" label="Actividad" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="editInicio" type="datetime-local" label="Inicio" required />
                        <flux:input wire:model="editFin" type="datetime-local" label="Finalización" required />
                    </div>
                @else
                    <div class="rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-800">
                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $this->selectedReservation->titulo }}</p>
                        <p class="app-date mt-1 text-zinc-600 dark:text-zinc-300">
                            {{ $this->selectedReservation->inicio->format('d/m/Y H:i') }}–{{ $this->selectedReservation->fin->format('H:i') }}
                        </p>
                    </div>
                @endcan

                @can('cancel', $this->selectedReservation)
                    <flux:textarea wire:model="motivo" label="Motivo de cancelación (opcional)" rows="3" />
                @endcan

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cerrar</flux:button>
                    </flux:modal.close>
                    @can('cancel', $this->selectedReservation)
                        <flux:button
                            type="button"
                            variant="danger"
                            wire:click="cancelReservation"
                            wire:confirm="¿Confirmás que querés cancelar esta reserva?"
                            wire:loading.attr="disabled"
                            wire:target="cancelReservation"
                        >
                            Cancelar reserva
                        </flux:button>
                    @endcan
                    @can('update', $this->selectedReservation)
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateReservation">
                            Guardar cambios
                        </flux:button>
                    @endcan
                </div>
            </form>
        @endif
    </flux:modal>
</div>
