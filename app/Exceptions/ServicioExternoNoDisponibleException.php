<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción de dominio que lanza un adaptador SOAP cuando el servicio externo
 * responde con un Fault o no responde a tiempo. No contiene XML: solo el motivo.
 */
class ServicioExternoNoDisponibleException extends RuntimeException
{
    public function __construct(
        public readonly string $servicio,
        public readonly string $motivo,
        public readonly int $intentos,
    ) {
        parent::__construct("{$servicio} no disponible: {$motivo}");
    }
}
