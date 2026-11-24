<?php

namespace App\Models;

use App\Domain\EstadoReno;
use Illuminate\Database\Eloquent\Model;

class Reno extends Model
{
    protected $table = 'renos';

    protected $fillable = ['nombre', 'edad', 'nivel_energia', 'estado'];

    protected function casts(): array
    {
        return [
            'edad' => 'integer',
            'nivel_energia' => 'integer',
            'estado' => EstadoReno::class,
        ];
    }
}
