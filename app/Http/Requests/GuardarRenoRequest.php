<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de FORMATO (400). Las reglas de negocio (nombre único, asignación) viven en RenoService.
 * ASIGNADO_VUELO no se acepta aquí: solo se llega a ese estado por PATCH /asignacion.
 */
class GuardarRenoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'max:60'],
            'edad' => ['required', 'integer', 'between:1,25'],
            'nivelEnergia' => ['required', 'integer', 'between:0,100'],
            'estado' => ['sometimes', 'string', Rule::in(['DISPONIBLE', 'EN_DESCANSO', 'EN_REVISION'])],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.in' => 'estado debe ser DISPONIBLE, EN_DESCANSO o EN_REVISION; para ASIGNADO_VUELO usa PATCH /api/renos/{id}/asignacion',
        ];
    }
}
