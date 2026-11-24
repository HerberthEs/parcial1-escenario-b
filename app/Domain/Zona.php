<?php

namespace App\Domain;

enum Zona: string
{
    case PISTA_DESPEGUE = 'PISTA_DESPEGUE';
    case ESTABLO = 'ESTABLO';
    case BODEGA_REGALOS = 'BODEGA_REGALOS';
    case HANGAR_TRINEO = 'HANGAR_TRINEO';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Zonas exteriores: requieren consultar el clima antes de programar trabajos. */
    public function esExterior(): bool
    {
        return $this === self::PISTA_DESPEGUE;
    }
}
