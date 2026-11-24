<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MantenimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zona' => $this->zona->value,
            'tipo' => $this->tipo->value,
            'fechaProgramada' => $this->fecha_programada->format('Y-m-d'),
            'responsable' => $this->responsable,
            'estado' => $this->estado->value,
            'pronostico' => $this->pronostico?->value,
        ];
    }
}
