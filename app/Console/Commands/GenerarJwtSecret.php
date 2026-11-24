<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerarJwtSecret extends Command
{
    protected $signature = 'jwt:secret {--show : Solo mostrar el secreto, sin escribir .env}';

    protected $description = 'Genera un JWT_SECRET aleatorio y lo guarda en .env';

    public function handle(): int
    {
        $secreto = bin2hex(random_bytes(32));
        if ($this->option('show')) {
            $this->line($secreto);

            return self::SUCCESS;
        }

        $ruta = base_path('.env');
        if (! file_exists($ruta)) {
            $this->error('No existe .env. Copia primero .env.example a .env');

            return self::FAILURE;
        }

        $contenido = file_get_contents($ruta);
        $contenido = preg_match('/^JWT_SECRET=.*$/m', $contenido)
            ? preg_replace('/^JWT_SECRET=.*$/m', 'JWT_SECRET='.$secreto, $contenido)
            : rtrim($contenido)."\nJWT_SECRET={$secreto}\n";
        file_put_contents($ruta, $contenido);

        $this->info('JWT_SECRET generado en .env');

        return self::SUCCESS;
    }
}
