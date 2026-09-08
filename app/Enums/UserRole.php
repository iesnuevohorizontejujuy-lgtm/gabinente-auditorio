<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Profesor = 'profesor';
}
