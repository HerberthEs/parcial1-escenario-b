<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio o de acceso que la API traduce al formato estándar.
 * La lanzan los servicios y el middleware; el manejador global la convierte en JSON.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $codigo,
        string $mensaje,
        public readonly array $detalles = [],
    ) {
        parent::__construct($mensaje);
    }

    public static function noEncontrado(string $recurso, int|string $id): self
    {
        return new self(404, 'RECURSO_NO_ENCONTRADO', "{$recurso} con id {$id} no existe");
    }

    public static function conflicto(string $codigo, string $mensaje, array $detalles = []): self
    {
        return new self(409, $codigo, $mensaje, $detalles);
    }

    public static function reglaNegocio(string $codigo, string $mensaje, array $detalles = []): self
    {
        return new self(422, $codigo, $mensaje, $detalles);
    }

    public static function noAutenticado(string $codigo, string $mensaje): self
    {
        return new self(401, $codigo, $mensaje);
    }

    public static function prohibido(string $mensaje, array $detalles = []): self
    {
        return new self(403, 'ROL_INSUFICIENTE', $mensaje, $detalles);
    }
}
