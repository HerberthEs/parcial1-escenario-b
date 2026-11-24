<?php

namespace App\Repositories;

use App\Models\User;

class UsuarioRepository
{
    public function buscarPorUsuario(string $usuario): ?User
    {
        return User::where('usuario', $usuario)->first();
    }
}
