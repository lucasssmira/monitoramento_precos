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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Paused->value => self::Paused->label(),
            self::Pending->value => self::Pending->label(),
            self::Unstable->value => self::Unstable->label(),
        ];
    }
}
