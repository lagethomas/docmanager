<?php
declare(strict_types=1);

namespace App\Core;

interface MiddlewareInterface
{
    /**
     * Retorna false para bloquear/redirecionar a requisição.
     */
    public function handle(): bool;
}