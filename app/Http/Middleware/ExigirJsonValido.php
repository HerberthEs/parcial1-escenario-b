<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Si el cuerpo dice ser JSON pero no se puede leer, responde 400 JSON_MAL_FORMADO. */
class ExigirJsonValido
{
    public function handle(Request $request, Closure $next): Response
    {
        $cuerpo = $request->getContent();
        if ($request->isJson() && trim($cuerpo) !== '') {
            json_decode($cuerpo);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException(400, 'JSON_MAL_FORMADO', 'El cuerpo de la solicitud no es JSON válido', [
                    ['error' => json_last_error_msg()],
                ]);
            }
        }

        return $next($request);
    }
}
