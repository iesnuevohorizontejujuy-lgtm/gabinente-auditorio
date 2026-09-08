<?php

namespace Database\Seeders;

use App\Models\Sala;
use Illuminate\Database\Seeder;

class SalaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salas = [
            [
                'nombre' => 'Sala Auditorio',
                'descripcion' => 'Espacio institucional para actos, conferencias y actividades académicas.',
                'ubicacion' => 'Planta baja',
                'capacidad' => 120,
            ],
            [
                'nombre' => 'Gabinete de Informática',
                'descripcion' => 'Aula equipada para clases y actividades con computadoras.',
                'ubicacion' => 'Primer piso',
                'capacidad' => 30,
            ],
            [
                'nombre' => 'Sala de Streaming',
                'descripcion' => 'Espacio preparado para transmisiones y producción audiovisual.',
                'ubicacion' => 'Primer piso',
                'capacidad' => 12,
            ],
        ];

        foreach ($salas as $sala) {
            Sala::query()->updateOrCreate(
                ['nombre' => $sala['nombre']],
                [...$sala, 'activa' => true],
            );
        }
    }
}
