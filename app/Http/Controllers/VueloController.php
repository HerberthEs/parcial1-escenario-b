<?php

namespace App\Http\Controllers;

use App\Services\VueloService;
use Illuminate\Http\JsonResponse;

class VueloController extends Controller
{
    public function __construct(private readonly VueloService $servicio) {}

    public function resumen(): JsonResponse
    {
        return response()->json(['data' => $this->servicio->resumen()]);
    }
}
