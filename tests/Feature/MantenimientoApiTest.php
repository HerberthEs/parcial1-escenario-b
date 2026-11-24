<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\Reno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MantenimientoApiTest extends TestCase
{
    use RefreshDatabase;

    private function respuestaSoap(string $pronostico): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mt="http://polonorte.beatlech/meteo">'
            ."<soapenv:Body><mt:ObtenerPronosticoResponse><mt:pronostico>{$pronostico}</mt:pronostico></mt:ObtenerPronosticoResponse></soapenv:Body></soapenv:Envelope>";
    }

    private function fault(): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault>'
            .'<faultcode>soap:Server</faultcode><faultstring>Estación sin señal</faultstring></soap:Fault></soap:Body></soap:Envelope>';
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'zona' => 'PISTA_DESPEGUE',
            'tipo' => 'LIMPIEZA_NIEVE',
            'fechaProgramada' => now()->addDays(5)->format('Y-m-d'),
            'responsable' => 'Elfo Pepe',
        ], $cambios);
    }

    public function test_pista_con_clima_despejado_queda_programado_201(): void
    {
        Http::fake(['*' => Http::response($this->respuestaSoap('DESPEJADO'))]);

        $this->postJson('/api/mantenimientos', $this->datos())
            ->assertCreated()
            ->assertJsonPath('data.estado', 'PROGRAMADO')
            ->assertJsonPath('data.pronostico', 'DESPEJADO');

        Http::assertSent(fn ($req) => str_contains($req->body(), '<mt:zona>PISTA_DESPEGUE</mt:zona>'));
    }

    public function test_regla_4_ventisca_responde_422_y_no_guarda(): void
    {
        Http::fake(['*' => Http::response($this->respuestaSoap('VENTISCA'))]);

        $this->assertErrorEstandar($this->postJson('/api/mantenimientos', $this->datos()), 422, 'CLIMA_ADVERSO');
        $this->assertSame(0, Mantenimiento::count());
    }

    public function test_fault_soap_guarda_pendiente_clima_y_responde_202(): void
    {
        Http::fake(['*' => Http::response($this->fault(), 500)]);

        $this->postJson('/api/mantenimientos', $this->datos())
            ->assertStatus(202)
            ->assertJsonPath('data.estado', 'PENDIENTE_CLIMA');

        Http::assertSentCount(2); // reintento con backoff: máximo 2 intentos
    }

    public function test_timeout_guarda_pendiente_clima_y_responde_202(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->postJson('/api/mantenimientos', $this->datos())
            ->assertStatus(202)
            ->assertJsonPath('data.estado', 'PENDIENTE_CLIMA');
    }

    public function test_zona_interior_no_consulta_el_clima(): void
    {
        Http::fake();
        $this->postJson('/api/mantenimientos', $this->datos(['zona' => 'ESTABLO']))->assertCreated();
        Http::assertNothingSent();
    }

    public function test_fecha_pasada_responde_400(): void
    {
        $r = $this->postJson('/api/mantenimientos', $this->datos(['fechaProgramada' => '2020-01-01']));
        $this->assertErrorEstandar($r, 400, 'ENTRADA_INVALIDA');
    }

    public function test_regla_3_transiciones(): void
    {
        $m = Mantenimiento::create($this->registro('PROGRAMADO'));

        $this->assertErrorEstandar($this->patchJson("/api/mantenimientos/{$m->id}/estado", ['estado' => 'COMPLETADO']), 409, 'TRANSICION_INVALIDA');
        $this->patchJson("/api/mantenimientos/{$m->id}/estado", ['estado' => 'EN_CURSO'])->assertOk();
        $this->patchJson("/api/mantenimientos/{$m->id}/estado", ['estado' => 'COMPLETADO'])->assertOk();
        $this->assertErrorEstandar($this->patchJson("/api/mantenimientos/{$m->id}/estado", ['estado' => 'CANCELADO']), 409, 'TRANSICION_INVALIDA');
    }

    public function test_filtra_por_zona(): void
    {
        Mantenimiento::create($this->registro('PROGRAMADO'));
        Mantenimiento::create(array_merge($this->registro('PROGRAMADO'), ['zona' => 'BODEGA_REGALOS']));
        $this->getJson('/api/mantenimientos?zona=BODEGA_REGALOS')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_resumen_de_vuelo(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            Reno::create(['nombre' => "R{$i}", 'edad' => 5, 'nivel_energia' => 80, 'estado' => 'ASIGNADO_VUELO']);
        }
        $this->getJson('/api/vuelo/resumen')->assertOk()->assertJsonPath('data.vueloListo', false);

        Mantenimiento::create($this->registro('COMPLETADO'));
        $this->getJson('/api/vuelo/resumen')->assertOk()
            ->assertJsonPath('data.totalAsignados', 9)
            ->assertJsonPath('data.energiaPromedio', 80)
            ->assertJsonPath('data.vueloListo', true);
    }

    private function registro(string $estado): array
    {
        return [
            'zona' => 'PISTA_DESPEGUE', 'tipo' => 'INSPECCION', 'fecha_programada' => now()->addDay()->format('Y-m-d'),
            'responsable' => 'Elfo', 'estado' => $estado,
        ];
    }
}
