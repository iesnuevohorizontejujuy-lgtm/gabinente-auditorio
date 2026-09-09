<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('forbids professors from opening user administration', function () {
    $professor = User::factory()->create();

    $this->actingAs($professor)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('allows an administrator to create a user', function () {
    $administrator = User::factory()->administrator()->create();

    Livewire::actingAs($administrator)
        ->test('pages::admin.users.index')
        ->set('name', 'Sofía Martínez')
        ->set('dni', '35123456')
        ->set('email', 'sofia@iesnh.edu.ar')
        ->set('rol', UserRole::Profesor->value)
        ->set('password', 'NuevaClave123!')
        ->set('password_confirmation', 'NuevaClave123!')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Sofía Martínez',
        'dni' => '35123456',
        'email' => 'sofia@iesnh.edu.ar',
        'rol' => UserRole::Profesor->value,
        'activo' => true,
    ]);

    $user = User::query()->where('email', 'sofia@iesnh.edu.ar')->firstOrFail();

    expect(Hash::check('NuevaClave123!', $user->password))->toBeTrue();
});

it('rejects a duplicate email address', function () {
    $administrator = User::factory()->administrator()->create();
    User::factory()->create(['email' => 'sofia@iesnh.edu.ar']);

    Livewire::actingAs($administrator)
        ->test('pages::admin.users.index')
        ->set('name', 'Sofía')
        ->set('dni', '35123456')
        ->set('email', 'sofia@iesnh.edu.ar')
        ->set('password', 'NuevaClave123!')
        ->set('password_confirmation', 'NuevaClave123!')
        ->call('save')
        ->assertHasErrors(['email' => ['unique']]);
});

it('imports teachers from a spreadsheet with their dni as the initial password', function () {
    $administrator = User::factory()->administrator()->create();
    User::factory()->create(['email' => 'existente@iesnh.edu.ar', 'dni' => '30111222']);
    $file = UploadedFile::fake()->createWithContent('docentes.csv', implode("\n", [
        'APELLIDO Y NOMBRE,DNI,CORREO',
        'Gómez Laura,35123456,laura.gomez@iesnh.edu.ar',
        'Docente Existente,30111222,existente@iesnh.edu.ar',
    ]));

    Livewire::actingAs($administrator)
        ->test('pages::admin.users.index')
        ->set('docentesFile', $file)
        ->call('importTeachers')
        ->assertHasNoErrors();

    $teacher = User::query()->where('email', 'laura.gomez@iesnh.edu.ar')->firstOrFail();

    expect($teacher)
        ->rol->toBe(UserRole::Profesor)
        ->dni->toBe('35123456')
        ->and(Hash::check('35123456', $teacher->password))->toBeTrue();

    $this->assertDatabaseCount('users', 3);
});
