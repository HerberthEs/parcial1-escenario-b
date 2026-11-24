<?php

namespace App\Services;

use App\Domain\EstadoMantenimiento;
use App\Domain\EstadoReno;
use App\Domain\Zona;
use App\Repositories\MantenimientoRepository;
use App\Repositories\RenoRepository;

/** Extra: resumen del vuelo del 24 de diciembre. */
class VueloService
{
    public function __construct(
        private readonly RenoRepository $renos,
        private readonly MantenimientoRepository $mantenimientos,
    ) {}

    public function resumen(): array
    {
        $asignados = $this->renos->listar(EstadoReno::ASIGNADO_VUELO->value);
        $pistaLista = $this->mantenimientos->existeEnZonaConEstado(Zona::PISTA_DESPEGUE, EstadoMantenimiento::COMPLETADO);
        $cupoCompleto = $asignados->count() === RenoService::MAXIMO_RENOS_VUELO;

        return [
            'renosAsignados' => $asignados->map(fn ($r) => [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'nivelEnergia' => $r->nivel_energia,
            ])->values(),
            'totalAsignados' => $asignados->count(),
            'energiaPromedio' => $asignados->isEmpty() ? 0 : round($asignados->avg('nivel_energia'), 2),
            'pistaConMantenimientoCompletado' => $pistaLista,
            'vueloListo' => $cupoCompleto && $pistaLista,
        ];
    }
}
