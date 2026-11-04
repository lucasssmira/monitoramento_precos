<?php

namespace App\Services\Scrapers;

use App\Contracts\ProductScraperInterface;
use App\Services\Products\SupportedDomainValidator;
use InvalidArgumentException;

class ScraperFactory
{
    public function __construct(
        private readonly SupportedDomainValidator $domainValidator = new SupportedDomainValidator,
        private readonly JsonLdParser $jsonLdParser = new JsonLdParser,
    ) {}

    /**
     * Resolve the appropriate scraper adapter for a canonical domain or full URL.
     */
    public function make(string $domainOrUrl): ProductScraperInterface
    {
        $canonicalDomain = str_contains($domainOrUrl, '://')
            ? $this->domainValidator->resolveCanonicalDomain($domainOrUrl)
            : strtolower(trim($domainOrUrl));

        return match ($canonicalDomain) {
            'amazon.com.br' => new AmazonScraper($this->jsonLdParser),
            'kabum.com.br' => new KabumScraper($this->jsonLdParser),
            'mercadolivre.com.br' => new MercadoLivreScraper($this->jsonLdParser),
            'magazineluiza.com.br', 'magazinevoce.com.br' => new MagaluScraper($this->jsonLdParser),
            'pichau.com.br' => new PichauScraper($this->jsonLdParser),
            default => throw new InvalidArgumentException('O domínio informado ainda não é suportado pelo sistema.'),
        };
    }
}
