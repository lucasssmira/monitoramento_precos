<?php

namespace App\DataTransferObjects;

final readonly class ScrapedProductData
{
    public function __construct(
        public string $title,
        public ?string $imageUrl,
        public ?float $cashPrice,
        public ?float $installmentPrice,
        public bool $inStock,
        public string $currency = 'BRL',
        public int $httpStatus = 200,
        public int $durationMs = 0,
    ) {}
}
