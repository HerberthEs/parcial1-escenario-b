<?php

namespace App\Http\Requests;

use App\Domain\Zona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarMantenimientosRequest extends FormRequest
{
    public function rules(): array
    {
        return ['zona' => ['sometimes', 'string', Rule::in(Zona::valores())]];
    }
}
