<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Paused = 'paused';
    case Unstable = 'unstable';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente / Coletando dados...',
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
            self::Unstable => 'Instável',
        };
    }
}
