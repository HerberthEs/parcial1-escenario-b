<?php

use App\Exceptions\ApiException;
use App\Exceptions\ServicioExternoNoDisponibleException;
use App\Http\Middleware\AutenticarJwt;
use App\Http\Middleware\ExigirJsonValido;
use App\Support\ErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware de seguridad: 'rol:JEFE_ESTABLO' exige token (401) y rol (403).
        $middleware->alias(['rol' => AutenticarJwt::class]);
        $middleware->api(prepend: [ExigirJsonValido::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejador global de errores: todo error de /api sale con el formato estándar.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (ApiException $e, Request $request) {
            return ErrorResponse::make($request, $e->status, $e->codigo, $e->getMessage(), $e->detalles);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            $detalles = [];
            foreach ($e->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $detalles[] = ['campo' => $campo, 'mensaje' => $mensaje];
                }
            }

            return ErrorResponse::make($request, 400, 'ENTRADA_INVALIDA', 'La solicitud tiene datos con formato inválido', $detalles);
        });

        // Red de seguridad: si una excepción del servicio externo escapara de la capa de servicios,
        // nunca debe convertirse en 500.
        $exceptions->render(function (ServicioExternoNoDisponibleException $e, Request $request) {
            return ErrorResponse::make($request, 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE', $e->getMessage(), [
                ['servicio' => $e->servicio, 'motivo' => $e->motivo, 'intentos' => $e->intentos],
            ]);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return ErrorResponse::make($request, 404, 'RECURSO_NO_ENCONTRADO', 'El recurso solicitado no existe');
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ErrorResponse::make($request, 404, 'RUTA_NO_ENCONTRADA', 'La ruta o el recurso solicitado no existe');
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return ErrorResponse::make($request, 405, 'METODO_NO_PERMITIDO', 'Método HTTP no permitido en esta ruta', [], $e->getHeaders());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ErrorResponse::make($request, 401, 'TOKEN_AUSENTE', 'Se requiere un token Bearer en el header Authorization');
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ErrorResponse::make($request, $e->getStatusCode(), 'ERROR_HTTP', $e->getMessage() ?: 'Error HTTP', [], $e->getHeaders());
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            report($e);

            return ErrorResponse::make($request, 500, 'ERROR_INTERNO', 'Error interno inesperado');
        });
    })->create();
