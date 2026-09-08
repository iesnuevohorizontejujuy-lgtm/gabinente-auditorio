<?php

namespace Database\Seeders;

use App\Enums\ReservaEstado;
use App\Models\Reserva;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profesor = User::query()->where('email', 'laura@iesnh.edu.ar')->first();

        if ($profesor === null) {
            return;
        }

        Sala::query()
            ->orderBy('id')
            ->get()
            ->each(function (Sala $sala, int $index) use ($profesor): void {
                $inicio = Carbon::now()
                    ->next(Carbon::MONDAY)
                    ->addDays($index)
                    ->setTime(10, 0);

                Reserva::query()->firstOrCreate(
                    [
                        'sala_id' => $sala->id,
                        'profesor_id' => $profesor->id,
                        'titulo' => 'Actividad de demostración',
                    ],
                    [
                        'descripcion' => 'Reserva local para comprobar el calendario.',
                        'inicio' => $inicio,
                        'fin' => $inicio->copy()->addHour(),
                        'estado' => $index === 0
                            ? ReservaEstado::Aprobada
                            : ReservaEstado::Pendiente,
                    ],
                );
            });
    }
}
