<?php

namespace App\Services\Products;

class SupportedDomainValidator
{
    /**
     * Map of canonical domains to their allowed host suffixes (RN-003).
     *
     * @var array<string, list<string>>
     */
    private const HOMOLOGATED_DOMAINS = [
        'amazon.com.br' => ['amazon.com.br'],
        'kabum.com.br' => ['kabum.com.br'],
        'mercadolivre.com.br' => ['mercadolivre.com.br'],
        'magazineluiza.com.br' => ['magazineluiza.com.br', 'magazinevoce.com.br'],
        'pichau.com.br' => ['pichau.com.br'],
    ];

    /**
     * Forbidden local/internal host suffixes (Anti-SSRF - RNF-006).
     *
     * @var list<string>
     */
    private const FORBIDDEN_HOST_SUFFIXES = [
        '.localhost',
        '.local',
        '.internal',
        '.test',
        '.invalid',
        '.example',
    ];

    /**
     * Validate that a URL is safe against SSRF (RNF-006).
     */
    public function isSafeHttpUrl(string $url): bool
    {
        $trimmed = trim($url);

        if ($trimmed === '' || filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($trimmed);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if ($host === '' || $host === 'localhost') {
            return false;
        }

        foreach (self::FORBIDDEN_HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the canonical homologated domain for a URL, or null if unsupported/unsafe (RN-003).
     */
    public function resolveCanonicalDomain(string $url): ?string
    {
        if (! $this->isSafeHttpUrl($url)) {
            return null;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $normalizedHost = strtolower($host);

        foreach (self::HOMOLOGATED_DOMAINS as $canonicalDomain => $allowedDomains) {
            foreach ($allowedDomains as $allowedDomain) {
                if (
                    $normalizedHost === $allowedDomain
                    || str_ends_with($normalizedHost, '.'.$allowedDomain)
                ) {
                    return $canonicalDomain;
                }
            }
        }

        return null;
    }

    /**
     * Check whether the given URL belongs to a homologated e-commerce domain (RN-003).
     */
    public function isSupported(string $url): bool
    {
        return $this->resolveCanonicalDomain($url) !== null;
    }
}
