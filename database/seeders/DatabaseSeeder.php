<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@iesnh.edu.ar'],
            [
                'name' => 'Administrador IESNH',
                'dni' => '11111111',
                'rol' => UserRole::Administrador,
                'activo' => true,
                'password' => 'Reservas123!',
            ],
        );

        foreach ([
            ['name' => 'Laura Gómez', 'dni' => '22222222', 'email' => 'laura@iesnh.edu.ar'],
            ['name' => 'Martín Pérez', 'dni' => '33333333', 'email' => 'martin@iesnh.edu.ar'],
        ] as $profesor) {
            User::query()->updateOrCreate(
                ['email' => $profesor['email']],
                [
                    ...$profesor,
                    'rol' => UserRole::Profesor,
                    'activo' => true,
                    'password' => 'Reservas123!',
                ],
            );
        }

        $this->call([
            SalaSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
