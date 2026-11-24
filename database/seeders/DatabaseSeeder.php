<?php

namespace Database\Seeders;

use App\Models\Reno;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuarios de prueba: las claves se leen de .env (DEMO_*), no están en el código.
        User::create([
            'name' => 'Jefe del Establo',
            'usuario' => 'jefe.establo',
            'password' => env('DEMO_JEFE_CLAVE', 'cambiar'),
            'rol' => 'JEFE_ESTABLO',
        ]);
        User::create([
            'name' => 'Elfo ayudante',
            'usuario' => 'elfo',
            'password' => env('DEMO_ELFO_CLAVE', 'cambiar'),
            'rol' => 'ELFO',
        ]);

        // Datos de ejemplo: 8 renos ya asignados al vuelo (el 9.º y el 10.º se prueban desde Postman).
        $asignados = [
            ['Dasher', 8, 95], ['Dancer', 7, 90], ['Prancer', 9, 88], ['Vixen', 6, 92],
            ['Comet', 10, 85], ['Cupid', 5, 91], ['Donner', 11, 87], ['Blitzen', 9, 93],
        ];
        foreach ($asignados as [$nombre, $edad, $energia]) {
            Reno::create(['nombre' => $nombre, 'edad' => $edad, 'nivel_energia' => $energia, 'estado' => 'ASIGNADO_VUELO']);
        }
        Reno::create(['nombre' => 'Copito', 'edad' => 3, 'nivel_energia' => 60, 'estado' => 'EN_DESCANSO']);
    }
}
