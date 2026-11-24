<?php

namespace App\Repositories;

use App\Domain\EstadoMantenimiento;
use App\Domain\Zona;
use App\Models\Mantenimiento;
use Illuminate\Support\Collection;

/** Capa de datos: único lugar que habla con la tabla mantenimientos. */
class MantenimientoRepository
{
    public function listar(?string $zona = null): Collection
    {
        return Mantenimiento::query()
            ->when($zona, fn ($q) => $q->where('zona', $zona))
            ->orderBy('fecha_programada')
            ->orderBy('id')
            ->get();
    }

    public function buscar(int $id): ?Mantenimiento
    {
        return Mantenimiento::find($id);
    }

    public function listarPorEstado(EstadoMantenimiento $estado): Collection
    {
        return Mantenimiento::where('estado', $estado->value)->orderBy('id')->get();
    }

    public function existeEnZonaConEstado(Zona $zona, EstadoMantenimiento $estado): bool
    {
        return Mantenimiento::where('zona', $zona->value)->where('estado', $estado->value)->exists();
    }

    public function crear(array $datos): Mantenimiento
    {
        return Mantenimiento::create($datos);
    }

    public function actualizar(Mantenimiento $mantenimiento, array $datos): Mantenimiento
    {
        $mantenimiento->fill($datos)->save();

        return $mantenimiento->refresh();
    }
}
