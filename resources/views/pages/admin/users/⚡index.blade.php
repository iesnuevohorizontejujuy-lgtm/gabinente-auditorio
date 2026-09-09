<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Imports\DocentesImport;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new #[Title('Gestión de usuarios')] class extends Component
{
    use PasswordValidationRules, ProfileValidationRules, WithFileUploads, WithPagination;

    public string $name = '';
    public string $dni = '';
    public string $email = '';
    public string $rol = UserRole::Profesor->value;
    public string $password = '';
    public string $password_confirmation = '';

    public ?TemporaryUploadedFile $docentesFile = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->orderBy('name')
            ->paginate(10);
    }

    /** @return list<UserRole> */
    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
    }

    public function create(): void
    {
        Gate::authorize('create', User::class);

        $this->resetForm();
        Flux::modal('user-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('create', User::class);

        $validated = $this->validate([
            ...$this->profileRules(),
            'dni' => ['required', 'digits_between:7,10', Rule::unique(User::class)],
            'rol' => ['required', Rule::enum(UserRole::class)],
            'password' => $this->passwordRules(),
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'dni' => $validated['dni'],
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            'password' => $validated['password'],
        ]);

        unset($this->users);
        $this->resetForm();
        Flux::modal('user-form')->close();
        Flux::toast(variant: 'success', text: 'Usuario creado correctamente.');
    }

    public function openImport(): void
    {
        Gate::authorize('create', User::class);

        $this->reset('docentesFile');
        $this->resetValidation();
        Flux::modal('import-teachers')->show();
    }

    public function importTeachers(): void
    {
        Gate::authorize('create', User::class);

        $validated = $this->validate([
            'docentesFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new DocentesImport;

        Excel::import($import, $validated['docentesFile']);

        unset($this->users);
        $this->reset('docentesFile');
        Flux::modal('import-teachers')->close();
        Flux::toast(variant: 'success', text: "{$import->createdCount} docentes creados y {$import->skippedCount} omitidos por duplicados.");
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'dni', 'email', 'rol', 'password', 'password_confirmation']);
        $this->rol = UserRole::Profesor->value;
        $this->resetValidation();
    }
};
?>

<div class="app-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid gap-1">
            <p class="app-eyebrow">Administración institucional</p>
            <flux:heading size="xl" level="1">Usuarios</flux:heading>
            <flux:text>Creá cuentas y asigná el rol con el que accederán al sistema.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="arrow-up-tray" wire:click="openImport">Importar docentes</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="create">Nuevo usuario</flux:button>
        </div>
    </div>

    <flux:card class="app-table-shell p-0">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>Usuario</flux:table.column>
                <flux:table.column>DNI</flux:table.column>
                <flux:table.column>Correo electrónico</flux:table.column>
                <flux:table.column>Rol</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->dni }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$user->isAdministrator() ? 'purple' : 'blue'" size="sm">
                                {{ $user->rol->name }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$user->activo ? 'green' : 'zinc'" size="sm">
                                {{ $user->activo ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-8 text-center">No hay usuarios registrados.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="user-form" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo usuario</flux:heading>
                <flux:text class="mt-2">La cuenta quedará activa y podrá iniciar sesión de inmediato.</flux:text>
            </div>

            <flux:input wire:model="name" label="Nombre completo" required />

            <flux:input wire:model="dni" inputmode="numeric" label="DNI" required />

            <flux:input wire:model="email" type="email" label="Correo electrónico" required />

            <flux:select wire:model="rol" label="Rol" required>
                @foreach ($this->roles as $role)
                    <flux:select.option :value="$role->value" wire:key="user-role-{{ $role->value }}">
                        {{ $role->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="password" type="password" label="Contraseña" required viewable />
                <flux:input wire:model="password_confirmation" type="password" label="Confirmar contraseña" required viewable />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">Crear usuario</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="import-teachers" class="md:w-[32rem]">
        <form wire:submit="importTeachers" class="space-y-6">
            <div>
                <flux:heading size="lg">Importar docentes</flux:heading>
                <flux:text class="mt-2">Subí un archivo Excel o CSV con las columnas: APELLIDO Y NOMBRE, DNI y CORREO.</flux:text>
            </div>

            <flux:input wire:model="docentesFile" type="file" accept=".xlsx,.xls,.csv" label="Archivo de docentes" required />
            <flux:error name="docentesFile" />

            <flux:callout variant="warning" icon="information-circle">
                La contraseña inicial de cada docente será su DNI. Las cuentas existentes no se modifican.
            </flux:callout>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="docentesFile,importTeachers">Importar archivo</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
