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
                'name' => 'Administrador',
                'apellido' => 'IESNH',
                'rol' => UserRole::Administrador,
                'activo' => true,
                'password' => 'Reservas123!',
            ],
        );

        foreach ([
            ['name' => 'Laura', 'apellido' => 'Gómez', 'email' => 'laura@iesnh.edu.ar'],
            ['name' => 'Martín', 'apellido' => 'Pérez', 'email' => 'martin@iesnh.edu.ar'],
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
