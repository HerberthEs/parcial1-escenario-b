<?php

namespace Tests\Feature;

use App\Models\Reno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenoApiTest extends TestCase
{
    use RefreshDatabase;

    private function jefe(): array
    {
        return $this->tokenDe('JEFE_ESTABLO');
    }

    public function test_registra_y_consulta_un_reno(): void
    {
        $r = $this->postJson('/api/renos', ['nombre' => 'Rudolph', 'edad' => 7, 'nivelEnergia' => 98], $this->jefe());
        $r->assertCreated()->assertJsonPath('data.estado', 'DISPONIBLE');

        $this->getJson('/api/renos/'.$r->json('data.id'))->assertOk()->assertJsonPath('data.nombre', 'Rudolph');
    }

    public function test_seguridad_401_sin_token_y_403_con_rol_distinto(): void
    {
        $this->assertErrorEstandar($this->postJson('/api/renos', []), 401, 'TOKEN_AUSENTE');
        $this->assertErrorEstandar($this->postJson('/api/renos', [], $this->tokenDe('ELFO')), 403, 'ROL_INSUFICIENTE');
    }

    public function test_entrada_invalida_400(): void
    {
        $r = $this->postJson('/api/renos', ['nombre' => 'X', 'edad' => 30, 'nivelEnergia' => 101], $this->jefe());
        $this->assertErrorEstandar($r, 400, 'ENTRADA_INVALIDA');
        $this->assertCount(3, $r->json('detalles'));
    }

    public function test_nombre_duplicado_409(): void
    {
        Reno::create(['nombre' => 'Comet', 'edad' => 5, 'nivel_energia' => 80, 'estado' => 'DISPONIBLE']);
        $r = $this->postJson('/api/renos', ['nombre' => 'comet', 'edad' => 5, 'nivelEnergia' => 80], $this->jefe());
        $this->assertErrorEstandar($r, 409, 'NOMBRE_DUPLICADO');
    }

    public function test_reno_inexistente_404(): void
    {
        $this->assertErrorEstandar($this->getJson('/api/renos/999'), 404, 'RECURSO_NO_ENCONTRADO');
    }

    public function test_filtra_por_estado(): void
    {
        Reno::create(['nombre' => 'A', 'edad' => 5, 'nivel_energia' => 80, 'estado' => 'DISPONIBLE']);
        Reno::create(['nombre' => 'B', 'edad' => 5, 'nivel_energia' => 80, 'estado' => 'EN_REVISION']);
        $this->getJson('/api/renos?estado=EN_REVISION')->assertOk()->assertJsonCount(1, 'data');
        $this->assertErrorEstandar($this->getJson('/api/renos?estado=VOLANDO'), 400, 'ENTRADA_INVALIDA');
    }

    public function test_regla_1_energia_y_estado_para_asignar(): void
    {
        $cansado = Reno::create(['nombre' => 'Cansado', 'edad' => 5, 'nivel_energia' => 69, 'estado' => 'DISPONIBLE']);
        $revision = Reno::create(['nombre' => 'Revision', 'edad' => 5, 'nivel_energia' => 90, 'estado' => 'EN_REVISION']);
        $listo = Reno::create(['nombre' => 'Listo', 'edad' => 5, 'nivel_energia' => 70, 'estado' => 'DISPONIBLE']);

        $this->assertErrorEstandar($this->patchJson("/api/renos/{$cansado->id}/asignacion", [], $this->jefe()), 409, 'ENERGIA_INSUFICIENTE');
        $this->assertErrorEstandar($this->patchJson("/api/renos/{$revision->id}/asignacion", [], $this->jefe()), 409, 'RENO_NO_DISPONIBLE');
        $this->patchJson("/api/renos/{$listo->id}/asignacion", [], $this->jefe())->assertOk()->assertJsonPath('data.estado', 'ASIGNADO_VUELO');
    }

    public function test_regla_2_el_decimo_reno_responde_409(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            Reno::create(['nombre' => "R{$i}", 'edad' => 5, 'nivel_energia' => 90, 'estado' => 'ASIGNADO_VUELO']);
        }
        $decimo = Reno::create(['nombre' => 'Decimo', 'edad' => 5, 'nivel_energia' => 99, 'estado' => 'DISPONIBLE']);

        $this->assertErrorEstandar($this->patchJson("/api/renos/{$decimo->id}/asignacion", [], $this->jefe()), 409, 'CUPO_VUELO_COMPLETO');
    }

    public function test_regla_5_no_elimina_reno_asignado(): void
    {
        $asignado = Reno::create(['nombre' => 'Asignado', 'edad' => 5, 'nivel_energia' => 90, 'estado' => 'ASIGNADO_VUELO']);
        $libre = Reno::create(['nombre' => 'Libre', 'edad' => 5, 'nivel_energia' => 90, 'estado' => 'DISPONIBLE']);

        $this->assertErrorEstandar($this->deleteJson("/api/renos/{$asignado->id}", [], $this->jefe()), 409, 'RENO_ASIGNADO_VUELO');
        $this->deleteJson("/api/renos/{$libre->id}", [], $this->jefe())->assertNoContent();
    }

    public function test_put_no_permite_asignar_por_la_puerta_trasera(): void
    {
        $reno = Reno::create(['nombre' => 'Astuto', 'edad' => 5, 'nivel_energia' => 10, 'estado' => 'DISPONIBLE']);
        $r = $this->putJson("/api/renos/{$reno->id}", ['nombre' => 'Astuto', 'edad' => 5, 'nivelEnergia' => 10, 'estado' => 'ASIGNADO_VUELO'], $this->jefe());
        $this->assertErrorEstandar($r, 400, 'ENTRADA_INVALIDA');
    }
}
