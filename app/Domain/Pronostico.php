<?php

namespace App\Domain;

enum Pronostico: string
{
    case DESPEJADO = 'DESPEJADO';
    case NEVADA_LEVE = 'NEVADA_LEVE';
    case VENTISCA = 'VENTISCA';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
