<?php

namespace App\Domain;

enum EstadoReno: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case EN_DESCANSO = 'EN_DESCANSO';
    case EN_REVISION = 'EN_REVISION';
    case ASIGNADO_VUELO = 'ASIGNADO_VUELO';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
