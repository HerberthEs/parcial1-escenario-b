<?php

namespace App\Console\Commands;

use App\Services\MantenimientoService;
use Illuminate\Console\Command;

class ConfirmarClimaPendiente extends Command
{
    protected $signature = 'mantenimientos:confirmar-clima';

    protected $description = 'Vuelve a consultar el clima de los mantenimientos PENDIENTE_CLIMA';

    public function handle(MantenimientoService $servicio): int
    {
        $resultados = $servicio->confirmarPendientesDeClima();
        if (! $resultados) {
            $this->info('No hay mantenimientos PENDIENTE_CLIMA.');
        }
        foreach ($resultados as $id => $resultado) {
            $this->line("Mantenimiento #{$id}: {$resultado}");
        }

        return self::SUCCESS;
    }
}
