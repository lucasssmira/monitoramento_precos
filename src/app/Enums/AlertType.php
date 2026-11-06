<?php

namespace App\Enums;

enum AlertType: string
{
    case PriceDrop = 'price_drop';
    case BackInStock = 'back_in_stock';

    public function label(): string
    {
        return match ($this) {
            self::PriceDrop => 'Queda de Preço',
            self::BackInStock => 'Retorno ao Estoque',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
