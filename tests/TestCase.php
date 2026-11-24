<?php

namespace Tests;

use App\Services\JwtService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tokenDe(string $rol): array
    {
        $token = app(JwtService::class)->emitir('prueba', $rol)['token'];

        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }

    protected function assertErrorEstandar($respuesta, int $status, string $codigo): void
    {
        $respuesta->assertStatus($status)
            ->assertJsonStructure(['codigo', 'mensaje', 'detalles', 'timestamp', 'ruta'])
            ->assertJsonPath('codigo', $codigo);
    }
}
