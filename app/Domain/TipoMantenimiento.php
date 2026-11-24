<?php

namespace App\Domain;

enum TipoMantenimiento: string
{
    case LIMPIEZA_NIEVE = 'LIMPIEZA_NIEVE';
    case REPARACION = 'REPARACION';
    case INSPECCION = 'INSPECCION';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
