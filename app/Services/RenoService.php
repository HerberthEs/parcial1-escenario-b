<?php

namespace App\Services;

use App\Domain\EstadoReno;
use App\Exceptions\ApiException;
use App\Models\Reno;
use App\Repositories\RenoRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio de los renos (reglas 1, 2 y 5 del escenario B).
 */
class RenoService
{
    public const ENERGIA_MINIMA_VUELO = 70;

    public const MAXIMO_RENOS_VUELO = 9;

    public function __construct(private readonly RenoRepository $renos) {}

    public function listar(?string $estado): Collection
    {
        return $this->renos->listar($estado);
    }

    public function obtener(int $id): Reno
    {
        return $this->renos->buscar($id) ?? throw ApiException::noEncontrado('Reno', $id);
    }

    public function registrar(array $datos): Reno
    {
        $this->asegurarNombreUnico($datos['nombre']);

        return $this->renos->crear([
            'nombre' => $datos['nombre'],
            'edad' => $datos['edad'],
            'nivel_energia' => $datos['nivelEnergia'],
            'estado' => $datos['estado'] ?? EstadoReno::DISPONIBLE->value,
        ]);
    }

    public function actualizar(int $id, array $datos): Reno
    {
        $reno = $this->obtener($id);
        $this->asegurarNombreUnico($datos['nombre'], $reno->id);

        $nuevoEstado = isset($datos['estado']) ? EstadoReno::from($datos['estado']) : $reno->estado;

        // Un reno asignado al vuelo debe conservar la energía mínima mientras siga asignado.
        if ($nuevoEstado === EstadoReno::ASIGNADO_VUELO && $datos['nivelEnergia'] < self::ENERGIA_MINIMA_VUELO) {
            throw ApiException::conflicto(
                'ENERGIA_INSUFICIENTE',
                'Un reno ASIGNADO_VUELO no puede quedar con energía menor a '.self::ENERGIA_MINIMA_VUELO.'; cambia primero su estado',
            );
        }

        return $this->renos->actualizar($reno, [
            'nombre' => $datos['nombre'],
            'edad' => $datos['edad'],
            'nivel_energia' => $datos['nivelEnergia'],
            'estado' => $nuevoEstado->value,
        ]);
    }

    /** Regla 5: no se puede eliminar un reno ASIGNADO_VUELO. */
    public function eliminar(int $id): void
    {
        $reno = $this->obtener($id);
        if ($reno->estado === EstadoReno::ASIGNADO_VUELO) {
            throw ApiException::conflicto('RENO_ASIGNADO_VUELO', "No se puede eliminar a {$reno->nombre}: está ASIGNADO_VUELO");
        }
        $this->renos->eliminar($reno);
    }

    /**
     * Reglas 1 y 2: solo un reno DISPONIBLE con energía >= 70 puede pasar a ASIGNADO_VUELO,
     * y como máximo 9 renos pueden estar asignados.
     */
    public function asignarAlVuelo(int $id): Reno
    {
        return DB::transaction(function () use ($id) {
            $reno = $this->obtener($id);

            if ($reno->estado !== EstadoReno::DISPONIBLE) {
                throw ApiException::conflicto(
                    'RENO_NO_DISPONIBLE',
                    "{$reno->nombre} está en estado {$reno->estado->value}; solo un reno DISPONIBLE puede asignarse al vuelo",
                );
            }

            if ($reno->nivel_energia < self::ENERGIA_MINIMA_VUELO) {
                throw ApiException::conflicto(
                    'ENERGIA_INSUFICIENTE',
                    "{$reno->nombre} tiene energía {$reno->nivel_energia}; se requiere al menos ".self::ENERGIA_MINIMA_VUELO,
                );
            }

            $asignados = $this->renos->contarPorEstado(EstadoReno::ASIGNADO_VUELO);
            if ($asignados >= self::MAXIMO_RENOS_VUELO) {
                throw ApiException::conflicto(
                    'CUPO_VUELO_COMPLETO',
                    'El vuelo ya tiene '.self::MAXIMO_RENOS_VUELO.' renos asignados',
                    [['asignados' => $asignados, 'maximo' => self::MAXIMO_RENOS_VUELO]],
                );
            }

            return $this->renos->actualizar($reno, ['estado' => EstadoReno::ASIGNADO_VUELO->value]);
        });
    }

    private function asegurarNombreUnico(string $nombre, ?int $excluirId = null): void
    {
        if ($this->renos->existeNombre($nombre, $excluirId)) {
            throw ApiException::conflicto('NOMBRE_DUPLICADO', "Ya existe un reno llamado {$nombre}");
        }
    }
}
