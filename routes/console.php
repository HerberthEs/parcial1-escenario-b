<?php

use Illuminate\Support\Facades\Schedule;

// "Se confirmará después": los mantenimientos PENDIENTE_CLIMA se reintentan cada 5 minutos
// (requiere `php artisan schedule:work`; también se puede ejecutar a mano).
Schedule::command('mantenimientos:confirmar-clima')->everyFiveMinutes();
