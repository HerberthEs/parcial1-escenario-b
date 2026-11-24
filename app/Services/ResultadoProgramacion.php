<?php

namespace App\Services;

use App\Models\Mantenimiento;

/** Resultado de programar un mantenimiento: si quedó pendiente de clima, la API responde 202. */
final class ResultadoProgramacion
{
    public function __construct(
        public readonly Mantenimiento $mantenimiento,
        public readonly bool $pendienteClima = false,
        public readonly ?string $motivo = null,
    ) {}
}
