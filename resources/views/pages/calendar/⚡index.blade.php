<?php

use App\Actions\Reservas\CreateReserva;
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
    public string $descripcion = '';
    public int $cantidadAsistentes = 1;
    public string $inicio = '';
    public string $fin = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($this->salaId === '' && $user instanceof User && $user->isAdministrator()) {
            $this->salaId = (string) Sala::query()
                ->activas()
                ->where('nombre', 'Gabinete de Informática')
                ->value('id');
        }
    }

    /** @return Collection<int, Sala> */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->activas()->orderBy('nombre')->get();
    }

    public function updatedSalaId(): void
    {
        $this->validateRoomFilter();
        $this->dispatch('calendar-filter-changed');
    }

    /**
     * @return list<array{
     *     id: int,
     *     title: string,
     *     start: string,
     *     end: string,
     *     backgroundColor: string,
     *     borderColor: string,
     *     extendedProps: array{sala: string, profesor: string, estado: string}
     * }>
     */
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
        $colors = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'];

        return Reserva::query()
            ->with(['sala', 'profesor'])
            ->aprobadas()
            ->entre(Carbon::parse($range['start']), Carbon::parse($range['end']))
            ->when($this->salaId !== '', fn ($query) => $query->where('sala_id', $this->salaId))
            ->orderBy('inicio')
            ->get()
            ->map(function (Reserva $reserva) use ($colors): array {
                $color = $colors[($reserva->sala_id - 1) % count($colors)];

                return [
                    'id' => $reserva->id,
                    'title' => $reserva->titulo,
                    'start' => $reserva->inicio->toIso8601String(),
                    'end' => $reserva->fin->toIso8601String(),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'sala' => $reserva->sala->nombre,
                        'profesor' => $reserva->profesor->name,
                        'estado' => 'Aprobada',
                    ],
                ];
            })
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

        $this->reset(['titulo', 'descripcion', 'cantidadAsistentes']);
        $this->cantidadAsistentes = 1;
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
            'formSalaId' => ['required', 'integer', Rule::exists('salas', 'id')->where('activa', true)],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'cantidadAsistentes' => ['required', 'integer', 'min:1', 'max:10000'],
            'inicio' => ['required', 'date'],
            'fin' => ['required', 'date', 'after:inicio'],
        ]);

        $action->handle($this->user(), [
            'sala_id' => (int) $validated['formSalaId'],
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'] ?: null,
            'cantidad_asistentes' => (int) $validated['cantidadAsistentes'],
            'inicio' => $validated['inicio'],
            'fin' => $validated['fin'],
        ]);

        Flux::modal('create-calendar-reservation')->close();
        Flux::toast(variant: 'success', text: 'Solicitud de reserva creada.');
        $this->dispatch('calendar-events-changed');
    }

    /** @return array<int, int> */
    #[Computed]
    public function roomOccupancy(): array
    {
        $businessDayStart = now()->startOfDay()->setTime(8, 0);
        $businessDayEnd = now()->startOfDay()->setTime(21, 0);
        $availableMinutes = $businessDayStart->diffInMinutes($businessDayEnd);
        $salaIds = $this->salas->pluck('id');

        $reservations = Reserva::query()
            ->aprobadas()
            ->whereIn('sala_id', $salaIds)
            ->entre($businessDayStart, $businessDayEnd)
            ->get(['id', 'sala_id', 'inicio', 'fin']);

        return $this->salas
            ->mapWithKeys(function (Sala $sala) use ($reservations, $businessDayStart, $businessDayEnd, $availableMinutes): array {
                $occupiedMinutes = $reservations
                    ->where('sala_id', $sala->id)
                    ->sum(function (Reserva $reserva) use ($businessDayStart, $businessDayEnd): float {
                        $start = $reserva->inicio->greaterThan($businessDayStart) ? $reserva->inicio : $businessDayStart;
                        $end = $reserva->fin->lessThan($businessDayEnd) ? $reserva->fin : $businessDayEnd;

                        return $start->diffInMinutes($end);
                    });

                return [$sala->id => (int) min(100, round(($occupiedMinutes / $availableMinutes) * 100))];
            })
            ->all();
    }

    private function validateRoomFilter(): void
    {
        Validator::make(
            ['sala_id' => $this->salaId],
            ['sala_id' => ['nullable', 'integer', Rule::exists('salas', 'id')->where('activa', true)]],
        )->validate();
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
    @calendar-events-changed.window="calendar?.refetchEvents()"
>
    <flux:card class="calendar-hero p-5 sm:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <p class="app-eyebrow">Agenda institucional</p>
                        <flux:heading size="xl" level="1">Calendario de espacios institucionales</flux:heading>
                    </div>
                    <flux:badge color="blue" size="sm">Actualizado en tiempo real</flux:badge>
                </div>
                <flux:text>Consultá la disponibilidad aprobada en vistas mensual, semanal o diaria.</flux:text>
                @can('create', Reserva::class)
                    <flux:text class="text-sm">Seleccioná un intervalo libre para iniciar una solicitud.</flux:text>
                @endcan
            </div>

            @can('create', Reserva::class)
                <flux:button :href="route('reservations.index', array_filter(['sala' => $salaId]))" wire:navigate variant="primary" icon="plus">
                    Nueva reserva
                </flux:button>
            @endcan
        </div>
    </flux:card>

    <flux:card class="calendar-filters p-4 sm:p-5">
        <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
            <div class="grid gap-3">
                <p class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">Espacios</p>
                <div class="hidden flex-wrap gap-2 md:flex">
                    <button type="button" wire:click="$set('salaId', '')" @class(['calendar-filter-pill', 'calendar-filter-pill-active' => $salaId === '']) aria-pressed="{{ $salaId === '' ? 'true' : 'false' }}">
                        Todos los espacios
                    </button>
                    @foreach ($this->salas as $sala)
                        <button
                            type="button"
                            wire:key="calendar-room-pill-{{ $sala->id }}"
                            wire:click="$set('salaId', '{{ $sala->id }}')"
                            @class(['calendar-filter-pill', 'calendar-filter-pill-active' => $salaId === (string) $sala->id])
                            aria-pressed="{{ $salaId === (string) $sala->id ? 'true' : 'false' }}"
                        >
                            {{ $sala->nombre }} <span class="text-xs opacity-70">({{ $sala->capacidad }}p)</span>
                        </button>
                    @endforeach
                </div>

                <flux:field class="md:hidden">
                    <flux:label>Espacio</flux:label>
                    <flux:select wire:model.live="salaId">
                        <flux:select.option value="">Todos los espacios</flux:select.option>
                        @foreach ($this->salas as $sala)
                            <flux:select.option :value="$sala->id" wire:key="calendar-room-{{ $sala->id }}">{{ $sala->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="salaId" />
                </flux:field>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                <span class="font-semibold tracking-wide uppercase">Estado visible</span>
                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full bg-emerald-600"></span>Aprobadas</span>
            </div>
        </div>
    </flux:card>

    <flux:card class="app-panel calendar-shell relative overflow-hidden p-3 sm:p-5">
        <div wire:loading.flex wire:target="events,salaId" class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-xs dark:bg-zinc-900/70">
            <flux:icon.loading class="size-6" />
        </div>
        <div wire:ignore>
            <div x-ref="calendar" class="reservation-calendar min-h-96"></div>
        </div>
    </flux:card>

    <flux:card class="calendar-summary p-4 sm:p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">Indicaciones</span>
                <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-full bg-emerald-600"></span>Reserva aprobada</span>
                @can('create', Reserva::class)
                    <span class="inline-flex items-center gap-2"><flux:icon.cursor-arrow-ripple class="size-4" />Arrastrá para seleccionar un horario</span>
                @endcan
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400">Ocupación de hoy</span>
                @foreach ($this->salas as $sala)
                    <div wire:key="calendar-occupancy-{{ $sala->id }}" class="calendar-occupancy">
                        <div class="flex items-center justify-between gap-4 text-xs">
                            <span class="max-w-28 truncate font-medium">{{ $sala->nombre }}</span>
                            <span class="app-date text-zinc-500 dark:text-zinc-400">{{ $this->roomOccupancy[$sala->id] }}%</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full rounded-full bg-campus-600" style="width: {{ $this->roomOccupancy[$sala->id] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </flux:card>

    <flux:modal name="create-calendar-reservation" class="md:w-[36rem]">
        <form wire:submit="create" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva reserva</flux:heading>
                <flux:text class="mt-2">Completá los datos para solicitar el horario seleccionado.</flux:text>
            </div>

            <flux:select wire:model="formSalaId" label="Espacio" required>
                <flux:select.option value="">Seleccioná un espacio</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option :value="$sala->id" wire:key="calendar-form-room-{{ $sala->id }}">
                        {{ $sala->nombre }} · hasta {{ $sala->capacidad }} personas
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="titulo" label="Actividad" required />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="3" />
            <flux:input wire:model="cantidadAsistentes" type="number" min="1" label="Cantidad de asistentes" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="inicio" type="datetime-local" label="Inicio" readonly />
                <flux:input wire:model="fin" type="datetime-local" label="Finalización" readonly />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">Enviar solicitud</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
