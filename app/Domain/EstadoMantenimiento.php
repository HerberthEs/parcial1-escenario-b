<?php

namespace App\Domain;

enum EstadoMantenimiento: string
{
    case PROGRAMADO = 'PROGRAMADO';
    case EN_CURSO = 'EN_CURSO';
    case COMPLETADO = 'COMPLETADO';
    case CANCELADO = 'CANCELADO';
    case PENDIENTE_CLIMA = 'PENDIENTE_CLIMA';

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Regla 3: PROGRAMADO -> EN_CURSO | CANCELADO, EN_CURSO -> COMPLETADO. */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PROGRAMADO => [self::EN_CURSO, self::CANCELADO],
            self::EN_CURSO => [self::COMPLETADO],
            default => [],
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }
}
