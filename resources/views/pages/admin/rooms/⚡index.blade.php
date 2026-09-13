<?php

use App\Models\Sala;
use App\Models\SalaImagen;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Gestión de espacios')] class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;
    public string $nombre = '';
    public string $descripcion = '';
    public string $ubicacion = '';
    public int $capacidad = 1;
    public string $horaInicioOperativo = '08:00';
    public string $horaFinOperativo = '21:00';
    public bool $activa = true;

    /** @var array<int, UploadedFile> */
    public array $imagenes = [];

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

    /** @return Collection<int, SalaImagen> */
    #[Computed]
    public function imagenesExistentes(): Collection
    {
        if ($this->editingId === null) {
            return new Collection;
        }

        return SalaImagen::query()
            ->where('sala_id', $this->editingId)
            ->orderBy('orden')
            ->get();
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
        $this->horaInicioOperativo = substr($sala->hora_inicio_operativo, 0, 5);
        $this->horaFinOperativo = substr($sala->hora_fin_operativo, 0, 5);
        $this->activa = $sala->activa;
        unset($this->imagenesExistentes);
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
            'horaInicioOperativo' => ['required', 'date_format:H:i'],
            'horaFinOperativo' => ['required', 'date_format:H:i', 'after:horaInicioOperativo'],
            'activa' => ['boolean'],
            'imagenes' => ['nullable', 'array', 'max:10'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $validated['descripcion'] = $validated['descripcion'] ?: null;
        $validated['ubicacion'] = $validated['ubicacion'] ?: null;
        $validated['hora_inicio_operativo'] = $validated['horaInicioOperativo'];
        $validated['hora_fin_operativo'] = $validated['horaFinOperativo'];

        unset($validated['horaInicioOperativo'], $validated['horaFinOperativo'], $validated['imagenes']);
        $rutasAlmacenadas = [];

        try {
            DB::transaction(function () use ($sala, $validated, &$rutasAlmacenadas): void {
                if ($sala === null) {
                    $sala = Sala::query()->create($validated);
                } else {
                    $sala->update($validated);
                }

                $orden = (int) $sala->imagenes()->max('orden') + 1;

                foreach ($this->imagenes as $imagen) {
                    $ruta = $imagen->store("salas/{$sala->id}", 'public');
                    $rutasAlmacenadas[] = $ruta;

                    $sala->imagenes()->create([
                        'ruta' => $ruta,
                        'orden' => $orden++,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($rutasAlmacenadas);

            throw $exception;
        }

        $message = $sala === null ? 'Espacio creado correctamente.' : 'Espacio actualizado correctamente.';

        unset($this->salas);
        $this->resetForm();
        Flux::modal('room-form')->close();
        Flux::toast(variant: 'success', text: $message);
    }

    public function deleteImage(int $imagenId): void
    {
        $sala = $this->editingId === null
            ? null
            : Sala::query()->findOrFail($this->editingId);

        if ($sala === null) {
            abort(404);
        }

        Gate::authorize('update', $sala);

        $imagen = $sala->imagenes()->findOrFail($imagenId);

        Storage::disk('public')->delete($imagen->ruta);
        $imagen->delete();
        unset($this->imagenesExistentes);
    }

    public function removeNewImage(int $index): void
    {
        unset($this->imagenes[$index]);
        $this->imagenes = array_values($this->imagenes);
        $this->resetValidation('imagenes.'.$index);
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
        $this->reset(['editingId', 'nombre', 'descripcion', 'ubicacion', 'capacidad', 'horaInicioOperativo', 'horaFinOperativo', 'activa', 'imagenes']);
        unset($this->imagenesExistentes);
        $this->capacidad = 1;
        $this->horaInicioOperativo = '08:00';
        $this->horaFinOperativo = '21:00';
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

    <flux:card class="app-table-shell px-4 py-0 sm:px-6">
        <flux:table :paginate="$this->salas">
            <flux:table.columns>
                <flux:table.column>Espacio</flux:table.column>
                <flux:table.column>Ubicación</flux:table.column>
                <flux:table.column>Capacidad</flux:table.column>
                <flux:table.column>Horario operativo</flux:table.column>
                <flux:table.column>Reservas</flux:table.column>
                <flux:table.column>Disponibilidad</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->salas as $sala)
                    <flux:table.row :key="$sala->id">
                        <flux:table.cell variant="strong">{{ $sala->nombre }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->ubicacion ?: 'Sin especificar' }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->capacidad }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->horarioOperativo() }}</flux:table.cell>
                        <flux:table.cell>{{ $sala->reservas_count }}</flux:table.cell>
                        <flux:table.cell>
                            @php($disponibilidad = $sala->disponibilidadActual())
                            <flux:badge :color="$disponibilidad->color()" size="sm">{{ $disponibilidad->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$sala->activa"
                                :aria-label="$sala->activa ? 'Deshabilitar '.$sala->nombre : 'Habilitar '.$sala->nombre"
                                class="data-checked:!bg-emerald-600"
                                wire:click="toggleActive({{ $sala->id }})"
                            />
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $sala->id }})">Editar</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="py-8 text-center">No hay espacios registrados.</flux:table.cell>
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
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="horaInicioOperativo" type="time" label="Hora de inicio" required />
                <flux:input wire:model="horaFinOperativo" type="time" label="Hora de finalización" required />
            </div>
            <flux:textarea wire:model="descripcion" label="Descripción" rows="3" />
            <flux:switch wire:model="activa" label="Espacio activo" />

            <flux:field>
                <flux:label>Imágenes</flux:label>
                <label
                    for="room-images"
                    class="flex min-h-32 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-campus-300 bg-campus-50 px-4 py-6 text-center text-campus-800 transition hover:border-campus-500 hover:bg-campus-100 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-campus-600 dark:border-campus-700 dark:bg-campus-900/40 dark:text-campus-100 dark:hover:border-campus-500 dark:hover:bg-campus-900/70"
                >
                    <flux:icon.photo class="size-8" />
                    <span class="font-semibold">Seleccionar imágenes</span>
                    <span class="text-sm text-campus-700 dark:text-campus-300">Elegí una o varias imágenes para este espacio</span>
                    <input
                        id="room-images"
                        type="file"
                        wire:model="imagenes"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="sr-only"
                    />
                </label>
                <flux:description>Podés subir hasta 10 imágenes JPG, PNG o WebP de 5 MB cada una.</flux:description>
                <flux:error name="imagenes" />
                <flux:error name="imagenes.*" />
            </flux:field>

            <div wire:loading wire:target="imagenes" class="text-sm text-zinc-500 dark:text-zinc-400">Cargando imágenes…</div>

            @if ($imagenes !== [])
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($imagenes as $index => $imagen)
                        @if ($imagen->isPreviewable())
                            <div wire:key="new-room-image-{{ $imagen->getFilename() }}" class="relative">
                                <img src="{{ $imagen->temporaryUrl() }}" alt="Vista previa de imagen nueva" class="aspect-square w-full rounded-lg object-cover" />
                                <flux:button type="button" size="sm" variant="danger" class="absolute right-2 top-2" wire:click="removeNewImage({{ $index }})">Quitar</flux:button>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($this->imagenesExistentes->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($this->imagenesExistentes as $imagen)
                        <div wire:key="room-image-{{ $imagen->id }}" class="relative">
                            <img src="{{ Storage::disk('public')->url($imagen->ruta) }}" alt="Imagen de {{ $nombre }}" class="aspect-square w-full rounded-lg object-cover" />
                            <flux:button type="button" size="sm" variant="danger" class="absolute right-2 top-2" wire:click="deleteImage({{ $imagen->id }})">Eliminar</flux:button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
