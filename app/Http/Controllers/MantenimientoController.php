<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoMantenimientoRequest;
use App\Http\Requests\ListarMantenimientosRequest;
use App\Http\Requests\ProgramarMantenimientoRequest;
use App\Http\Resources\MantenimientoResource;
use App\Services\MantenimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MantenimientoController extends Controller
{
    public function __construct(private readonly MantenimientoService $servicio) {}

    public function index(ListarMantenimientosRequest $request): AnonymousResourceCollection
    {
        return MantenimientoResource::collection($this->servicio->listar($request->validated('zona')));
    }

    public function show(int $id): MantenimientoResource
    {
        return new MantenimientoResource($this->servicio->obtener($id));
    }

    /** 201 si quedó PROGRAMADO; 202 Accepted si el servicio de clima falló y quedó PENDIENTE_CLIMA. */
    public function store(ProgramarMantenimientoRequest $request): JsonResponse
    {
        $resultado = $this->servicio->programar($request->validated());
        $recurso = new MantenimientoResource($resultado->mantenimiento);
        $location = url("/api/mantenimientos/{$resultado->mantenimiento->id}");

        if ($resultado->pendienteClima) {
            return response()->json([
                'mensaje' => 'El servicio meteorológico no respondió; el mantenimiento quedó PENDIENTE_CLIMA y se confirmará después',
                'motivo' => $resultado->motivo,
                'data' => $recurso,
            ], 202, ['Location' => $location], JSON_UNESCAPED_UNICODE);
        }

        return $recurso->response()->setStatusCode(201)->header('Location', $location);
    }

    public function cambiarEstado(CambiarEstadoMantenimientoRequest $request, int $id): MantenimientoResource
    {
        return new MantenimientoResource($this->servicio->cambiarEstado($id, $request->validated('estado')));
    }
}
