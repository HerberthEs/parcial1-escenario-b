<?php

namespace App\Http\Requests;

use App\Domain\EstadoMantenimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoMantenimientoRequest extends FormRequest
{
    public function rules(): array
    {
        return ['estado' => ['required', 'string', Rule::in(EstadoMantenimiento::valores())]];
    }
}
