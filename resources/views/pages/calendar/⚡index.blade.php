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
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Calendario de reservas')] class extends Component
{
    #[Url(as: 'sala')]
    public string $salaId = '';

    public string $weekStart = '';

    public string $formSalaId = '';

    public string $titulo = '';

    public string $descripcion = '';

    public int $cantidadAsistentes = 1;

    public string $inicio = '';

    public string $fin = '';

    /** @var list<array{inicio: string, fin: string}> */
    private const TIME_SLOTS = [
        ['inicio' => '08:00', 'fin' => '09:20'],
        ['inicio' => '09:20', 'fin' => '10:00'],
        ['inicio' => '10:00', 'fin' => '10:40'],
        ['inicio' => '10:40', 'fin' => '12:00'],
        ['inicio' => '12:00', 'fin' => '12:40'],
        ['inicio' => '12:40', 'fin' => '13:20'],
        ['inicio' => '13:20', 'fin' => '15:20'],
        ['inicio' => '15:20', 'fin' => '16:00'],
        ['inicio' => '16:00', 'fin' => '17:00'],
        ['inicio' => '17:00', 'fin' => '18:00'],
        ['inicio' => '18:00', 'fin' => '19:00'],
        ['inicio' => '19:00', 'fin' => '20:00'],
        ['inicio' => '20:00', 'fin' => '21:00'],
    ];

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

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
        Validator::make(
            ['sala_id' => $this->salaId],
            ['sala_id' => ['nullable', 'integer', 'exists:salas,id']],
        )->validate();

    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
        unset($this->calendarReservations, $this->weekDays);
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
        unset($this->calendarReservations, $this->weekDays);
    }

    public function currentWeek(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        unset($this->calendarReservations, $this->weekDays);
    }

    #[On('calendar-slot-selected')]
    public function prepareCreateFromCalendar(string $date, int $startSlot, int $endSlot): void
    {
        Gate::authorize('create', Reserva::class);

        $validated = Validator::make(
            ['date' => $date, 'start_slot' => $startSlot, 'end_slot' => $endSlot],
            [
                'date' => ['required', Rule::in(array_column($this->weekDays, 'fecha'))],
                'start_slot' => ['required', 'integer', 'min:0', 'max:'.(count(self::TIME_SLOTS) - 1)],
                'end_slot' => ['required', 'integer', 'gte:start_slot', 'max:'.(count(self::TIME_SLOTS) - 1)],
            ],
        )->validate();

        $start = self::TIME_SLOTS[$validated['start_slot']]['inicio'];
        $end = self::TIME_SLOTS[$validated['end_slot']]['fin'];

        $this->reset(['titulo', 'descripcion', 'cantidadAsistentes']);
        $this->cantidadAsistentes = 1;
        $this->formSalaId = $this->salaId;
        $this->inicio = "{$validated['date']}T{$start}";
        $this->fin = "{$validated['date']}T{$end}";
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

        unset($this->calendarReservations);
        Flux::modal('create-calendar-reservation')->close();
        Flux::toast(variant: 'success', text: 'Solicitud de reserva creada.');
    }

    /** @return list<array{nombre: string, fecha: string, etiqueta: string}> */
    #[Computed]
    public function weekDays(): array
    {
        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
        $weekStart = Carbon::parse($this->weekStart)->startOfDay();

        return collect($dayNames)
            ->map(fn (string $name, int $offset): array => [
                'nombre' => $name,
                'fecha' => $weekStart->copy()->addDays($offset)->toDateString(),
                'etiqueta' => $weekStart->copy()->addDays($offset)->format('d/m'),
            ])
            ->all();
    }

    /** @return list<array{inicio: string, fin: string}> */
    #[Computed]
    public function timeSlots(): array
    {
        return self::TIME_SLOTS;
    }

    /** @return Collection<int, Reserva> */
    #[Computed]
    public function calendarReservations(): Collection
    {
        $weekStart = Carbon::parse($this->weekStart)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(5);

        return Reserva::query()
            ->with(['sala', 'profesor'])
            ->aprobadas()
            ->entre($weekStart, $weekEnd)
            ->when($this->salaId !== '', fn ($query) => $query->where('sala_id', $this->salaId))
            ->orderBy('inicio')
            ->get();
    }

    public function reservationForSlot(string $date, string $start, string $end): ?Reserva
    {
        $slotStart = Carbon::parse("{$date} {$start}");
        $slotEnd = Carbon::parse("{$date} {$end}");

        return $this->calendarReservations->first(
            fn (Reserva $reserva): bool => $reserva->inicio->lt($slotEnd) && $reserva->fin->gt($slotStart),
        );
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
    class="app-page"
    x-data="{
        selecting: false,
        selectedDay: null,
        startSlot: null,
        endSlot: null,
        begin(day, slot) {
            this.selecting = true;
            this.selectedDay = day;
            this.startSlot = slot;
            this.endSlot = slot;
        },
        extend(day, slot) {
            if (this.selecting && this.selectedDay === day) {
                this.endSlot = slot;
            }
        },
        finish() {
            if (! this.selecting) {
                return;
            }

            const startSlot = Math.min(this.startSlot, this.endSlot);
            const endSlot = Math.max(this.startSlot, this.endSlot);

            this.selecting = false;
            $dispatch('calendar-slot-selected', {
                date: this.selectedDay,
                startSlot,
                endSlot,
            });
        },
        isSelected(day, slot) {
            if (this.selectedDay !== day || this.startSlot === null || this.endSlot === null) {
                return false;
            }

            return slot >= Math.min(this.startSlot, this.endSlot) && slot <= Math.max(this.startSlot, this.endSlot);
        },
    }"
    @pointerup.window="finish()"
    @pointercancel.window="selecting = false"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid gap-1">
            <p class="app-eyebrow">Agenda institucional</p>
            <flux:heading size="xl" level="1">Calendario de espacios</flux:heading>
            <flux:text>Consultá la disponibilidad aprobada de los espacios institucionales.</flux:text>
            @can('create', Reserva::class)
                <flux:text class="text-sm">Arrastrá sobre bloques sin asignar para solicitar una reserva.</flux:text>
            @endcan
        </div>

        <flux:field class="w-full sm:max-w-xs">
            <flux:label>Espacio</flux:label>
            <flux:select wire:model.live="salaId">
                <flux:select.option value="">Todos los espacios</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option :value="$sala->id" wire:key="calendar-room-{{ $sala->id }}">
                        {{ $sala->nombre }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="salaId" />
        </flux:field>
    </div>

    <flux:card class="app-panel overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousWeek">Semana anterior</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="currentWeek">Semana actual</flux:button>
                <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="nextWeek">Semana siguiente</flux:button>
            </div>
            <p class="app-date text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ \Illuminate\Support\Carbon::parse($weekStart)->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($weekStart)->addDays(4)->format('d/m/Y') }}
            </p>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="salaId,previousWeek,nextWeek,currentWeek">
            <div class="grid min-w-240 grid-cols-[8rem_repeat(5,minmax(10rem,1fr))]">
                <div class="sticky left-0 z-10 border-b border-r border-zinc-200 bg-zinc-50 p-3 text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">Horario</div>
                @foreach ($this->weekDays as $weekDay)
                    <div wire:key="day-{{ $weekDay['fecha'] }}" class="border-b border-r border-zinc-200 bg-zinc-50 p-3 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $weekDay['nombre'] }}</p>
                        <p class="app-date text-xs text-zinc-500 dark:text-zinc-400">{{ $weekDay['etiqueta'] }}</p>
                    </div>
                @endforeach

                @foreach ($this->timeSlots as $slotIndex => $timeSlot)
                    <div wire:key="time-{{ $timeSlot['inicio'] }}" class="sticky left-0 z-10 flex min-h-20 items-center border-b border-r border-zinc-200 bg-white p-3 text-center text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        {{ $timeSlot['inicio'] }} a {{ $timeSlot['fin'] }}
                    </div>
                    @foreach ($this->weekDays as $weekDay)
                        @php($reserva = $this->reservationForSlot($weekDay['fecha'], $timeSlot['inicio'], $timeSlot['fin']))
                        <div
                            wire:key="slot-{{ $weekDay['fecha'] }}-{{ $timeSlot['inicio'] }}"
                            @if (! $reserva && auth()->user()->can('create', Reserva::class))
                                @pointerdown.prevent="begin('{{ $weekDay['fecha'] }}', {{ $slotIndex }})"
                                @pointerenter="extend('{{ $weekDay['fecha'] }}', {{ $slotIndex }})"
                                :class="isSelected('{{ $weekDay['fecha'] }}', {{ $slotIndex }}) ? 'bg-campus-100/70 dark:bg-campus-900/40' : ''"
                            @endif
                            class="min-h-20 border-b border-r border-zinc-200 p-2 dark:border-zinc-700"
                        >
                            @if ($reserva)
                                <div class="h-full rounded-md bg-campus-100 p-2 text-xs text-campus-900 dark:bg-campus-900/60 dark:text-campus-100">
                                    <p class="font-semibold">{{ $reserva->titulo }}</p>
                                    <p class="mt-1 text-campus-700 dark:text-campus-300">{{ $reserva->sala->nombre }}</p>
                                    <p class="mt-1 text-campus-700 dark:text-campus-300">Prof. {{ $reserva->profesor->name }}</p>
                                </div>
                            @else
                                <div class="flex h-full min-h-16 items-center justify-center rounded-md bg-zinc-50 px-2 text-center text-xs font-medium text-zinc-400 dark:bg-zinc-800/40 dark:text-zinc-500">
                                    Sin asignar
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </flux:card>

    <flux:modal name="create-calendar-reservation" class="md:w-[36rem]">
        <form wire:submit="create" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva reserva</flux:heading>
                <flux:text class="mt-2">Completá los datos para solicitar los bloques seleccionados.</flux:text>
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
