<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Hardware = 'Hardware';
    case Perifericos = 'Periféricos';
    case Smartphones = 'Smartphones';
    case Eletrodomesticos = 'Eletrodomésticos';
    case Games = 'Games';
    case Informatica = 'Informática';
    case AudioVideo = 'Áudio e Vídeo';
    case Outros = 'Outros';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $category): string => $category->value,
            self::cases(),
        );
    }
}
