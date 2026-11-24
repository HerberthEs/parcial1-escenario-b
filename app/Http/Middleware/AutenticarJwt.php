<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de seguridad.
 *  - Sin header Authorization: Bearer <token>  -> 401 TOKEN_AUSENTE
 *  - Token mal firmado o expirado              -> 401 TOKEN_INVALIDO / TOKEN_EXPIRADO
 *  - Token válido pero con otro rol            -> 403 ROL_INSUFICIENTE
 * Uso en rutas: ->middleware('rol:JEFE_ESTABLO')
 */
class AutenticarJwt
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            throw ApiException::noAutenticado('TOKEN_AUSENTE', 'Se requiere un token Bearer en el header Authorization');
        }

        $payload = $this->jwt->validar($token);
        $rol = $payload['rol'] ?? null;

        if ($rolesPermitidos && ! in_array($rol, $rolesPermitidos, true)) {
            throw ApiException::prohibido(
                'Tu rol no tiene permiso para esta operación',
                [['rolActual' => $rol, 'rolesPermitidos' => $rolesPermitidos]],
            );
        }

        $request->attributes->set('usuario', $payload['sub'] ?? null);
        $request->attributes->set('rol', $rol);

        return $next($request);
    }
}
