<?php

namespace App\Enums;

enum SalaDisponibilidad: string
{
    case Disponible = 'disponible';
    case Ocupada = 'ocupada';
    case Cerrada = 'cerrada';
    case FueraDeServicio = 'fuera_de_servicio';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Ocupada => 'Ocupada',
            self::Cerrada => 'Cerrada',
            self::FueraDeServicio => 'Fuera de servicio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Disponible => 'green',
            self::Ocupada => 'amber',
            self::Cerrada => 'zinc',
            self::FueraDeServicio => 'red',
        };
    }
}
