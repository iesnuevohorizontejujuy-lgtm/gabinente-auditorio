<?php

use App\Models\Reserva;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Calendario de reservas')] class extends Component
{
    public string $salaId = '';

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

        $this->dispatch('calendar-filter-changed');
    }

    /**
     * @return array<int, array<string, int|string>>
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

        Validator::make(
            ['sala_id' => $this->salaId],
            ['sala_id' => ['nullable', 'integer', 'exists:salas,id']],
        )->validate();

        $colors = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'];

        return Reserva::query()
            ->with('sala')
            ->aprobadas()
            ->entre(Carbon::parse($range['start']), Carbon::parse($range['end']))
            ->when($this->salaId !== '', fn ($query) => $query->where('sala_id', $this->salaId))
            ->orderBy('inicio')
            ->get()
            ->map(fn (Reserva $reserva): array => [
                'id' => $reserva->id,
                'title' => $reserva->titulo.' · '.$reserva->sala->nombre,
                'start' => $reserva->inicio->toIso8601String(),
                'end' => $reserva->fin->toIso8601String(),
                'backgroundColor' => $colors[($reserva->sala_id - 1) % count($colors)],
                'borderColor' => $colors[($reserva->sala_id - 1) % count($colors)],
            ])
            ->all();
    }
};
?>

<div
    class="app-page"
    x-data="reservationCalendar"
    @calendar-filter-changed.window="calendar?.refetchEvents()"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid gap-1">
            <p class="app-eyebrow">Agenda institucional</p>
            <flux:heading size="xl" level="1">Calendario de espacios</flux:heading>
            <flux:text>Consultá la disponibilidad aprobada de los espacios institucionales.</flux:text>
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

    <flux:card class="app-panel relative overflow-hidden">
        <div
            wire:loading.flex
            wire:target="events,salaId"
            class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-xs dark:bg-zinc-900/70"
        >
            <flux:icon.loading class="size-6" />
        </div>

        <div wire:ignore>
            <div x-ref="calendar" class="reservation-calendar min-h-96"></div>
        </div>
    </flux:card>
</div>
