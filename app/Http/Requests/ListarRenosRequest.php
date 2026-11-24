<?php

namespace App\Http\Requests;

use App\Domain\EstadoReno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarRenosRequest extends FormRequest
{
    public function rules(): array
    {
        return ['estado' => ['sometimes', 'string', Rule::in(EstadoReno::valores())]];
    }
}
