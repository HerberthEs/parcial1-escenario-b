<?php

namespace App\Providers;

use App\Adapters\MeteoAdapter;
use App\Services\JwtService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => new JwtService(
            config('jwt.secret'),
            config('jwt.ttl_minutos'),
            config('jwt.emisor'),
        ));

        $this->app->bind(MeteoAdapter::class, fn () => new MeteoAdapter(
            config('services.meteo.url'),
            (int) config('services.meteo.timeout'),
            (int) config('services.meteo.intentos'),
            (int) config('services.meteo.backoff_ms'),
        ));
    }

    public function boot(): void {}
}
