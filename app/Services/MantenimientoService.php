<?php

namespace App\Services;

use App\Adapters\MeteoAdapter;
use App\Domain\EstadoMantenimiento;
use App\Domain\Pronostico;
use App\Domain\Zona;
use App\Exceptions\ApiException;
use App\Exceptions\ServicioExternoNoDisponibleException;
use App\Models\Mantenimiento;
use App\Repositories\MantenimientoRepository;
use Illuminate\Support\Collection;

/**
 * Reglas de negocio de mantenimiento (reglas 3 y 4 del escenario B) y manejo
 * del fallo del servicio meteorológico.
 */
class MantenimientoService
{
    public function __construct(
        private readonly MantenimientoRepository $mantenimientos,
        private readonly MeteoAdapter $meteo,
    ) {}

    public function listar(?string $zona): Collection
    {
        return $this->mantenimientos->listar($zona);
    }

    public function obtener(int $id): Mantenimiento
    {
        return $this->mantenimientos->buscar($id) ?? throw ApiException::noEncontrado('Mantenimiento', $id);
    }

    /**
     * Regla 4: en zona exterior (PISTA_DESPEGUE) se consulta el clima.
     *  - VENTISCA                 -> 422, no se guarda.
     *  - Fault o timeout (3 s)    -> se guarda como PENDIENTE_CLIMA y el controlador responde 202.
     *  - DESPEJADO / NEVADA_LEVE  -> se guarda como PROGRAMADO (201).
     */
    public function programar(array $datos): ResultadoProgramacion
    {
        $zona = Zona::from($datos['zona']);
        $registro = [
            'zona' => $zona->value,
            'tipo' => $datos['tipo'],
            'fecha_programada' => $datos['fechaProgramada'],
            'responsable' => $datos['responsable'],
            'estado' => EstadoMantenimiento::PROGRAMADO->value,
        ];

        if (! $zona->esExterior()) {
            return new ResultadoProgramacion($this->mantenimientos->crear($registro));
        }

        try {
            $pronostico = $this->meteo->obtenerPronostico($zona, $datos['fechaProgramada']);
        } catch (ServicioExternoNoDisponibleException $e) {
            $registro['estado'] = EstadoMantenimiento::PENDIENTE_CLIMA->value;

            return new ResultadoProgramacion($this->mantenimientos->crear($registro), true, $e->motivo);
        }

        if ($pronostico === Pronostico::VENTISCA) {
            throw ApiException::reglaNegocio(
                'CLIMA_ADVERSO',
                "No se puede programar trabajo en {$zona->value} el {$datos['fechaProgramada']}: el pronóstico es VENTISCA",
                [['servicio' => MeteoAdapter::SERVICIO, 'pronostico' => $pronostico->value]],
            );
        }

        $registro['pronostico'] = $pronostico->value;

        return new ResultadoProgramacion($this->mantenimientos->crear($registro));
    }

    /** Regla 3: PROGRAMADO -> EN_CURSO | CANCELADO, EN_CURSO -> COMPLETADO; cualquier otra -> 409. */
    public function cambiarEstado(int $id, string $estado): Mantenimiento
    {
        $mantenimiento = $this->obtener($id);
        $destino = EstadoMantenimiento::from($estado);

        if (! $mantenimiento->estado->puedePasarA($destino)) {
            throw ApiException::conflicto(
                'TRANSICION_INVALIDA',
                "No se puede pasar de {$mantenimiento->estado->value} a {$destino->value}",
                [[
                    'estadoActual' => $mantenimiento->estado->value,
                    'estadoSolicitado' => $destino->value,
                    'permitidas' => array_map(fn ($e) => $e->value, $mantenimiento->estado->transicionesPermitidas()),
                ]],
            );
        }

        return $this->mantenimientos->actualizar($mantenimiento, ['estado' => $destino->value]);
    }

    /**
     * "Se confirmará después": reintenta el clima de los mantenimientos PENDIENTE_CLIMA.
     * Lo ejecuta el comando `php artisan mantenimientos:confirmar-clima` (programado cada 5 min).
     * Devuelve [id => resultado].
     */
    public function confirmarPendientesDeClima(): array
    {
        $resultados = [];
        foreach ($this->mantenimientos->listarPorEstado(EstadoMantenimiento::PENDIENTE_CLIMA) as $m) {
            try {
                $pronostico = $this->meteo->obtenerPronostico($m->zona, $m->fecha_programada->format('Y-m-d'));
            } catch (ServicioExternoNoDisponibleException $e) {
                $resultados[$m->id] = 'sigue PENDIENTE_CLIMA ('.$e->motivo.')';

                continue;
            }

            $nuevo = $pronostico === Pronostico::VENTISCA ? EstadoMantenimiento::CANCELADO : EstadoMantenimiento::PROGRAMADO;
            $this->mantenimientos->actualizar($m, ['estado' => $nuevo->value, 'pronostico' => $pronostico->value]);
            $resultados[$m->id] = "{$nuevo->value} (pronóstico {$pronostico->value})";
        }

        return $resultados;
    }
}
