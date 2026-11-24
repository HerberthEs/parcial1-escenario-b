<?php

namespace App\Services;

use App\Exceptions\ApiException;
use RuntimeException;

/**
 * Emite y valida JWT firmados con HS256. El secreto viene de la variable de entorno JWT_SECRET.
 */
class JwtService
{
    public function __construct(
        private readonly ?string $secreto,
        private readonly int $ttlMinutos,
        private readonly string $emisor,
    ) {}

    public function emitir(string $sujeto, string $rol): array
    {
        $ahora = time();
        $expira = $ahora + $this->ttlMinutos * 60;
        $payload = ['iss' => $this->emisor, 'sub' => $sujeto, 'rol' => $rol, 'iat' => $ahora, 'exp' => $expira];

        $segmentos = [
            $this->b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])),
            $this->b64(json_encode($payload)),
        ];
        $segmentos[] = $this->b64($this->firmar(implode('.', $segmentos)));

        return ['token' => implode('.', $segmentos), 'expiraEn' => $expira];
    }

    /** Devuelve el payload si el token es válido; si no, lanza 401. */
    public function validar(string $token): array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            throw ApiException::noAutenticado('TOKEN_INVALIDO', 'El token no tiene formato JWT');
        }
        [$cabecera, $cuerpo, $firma] = $partes;

        $esperada = $this->b64($this->firmar("{$cabecera}.{$cuerpo}"));
        if (! hash_equals($esperada, $firma)) {
            throw ApiException::noAutenticado('TOKEN_INVALIDO', 'La firma del token no es válida');
        }

        $header = json_decode($this->desB64($cabecera), true);
        $payload = json_decode($this->desB64($cuerpo), true);
        if (($header['alg'] ?? null) !== 'HS256' || ! is_array($payload)) {
            throw ApiException::noAutenticado('TOKEN_INVALIDO', 'El token no es válido');
        }
        if (($payload['exp'] ?? 0) < time()) {
            throw ApiException::noAutenticado('TOKEN_EXPIRADO', 'El token expiró; vuelve a iniciar sesión');
        }

        return $payload;
    }

    private function firmar(string $datos): string
    {
        if (empty($this->secreto)) {
            throw new RuntimeException('JWT_SECRET no está configurado. Ejecuta: php artisan jwt:secret');
        }

        return hash_hmac('sha256', $datos, $this->secreto, true);
    }

    private function b64(string $datos): string
    {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }

    private function desB64(string $datos): string
    {
        return (string) base64_decode(strtr($datos, '-_', '+/'));
    }
}
