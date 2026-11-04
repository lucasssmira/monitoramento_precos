<?php

namespace App\Services\Scrapers;

use Symfony\Component\DomCrawler\Crawler;

class JsonLdParser
{
    /**
     * Extract structured product metadata from JSON-LD script tags in the document.
     *
     * @return array{
     *     title: ?string,
     *     imageUrl: ?string,
     *     cashPrice: ?float,
     *     installmentPrice: ?float,
     *     inStock: ?bool,
     *     currency: string
     * }
     */
    public function extract(Crawler $crawler): array
    {
        $result = [
            'title' => null,
            'imageUrl' => null,
            'cashPrice' => null,
            'installmentPrice' => null,
            'inStock' => null,
            'currency' => 'BRL',
        ];

        $scripts = $crawler->filter('script[type="application/ld+json"]');

        foreach ($scripts as $scriptNode) {
            $rawJson = trim($scriptNode->textContent ?? '');

            if ($rawJson === '') {
                continue;
            }

            $decoded = json_decode($rawJson, true);

            if (! is_array($decoded)) {
                continue;
            }

            $productNode = $this->findProductNode($decoded);

            if ($productNode === null) {
                continue;
            }

            if ($result['title'] === null && isset($productNode['name']) && is_string($productNode['name'])) {
                $result['title'] = trim(html_entity_decode($productNode['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            if ($result['imageUrl'] === null && isset($productNode['image'])) {
                $result['imageUrl'] = $this->extractImage($productNode['image']);
            }

            if (isset($productNode['offers']) && is_array($productNode['offers'])) {
                $offerData = $this->extractOffers($productNode['offers']);

                if ($result['cashPrice'] === null && $offerData['cashPrice'] !== null) {
                    $result['cashPrice'] = $offerData['cashPrice'];
                }

                if ($result['installmentPrice'] === null && $offerData['installmentPrice'] !== null) {
                    $result['installmentPrice'] = $offerData['installmentPrice'];
                }

                if ($result['inStock'] === null && $offerData['inStock'] !== null) {
                    $result['inStock'] = $offerData['inStock'];
                }

                if ($offerData['currency'] !== null) {
                    $result['currency'] = $offerData['currency'];
                }
            }
        }

        return $result;
    }

    /**
     * Parse Brazilian ("R$ 1.499,90") or standard ("1499.90") price strings into a float.
     */
    public function parsePrice(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            $val = round((float) $raw, 2);

            return $val > 0 ? $val : null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/(\d{1,3}(?:\.\d{3})+,\d{2}|\d+,\d{2}|\d+\.\d{2}|\d+)/', $trimmed, $matches) !== 1) {
            return null;
        }

        $numberToken = $matches[1];

        if (str_contains($numberToken, ',')) {
            $normalized = str_replace('.', '', $numberToken);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = $numberToken;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        $value = round((float) $normalized, 2);

        return $value > 0 ? $value : null;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>|null
     */
    private function findProductNode(array $data): ?array
    {
        if (isset($data['@type'])) {
            $types = is_array($data['@type']) ? $data['@type'] : [$data['@type']];

            foreach ($types as $type) {
                if (is_string($type) && strcasecmp($type, 'Product') === 0) {
                    /** @var array<string, mixed> $data */
                    return $data;
                }
            }
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (is_array($item)) {
                    $found = $this->findProductNode($item);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        foreach ($data as $item) {
            if (is_array($item)) {
                $found = $this->findProductNode($item);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function extractImage(mixed $image): ?string
    {
        if (is_string($image) && trim($image) !== '') {
            return trim($image);
        }

        if (is_array($image)) {
            if (isset($image['url']) && is_string($image['url'])) {
                return trim($image['url']);
            }

            foreach ($image as $item) {
                $extracted = $this->extractImage($item);
                if ($extracted !== null) {
                    return $extracted;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $offers
     * @return array{cashPrice: ?float, installmentPrice: ?float, inStock: ?bool, currency: ?string}
     */
    private function extractOffers(array $offers): array
    {
        $offerList = isset($offers['@type']) || isset($offers['price']) || isset($offers['lowPrice'])
            ? [$offers]
            : $offers;

        $prices = [];
        $highPrice = null;
        $inStock = null;
        $currency = null;

        foreach ($offerList as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            if (isset($offer['price'])) {
                $parsed = $this->parsePrice($offer['price']);
                if ($parsed !== null) {
                    $prices[] = $parsed;
                }
            }

            if (isset($offer['lowPrice'])) {
                $parsedLow = $this->parsePrice($offer['lowPrice']);
                if ($parsedLow !== null) {
                    $prices[] = $parsedLow;
                }
            }

            if (isset($offer['highPrice'])) {
                $parsedHigh = $this->parsePrice($offer['highPrice']);
                if ($parsedHigh !== null) {
                    $highPrice = $parsedHigh;
                }
            }

            if (isset($offer['priceCurrency']) && is_string($offer['priceCurrency']) && $currency === null) {
                $currency = strtoupper(trim($offer['priceCurrency']));
            }

            if (isset($offer['availability']) && is_string($offer['availability'])) {
                $availability = strtolower($offer['availability']);

                if (
                    str_contains($availability, 'instock')
                    || str_contains($availability, 'onlineonly')
                    || str_contains($availability, 'limitedavailability')
                    || str_contains($availability, 'preorder')
                ) {
                    $inStock = true;
                } elseif (
                    str_contains($availability, 'outofstock')
                    || str_contains($availability, 'soldout')
                    || str_contains($availability, 'discontinued')
                ) {
                    $inStock = $inStock ?? false;
                }
            }
        }

        $cashPrice = $prices !== [] ? min($prices) : null;
        $installmentPrice = null;

        if ($highPrice !== null && $cashPrice !== null && $highPrice > $cashPrice) {
            $installmentPrice = $highPrice;
        } elseif (count($prices) > 1) {
            $maxPrice = max($prices);
            if ($cashPrice !== null && $maxPrice > $cashPrice) {
                $installmentPrice = $maxPrice;
            }
        }

        return [
            'cashPrice' => $cashPrice,
            'installmentPrice' => $installmentPrice,
            'inStock' => $inStock,
            'currency' => $currency,
        ];
    }
}
