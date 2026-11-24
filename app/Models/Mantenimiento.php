<?php

namespace App\Models;

use App\Domain\EstadoMantenimiento;
use App\Domain\Pronostico;
use App\Domain\TipoMantenimiento;
use App\Domain\Zona;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $fillable = ['zona', 'tipo', 'fecha_programada', 'responsable', 'estado', 'pronostico'];

    protected function casts(): array
    {
        return [
            'zona' => Zona::class,
            'tipo' => TipoMantenimiento::class,
            'fecha_programada' => 'date:Y-m-d',
            'estado' => EstadoMantenimiento::class,
            'pronostico' => Pronostico::class,
        ];
    }
}
