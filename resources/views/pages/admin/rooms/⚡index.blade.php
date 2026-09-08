<?php

use App\Models\Sala;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Gestión de espacios')] class extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public string $nombre = '';
    public string $descripcion = '';
    public string $ubicacion = '';
    public int $capacidad = 1;
    public bool $activa = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', Sala::class);
    }

    /** @return LengthAwarePaginator<int, Sala> */
    #[Computed]
    public function salas(): LengthAwarePaginator
    {
        return Sala::query()
            ->withCount('reservas')
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function create(): void
    {
        Gate::authorize('create', Sala::class);
        $this->resetForm();
        Flux::modal('room-form')->show();
    }

    public function edit(Sala $sala): void
    {
        Gate::authorize('update', $sala);

        $this->editingId = $sala->id;
        $this->nombre = $sala->nombre;
        $this->descripcion = $sala->descripcion ?? '';
        $this->ubicacion = $sala->ubicacion ?? '';
        $this->capacidad = $sala->capacidad;
        $this->activa = $sala->activa;
        $this->resetValidation();

        Flux::modal('room-form')->show();
    }

    public function save(): void
    {
        $sala = $this->editingId === null
            ? null
            : Sala::query()->findOrFail($this->editingId);

        Gate::authorize($sala === null ? 'create' : 'update', $sala ?? Sala::class);

        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('salas')->ignore($sala)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'capacidad' => ['required', 'integer', 'min:1', 'max:10000'],
            'activa' => ['boolean'],
        ]);

        $validated['descripcion'] = $validated['descripcion'] ?: null;
        $validated['ubicacion'] = $validated['ubicacion'] ?: null;

        if ($sala === null) {
            Sala::query()->create($validated);
            $message = 'Espacio creado correctamente.';
        } else {
            $sala->update($validated);
            $message = 'Espacio actualizado correctamente.';
        }

        unset($this->salas);
        $this->resetForm();
        Flux::modal('room-form')->close();
        Flux::toast(variant: 'success', text: $message);
    }

    public function toggleActive(Sala $sala): void
    {
        Gate::authorize('update', $sala);

        $sala->update(['activa' => ! $sala->activa]);
        unset($this->salas);

        Flux::toast(
            variant: 'success',
            text: $sala->activa ? 'Espacio habilitado.' : 'Espacio deshabilitado.',
        );
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'descripcion', 'ubicacion', 'capacidad', 'activa']);
        $this->capacidad = 1;
        $this->activa = true;
        $this->resetValidation();
    }
};
?>

<div class="app-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid gap-1">
            <p class="app-eyebrow">Infraestructura y recursos físicos</p>
            <flux:heading size="xl" level="1">Gestión de espacios del campus</flux:heading>
            <flux:text>Administrá las salas disponibles sin eliminar su historial.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">Nuevo espacio</flux:button>
    </div>

    <flux:card class="app-table-shell p-0">
        <flux:table :paginate="$this->salas">
            <flux:table.columns>
                <flux:table.column>Espacio</flux:table.column>
                <flux:table.column>Ubicación</flux:table.column>
                <flux:table.column>Capacidad</flux:table.column>
                <flux:table.column>Reservas</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->salas as $sala)
                    <flux:table.row :key="$sala->id">
                        <flux:table.cell variant="strong">{{ $sala->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->ubicacion ?: 'Sin especificar' }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->capacidad }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->reservas_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$sala->activa ? 'green' : 'zinc'" size="sm">
                                {{ $sala->activa ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $sala->id }})">Editar</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $sala->id }})">
                                    {{ $sala->activa ? 'Deshabilitar' : 'Habilitar' }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center">No hay espacios registrados.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="room-form" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId === null ? 'Nuevo espacio' : 'Editar espacio' }}</flux:heading>
                <flux:text class="mt-2">Completá los datos operativos de la sala.</flux:text>
            </div>

            <flux:input wire:model="nombre" label="Nombre" required />
            <flux:input wire:model="ubicacion" label="Ubicación" />
            <flux:input wire:model="capacidad" type="number" min="1" label="Capacidad" required />
            <flux:textarea wire:model="descripcion" label="Descripción" rows="3" />
            <flux:switch wire:model="activa" label="Espacio activo" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
