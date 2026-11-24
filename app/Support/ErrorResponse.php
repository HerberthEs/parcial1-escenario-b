<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Única fábrica de respuestas de error: todas usan la misma estructura.
 */
class ErrorResponse
{
    public static function make(Request $request, int $status, string $codigo, string $mensaje, array $detalles = [], array $headers = []): JsonResponse
    {
        return response()->json([
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'detalles' => array_values($detalles),
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'ruta' => '/'.ltrim($request->path(), '/'),
        ], $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
