<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SPACE_CARDS = [
        'Gabinete de Informática' => [
            'key' => 'gabinete',
            'title' => 'Gabinete',
            'description' => 'Reservá el gabinete para actividades, clases y reuniones de trabajo.',
            'color' => '#2563eb',
        ],
        'Sala Auditorio' => [
            'key' => 'auditorio',
            'title' => 'Auditorio',
            'description' => 'Organizá actos, charlas, presentaciones y encuentros institucionales.',
            'color' => '#7c3aed',
        ],
        'Sala de Streaming' => [
            'key' => 'streaming',
            'title' => 'Sala de Streaming',
            'description' => 'Programá transmisiones, grabaciones y producciones audiovisuales.',
            'color' => '#0891b2',
        ],
    ];

    public function __invoke(): View
    {
        $roomsByName = Sala::query()
            ->activas()
            ->whereIn('nombre', array_keys(self::SPACE_CARDS))
            ->get()
            ->keyBy('nombre');

        $spaces = [];

        foreach (self::SPACE_CARDS as $roomName => $card) {
            $room = $roomsByName->get($roomName);

            if ($room === null) {
                continue;
            }

            $spaces[] = [
                ...$card,
                'id' => $room->id,
                'capacity' => $room->capacidad,
            ];
        }

        return view('dashboard', ['spaces' => $spaces]);
    }
}
