<?php
/**
 * Mock local de MeteoPoloNorteService (SOAP 1.1) para desarrollo y demo.
 * Levantar:  php -S 127.0.0.1:8089 mock-soap/server.php
 * WSDL:      http://127.0.0.1:8089/meteo?wsdl
 *
 * Comportamiento (según fecha solicitada):
 *   2026-12-24 -> soap:Fault  faultcode soap:Server  faultstring "Estación sin señal"  (definido por el docente)
 *   2026-12-25 -> tarda 5 s en responder (para probar el timeout de 3 s)
 *   2026-12-21 -> VENTISCA
 *   2026-12-22 -> NEVADA_LEVE
 *   otra fecha -> DESPEJADO
 */

$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($ruta !== '/meteo') {
    http_response_code(404);
    echo 'Usa /meteo';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/xml; charset=utf-8');
    readfile(__DIR__.'/meteo.wsdl');
    return;
}

$xml = file_get_contents('php://input');
$dom = new DOMDocument();
if (! @$dom->loadXML($xml)) {
    responderFault('soap:Client', 'XML mal formado');
    return;
}
$xp = new DOMXPath($dom);
$zona = trim($xp->evaluate('string(//*[local-name()="zona"])'));
$fecha = trim($xp->evaluate('string(//*[local-name()="fecha"])'));
error_log("[mock meteo] ObtenerPronostico zona={$zona} fecha={$fecha}");

if ($fecha === '2026-12-24') {
    responderFault('soap:Server', 'Estación sin señal');
    return;
}
if ($fecha === '2026-12-25') {
    sleep(5);
}

$pronostico = match ($fecha) {
    '2026-12-21' => 'VENTISCA',
    '2026-12-22' => 'NEVADA_LEVE',
    default => 'DESPEJADO',
};

header('Content-Type: text/xml; charset=utf-8');
echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:mt="http://polonorte.beatlech/meteo">
  <soapenv:Body>
    <mt:ObtenerPronosticoResponse>
      <mt:pronostico>{$pronostico}</mt:pronostico>
    </mt:ObtenerPronosticoResponse>
  </soapenv:Body>
</soapenv:Envelope>
XML;

function responderFault(string $code, string $mensaje): void
{
    http_response_code(500);
    header('Content-Type: text/xml; charset=utf-8');
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>{$code}</faultcode>
      <faultstring>{$mensaje}</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML;
}
