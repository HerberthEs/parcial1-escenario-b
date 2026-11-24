<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRenoRequest;
use App\Http\Requests\ListarRenosRequest;
use App\Http\Resources\RenoResource;
use App\Services\RenoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Controlador delgado: recibe la petición validada, delega en RenoService y arma la respuesta HTTP. */
class RenoController extends Controller
{
    public function __construct(private readonly RenoService $servicio) {}

    public function index(ListarRenosRequest $request): AnonymousResourceCollection
    {
        return RenoResource::collection($this->servicio->listar($request->validated('estado')));
    }

    public function store(GuardarRenoRequest $request): JsonResponse
    {
        $reno = $this->servicio->registrar($request->validated());

        return (new RenoResource($reno))->response()->setStatusCode(201)
            ->header('Location', url("/api/renos/{$reno->id}"));
    }

    public function show(int $id): RenoResource
    {
        return new RenoResource($this->servicio->obtener($id));
    }

    public function update(GuardarRenoRequest $request, int $id): RenoResource
    {
        return new RenoResource($this->servicio->actualizar($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->servicio->eliminar($id);

        return response()->noContent();
    }

    public function asignar(int $id): RenoResource
    {
        return new RenoResource($this->servicio->asignarAlVuelo($id));
    }
}
