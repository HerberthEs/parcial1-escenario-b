<?php

namespace App\Repositories;

use App\Domain\EstadoReno;
use App\Models\Reno;
use Illuminate\Support\Collection;

/** Capa de datos: único lugar que habla con la tabla renos. */
class RenoRepository
{
    public function listar(?string $estado = null): Collection
    {
        return Reno::query()
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderBy('id')
            ->get();
    }

    public function buscar(int $id): ?Reno
    {
        return Reno::find($id);
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        return Reno::query()
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->exists();
    }

    public function contarPorEstado(EstadoReno $estado): int
    {
        return Reno::where('estado', $estado->value)->count();
    }

    public function crear(array $datos): Reno
    {
        return Reno::create($datos);
    }

    public function actualizar(Reno $reno, array $datos): Reno
    {
        $reno->fill($datos)->save();

        return $reno->refresh();
    }

    public function eliminar(Reno $reno): void
    {
        $reno->delete();
    }
}
