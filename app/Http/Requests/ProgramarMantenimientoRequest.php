<?php

namespace App\Http\Requests;

use App\Domain\TipoMantenimiento;
use App\Domain\Zona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación de formato (400). El clima (regla 4) se decide en MantenimientoService. */
class ProgramarMantenimientoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'zona' => ['required', 'string', Rule::in(Zona::valores())],
            'tipo' => ['required', 'string', Rule::in(TipoMantenimiento::valores())],
            'fechaProgramada' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'responsable' => ['required', 'string', 'min:2', 'max:80'],
            'estado' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'fechaProgramada.after_or_equal' => 'fechaProgramada no puede ser una fecha pasada',
            'estado.prohibited' => 'El estado lo asigna el sistema (PROGRAMADO o PENDIENTE_CLIMA)',
        ];
    }
}
