<?php

use App\Models\Sala;
use App\Models\SalaImagen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('forbids professors from opening room administration', function () {
    $professor = User::factory()->create();

    $this->actingAs($professor)
        ->get(route('admin.rooms.index'))
        ->assertForbidden();
});

it('shows a prominent image selection control to administrators', function () {
    $administrator = User::factory()->administrator()->create();

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->assertSee('Seleccionar imágenes')
        ->assertSee('Elegí una o varias imágenes para este espacio');
});

it('renders a switch for each room state', function () {
    $administrator = User::factory()->administrator()->create();
    Sala::factory()->create(['activa' => true]);
    Sala::factory()->inactiva()->create();

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->assertSeeHtml('data-flux-switch')
        ->assertSeeHtml('data-checked:!bg-emerald-600')
        ->assertDontSee('Activa')
        ->assertDontSee('Inactiva');
});

it('allows an administrator to create a room with multiple images', function () {
    $administrator = User::factory()->administrator()->create();
    Storage::fake('public');

    $images = [
        UploadedFile::fake()->image('aula-magna-1.jpg'),
        UploadedFile::fake()->image('aula-magna-2.png'),
    ];

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->set('nombre', 'Aula Magna')
        ->set('ubicacion', 'Planta baja')
        ->set('capacidad', 80)
        ->set('horaInicioOperativo', '07:30')
        ->set('horaFinOperativo', '20:30')
        ->set('activa', true)
        ->set('imagenes', $images)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('salas', [
        'nombre' => 'Aula Magna',
        'ubicacion' => 'Planta baja',
        'capacidad' => 80,
        'activa' => true,
    ]);

    $sala = Sala::query()->where('nombre', 'Aula Magna')->firstOrFail();
    $imagenes = $sala->imagenes;

    expect(substr($sala->hora_inicio_operativo, 0, 5))->toBe('07:30');
    expect(substr($sala->hora_fin_operativo, 0, 5))->toBe('20:30');
    expect($imagenes)->toHaveCount(2);

    foreach ($imagenes as $imagen) {
        Storage::disk('public')->assertExists($imagen->ruta);
    }
});

it('rejects an operating schedule that ends before it starts', function () {
    $administrator = User::factory()->administrator()->create();

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->set('nombre', 'Aula Magna')
        ->set('capacidad', 80)
        ->set('horaInicioOperativo', '21:00')
        ->set('horaFinOperativo', '08:00')
        ->call('save')
        ->assertHasErrors(['horaFinOperativo']);

    $this->assertDatabaseMissing('salas', ['nombre' => 'Aula Magna']);
});

it('updates and disables a room without deleting it', function () {
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create(['nombre' => 'Sala anterior', 'activa' => true]);

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->call('edit', $sala->id)
        ->set('nombre', 'Sala renovada')
        ->call('save')
        ->call('toggleActive', $sala->id)
        ->assertHasNoErrors();

    expect($sala->refresh())
        ->nombre->toBe('Sala renovada')
        ->activa->toBeFalse();
});

it('allows an administrator to delete a room image', function () {
    $administrator = User::factory()->administrator()->create();
    $sala = Sala::factory()->create();
    $imagen = SalaImagen::factory()->for($sala)->create(['ruta' => 'salas/'.$sala->id.'/aula.jpg']);
    Storage::fake('public');
    Storage::disk('public')->put($imagen->ruta, 'imagen de prueba');

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->call('edit', $sala->id)
        ->call('deleteImage', $imagen->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('sala_imagenes', ['id' => $imagen->id]);
    Storage::disk('public')->assertMissing($imagen->ruta);
});

it('allows an administrator to remove a new image before saving a room', function () {
    $administrator = User::factory()->administrator()->create();
    $images = [
        UploadedFile::fake()->image('aula-magna-1.jpg'),
        UploadedFile::fake()->image('aula-magna-2.jpg'),
    ];

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->set('imagenes', $images)
        ->call('removeNewImage', 0)
        ->assertSet('imagenes', fn (array $imagenes): bool => count($imagenes) === 1);
});

it('rejects files that are not images', function () {
    $administrator = User::factory()->administrator()->create();
    $archivoInvalido = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

    Livewire::actingAs($administrator)
        ->test('pages::admin.rooms.index')
        ->set('nombre', 'Aula Magna')
        ->set('capacidad', 80)
        ->set('imagenes', [$archivoInvalido])
        ->call('save')
        ->assertHasErrors(['imagenes.0']);

    $this->assertDatabaseMissing('salas', ['nombre' => 'Aula Magna']);
});
