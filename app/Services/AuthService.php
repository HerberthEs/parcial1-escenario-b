<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly JwtService $jwt,
    ) {}

    public function login(string $usuario, string $clave): array
    {
        $user = $this->usuarios->buscarPorUsuario($usuario);
        if (! $user || ! Hash::check($clave, $user->password)) {
            throw ApiException::noAutenticado('CREDENCIALES_INVALIDAS', 'Usuario o clave incorrectos');
        }

        $emitido = $this->jwt->emitir($user->usuario, $user->rol);

        return [
            'token' => $emitido['token'],
            'tipo' => 'Bearer',
            'rol' => $user->rol,
            'expiraEn' => date(DATE_ATOM, $emitido['expiraEn']),
        ];
    }
}
