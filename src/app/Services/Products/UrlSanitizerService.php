<?php

namespace App\Services\Products;

class UrlSanitizerService
{
    /**
     * Exact tracking parameter names to strip during URL normalization (RN-004).
     *
     * @var list<string>
     */
    private const TRACKING_PARAMETERS = [
        'ref',
        'tag',
        'fbclid',
        'gclid',
        'msclkid',
        'mc_cid',
        'mc_eid',
        '_ga',
        '_gl',
        'spm',
        'aff_fsk',
        'aff_platform',
        'mkwid',
        'pcrid',
        'pd_rd_r',
        'pd_rd_w',
        'pd_rd_wg',
        'pf_rd_p',
        'pf_rd_r',
        'qid',
        'sr',
    ];

    /**
     * Sanitize and normalize a product URL by removing tracking parameters and fragments.
     */
    public function sanitize(string $url): ?string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return null;
        }

        $parts = parse_url($trimmed);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = (string) preg_replace('/^www\./i', '', $host);

        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        } elseif (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        $cleanQuery = '';

        if (isset($parts['query']) && $parts['query'] !== '') {
            /** @var array<int|string, mixed> $queryParams */
            $queryParams = [];
            parse_str($parts['query'], $queryParams);

            $filteredParams = $this->filterTrackingParameters($queryParams);

            if ($filteredParams !== []) {
                ksort($filteredParams);
                $cleanQuery = '?'.http_build_query($filteredParams, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return sprintf('https://%s%s%s', $host, $path, $cleanQuery);
    }

    /**
     * @param  array<int|string, mixed>  $params
     * @return array<int|string, mixed>
     */
    private function filterTrackingParameters(array $params): array
    {
        $filtered = [];

        foreach ($params as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (str_starts_with($lowerKey, 'utm_') || in_array($lowerKey, self::TRACKING_PARAMETERS, true)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }
}
