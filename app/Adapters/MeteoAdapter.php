<?php

namespace App\Adapters;

use App\Domain\Pronostico;
use App\Domain\Zona;
use App\Exceptions\ServicioExternoNoDisponibleException;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adaptador del servicio SOAP heredado MeteoPoloNorteService (operación ObtenerPronostico).
 *
 * Es la ÚNICA clase que conoce XML: arma el sobre SOAP, lo envía y traduce la respuesta
 * a un objeto de dominio (enum Pronostico). Si el servicio responde con soap:Fault,
 * no responde en N segundos o devuelve algo ilegible, lanza ServicioExternoNoDisponibleException.
 * Extra: reintenta con backoff exponencial (máximo 2 intentos) antes de declararlo caído.
 */
class MeteoAdapter
{
    public const SERVICIO = 'MeteoPoloNorteService';

    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';

    private const NS_METEO = 'http://polonorte.beatlech/meteo';

    public function __construct(
        private readonly string $url,
        private readonly int $timeoutSegundos = 3,
        private readonly int $intentos = 2,
        private readonly int $backoffMs = 300,
    ) {}

    public function obtenerPronostico(Zona $zona, string $fecha): Pronostico
    {
        $sobre = $this->construirSobre($zona->value, $fecha);
        $motivo = 'sin respuesta';

        for ($intento = 1; $intento <= $this->intentos; $intento++) {
            try {
                $respuesta = Http::withHeaders(['SOAPAction' => '"ObtenerPronostico"'])
                    ->connectTimeout($this->timeoutSegundos)
                    ->timeout($this->timeoutSegundos)
                    ->withBody($sobre, 'text/xml; charset=utf-8')
                    ->post($this->url);

                return $this->interpretar($respuesta->body(), $respuesta->status());
            } catch (ConnectionException) {
                $motivo = "sin respuesta en {$this->timeoutSegundos} s (timeout)";
            } catch (SoapFaultException $fault) {
                $motivo = $fault->getMessage();
            }

            Log::warning(self::SERVICIO." falló (intento {$intento}/{$this->intentos}): {$motivo}");
            if ($intento < $this->intentos) {
                usleep($this->backoffMs * 1000 * (2 ** ($intento - 1))); // backoff: 300 ms, 600 ms...
            }
        }

        throw new ServicioExternoNoDisponibleException(self::SERVICIO, $motivo, $this->intentos);
    }

    private function construirSobre(string $zona, string $fecha): string
    {
        $zona = htmlspecialchars($zona, ENT_XML1);
        $fecha = htmlspecialchars($fecha, ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:mt="http://polonorte.beatlech/meteo">
  <soapenv:Body>
    <mt:ObtenerPronosticoRequest>
      <mt:zona>{$zona}</mt:zona>
      <mt:fecha>{$fecha}</mt:fecha>
    </mt:ObtenerPronosticoRequest>
  </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    /** XML -> objeto de dominio. */
    private function interpretar(string $xml, int $status): Pronostico
    {
        $dom = new DOMDocument;
        if (trim($xml) === '' || ! @$dom->loadXML($xml, LIBXML_NONET)) {
            throw new SoapFaultException("respuesta no es XML válido (HTTP {$status})");
        }
        $xpath = new DOMXPath($dom);

        $fault = $xpath->query('//*[local-name()="Fault"]')->item(0);
        if ($fault) {
            $faultcode = trim($xpath->evaluate('string(.//*[local-name()="faultcode"])', $fault));
            $faultstring = trim($xpath->evaluate('string(.//*[local-name()="faultstring"])', $fault));

            throw new SoapFaultException(trim("SOAP Fault {$faultcode}: {$faultstring}"));
        }

        $xpath->registerNamespace('mt', self::NS_METEO);
        $valor = trim($xpath->evaluate('string(//mt:pronostico)'));
        if ($valor === '') {
            $valor = trim($xpath->evaluate('string(//*[local-name()="pronostico"])'));
        }

        return Pronostico::tryFrom($valor)
            ?? throw new SoapFaultException("pronóstico desconocido '{$valor}' (HTTP {$status})");
    }
}
