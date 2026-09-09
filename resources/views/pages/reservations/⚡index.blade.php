<?php

use App\Actions\Reservas\ApproveReserva;
use App\Actions\Reservas\CancelReserva;
use App\Actions\Reservas\CreateReserva;
use App\Actions\Reservas\RejectReserva;
use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Reservas')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $estado = '';
    public string $salaId = '';

    public string $titulo = '';
    public string $descripcion = '';
    #[Url(as: 'sala')]
    public string $formSalaId = '';
    public int $cantidadAsistentes = 1;
    public string $inicio = '';
    public string $fin = '';

    public ?int $selectedReservationId = null;
    public string $motivo = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Reserva::class);
    }

    /** @return LengthAwarePaginator<int, Reserva> */
    #[Computed]
    public function reservas(): LengthAwarePaginator
    {
        $user = $this->user();

        return Reserva::query()
            ->with(['sala', 'profesor'])
            ->when($user->isProfessor(), fn ($query) => $query->whereBelongsTo($user, 'profesor'))
            ->when($this->estado !== '', fn ($query) => $query->where('estado', $this->estado))
            ->when($this->salaId !== '', fn ($query) => $query->where('sala_id', $this->salaId))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('titulo', 'like', '%'.$this->search.'%')
                        ->orWhereHas('profesor', fn ($query) => $query
                            ->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('inicio')
            ->orderByDesc('id')
            ->paginate(10);
    }

    /** @return Collection<int, Sala> */
    #[Computed]
    public function salas(): Collection
    {
        return Sala::query()->activas()->orderBy('nombre')->get();
    }

    /** @return list<ReservaEstado> */
    #[Computed]
    public function estados(): array
    {
        return ReservaEstado::cases();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function updatedSalaId(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Reserva::class);

        $this->reset(['titulo', 'descripcion', 'cantidadAsistentes', 'inicio', 'fin']);
        $this->cantidadAsistentes = 1;
        $this->resetValidation();
        Flux::modal('create-reservation')->show();
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

        unset($this->reservas);
        $this->resetReservationForm();
        Flux::modal('create-reservation')->close();
        Flux::toast(variant: 'success', text: 'Solicitud de reserva creada.');
    }

    public function approve(int $reservationId, ApproveReserva $action): void
    {
        $reserva = Reserva::query()->findOrFail($reservationId);
        $action->handle($reserva, $this->user());

        unset($this->reservas);
        Flux::toast(variant: 'success', text: 'Reserva aprobada.');
    }

    public function prepareRejection(int $reservationId): void
    {
        $reserva = Reserva::query()->findOrFail($reservationId);
        Gate::authorize('reject', $reserva);

        $this->selectedReservationId = $reserva->id;
        $this->motivo = '';
        $this->resetValidation();
        Flux::modal('reject-reservation')->show();
    }

    public function reject(RejectReserva $action): void
    {
        $validated = $this->validate([
            'selectedReservationId' => ['required', 'integer', 'exists:reservas,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $reserva = Reserva::query()->findOrFail($validated['selectedReservationId']);
        $action->handle($reserva, $this->user(), $validated['motivo']);

        unset($this->reservas);
        $this->reset(['selectedReservationId', 'motivo']);
        Flux::modal('reject-reservation')->close();
        Flux::toast(variant: 'success', text: 'Solicitud rechazada.');
    }

    public function prepareCancellation(int $reservationId): void
    {
        $reserva = Reserva::query()->findOrFail($reservationId);
        Gate::authorize('cancel', $reserva);

        $this->selectedReservationId = $reserva->id;
        $this->motivo = '';
        $this->resetValidation();
        Flux::modal('cancel-reservation')->show();
    }

    public function cancel(CancelReserva $action): void
    {
        $validated = $this->validate([
            'selectedReservationId' => ['required', 'integer', 'exists:reservas,id'],
            'motivo' => ['nullable', 'string', 'max:2000'],
        ]);

        $reserva = Reserva::query()->findOrFail($validated['selectedReservationId']);
        $action->handle($reserva, $this->user(), $validated['motivo'] ?: null);

        unset($this->reservas);
        $this->reset(['selectedReservationId', 'motivo']);
        Flux::modal('cancel-reservation')->close();
        Flux::toast(variant: 'success', text: 'Reserva cancelada.');
    }

    private function resetReservationForm(): void
    {
        $this->reset(['formSalaId', 'titulo', 'descripcion', 'cantidadAsistentes', 'inicio', 'fin']);
        $this->cantidadAsistentes = 1;
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

<div class="app-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid gap-1">
            <p class="app-eyebrow">Gestión académica de espacios</p>
            <flux:heading size="xl" level="1">{{ auth()->user()->isAdministrator() ? 'Gestión integral de reservas' : 'Mis reservas' }}</flux:heading>
            <flux:text>Consultá y gestioná las solicitudes de espacios institucionales.</flux:text>
        </div>

        @can('create', Reserva::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreate">Nueva reserva</flux:button>
        @endcan
    </div>

    <flux:card class="app-panel">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" label="Buscar" placeholder="Título o profesor" />

            <flux:select wire:model.live="estado" label="Estado">
                <flux:select.option value="">Todos los estados</flux:select.option>
                @foreach ($this->estados as $reservationStatus)
                    <flux:select.option :value="$reservationStatus->value" wire:key="status-{{ $reservationStatus->value }}">
                        {{ $reservationStatus->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="salaId" label="Espacio">
                <flux:select.option value="">Todos los espacios</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option :value="$sala->id" wire:key="room-{{ $sala->id }}">{{ $sala->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:card class="app-table-shell p-0">
        <flux:table :paginate="$this->reservas">
            <flux:table.columns>
                <flux:table.column>Reserva</flux:table.column>
                @if (auth()->user()->isAdministrator())
                    <flux:table.column>Profesor</flux:table.column>
                @endif
                <flux:table.column>Fecha y horario</flux:table.column>
                <flux:table.column>Asistentes</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->reservas as $reserva)
                    <flux:table.row :key="$reserva->id">
                        <flux:table.cell>
                            <div class="grid gap-1">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $reserva->titulo }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $reserva->sala->nombre }}</span>
                            </div>
                        </flux:table.cell>
                        @if (auth()->user()->isAdministrator())
                            <flux:table.cell>{{ $reserva->profesor->name }}</flux:table.cell>
                        @endif
                        <flux:table.cell>
                            <div class="app-date grid gap-1 text-sm">
                                <span>{{ $reserva->inicio->format('d/m/Y') }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $reserva->inicio->format('H:i') }}–{{ $reserva->fin->format('H:i') }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $reserva->cantidad_asistentes }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$reserva->estado->color()" size="sm">{{ $reserva->estado->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex flex-wrap justify-end gap-2">
                                @can('approve', $reserva)
                                    <flux:button size="sm" variant="primary" wire:click="approve({{ $reserva->id }})">Aprobar</flux:button>
                                @endcan
                                @can('reject', $reserva)
                                    <flux:button size="sm" variant="danger" wire:click="prepareRejection({{ $reserva->id }})">Rechazar</flux:button>
                                @endcan
                                @can('cancel', $reserva)
                                    <flux:button size="sm" variant="ghost" wire:click="prepareCancellation({{ $reserva->id }})">Cancelar</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center">No hay reservas para mostrar.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="create-reservation" class="md:w-[36rem]">
        <form wire:submit="create" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva reserva</flux:heading>
                <flux:text class="mt-2">La solicitud quedará pendiente de aprobación administrativa.</flux:text>
            </div>

            <flux:select wire:model="formSalaId" label="Espacio" required>
                <flux:select.option value="">Seleccioná un espacio</flux:select.option>
                @foreach ($this->salas as $sala)
                    <flux:select.option :value="$sala->id" wire:key="form-room-{{ $sala->id }}">
                        {{ $sala->nombre }} · hasta {{ $sala->capacidad }} personas
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="titulo" label="Actividad" required />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="3" />
            <flux:input wire:model="cantidadAsistentes" type="number" min="1" label="Cantidad de asistentes" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="inicio" type="datetime-local" label="Inicio" required />
                <flux:input wire:model="fin" type="datetime-local" label="Finalización" required />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="create">Enviar solicitud</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="reject-reservation" class="md:w-[30rem]">
        <form wire:submit="reject" class="space-y-6">
            <div>
                <flux:heading size="lg">Rechazar solicitud</flux:heading>
                <flux:text class="mt-2">Indicá el motivo para que el profesor pueda conocer la decisión.</flux:text>
            </div>
            <flux:textarea wire:model="motivo" label="Motivo" rows="4" required />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Volver</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Confirmar rechazo</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="cancel-reservation" class="md:w-[30rem]">
        <form wire:submit="cancel" class="space-y-6">
            <div>
                <flux:heading size="lg">Cancelar reserva</flux:heading>
                <flux:text class="mt-2">Esta acción conserva la solicitud y registra el cambio en su historial.</flux:text>
            </div>
            <flux:textarea wire:model="motivo" label="Motivo (opcional)" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Volver</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Confirmar cancelación</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
