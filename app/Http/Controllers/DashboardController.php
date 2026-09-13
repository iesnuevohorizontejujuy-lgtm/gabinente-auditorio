<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SPACE_COLORS = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706'];

    public function __invoke(): View
    {
        $spaces = Sala::query()
            ->activas()
            ->select(['id', 'nombre', 'descripcion', 'capacidad', 'hora_inicio_operativo', 'hora_fin_operativo'])
            ->with('imagenes:id,sala_id,ruta,orden')
            ->orderBy('nombre')
            ->get()
            ->values()
            ->map(function (Sala $sala, int $index): array {
                $primaryImage = $sala->imagenes->first();

                return [
                    'id' => $sala->id,
                    'title' => $sala->nombre,
                    'description' => $sala->descripcion ?: 'Espacio institucional disponible para reservas.',
                    'capacity' => $sala->capacidad,
                    'operatingHours' => $sala->horarioOperativo(),
                    'color' => self::SPACE_COLORS[$index % count(self::SPACE_COLORS)],
                    'imageUrl' => $primaryImage === null
                        ? null
                        : Storage::disk('public')->url($primaryImage->ruta),
                ];
            });

        return view('dashboard', ['spaces' => $spaces]);
    }
}
