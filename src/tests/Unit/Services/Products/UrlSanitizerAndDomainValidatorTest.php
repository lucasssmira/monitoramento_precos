<?php

use App\Services\Products\SupportedDomainValidator;
use App\Services\Products\UrlSanitizerService;

describe('UrlSanitizerService', function () {
    it('removes tracking parameters and normalizes scheme, host and trailing slash (RN-004)', function () {
        $sanitizer = new UrlSanitizerService;

        $rawUrl = 'http://www.Kabum.com.br/produto/12345/placa-de-video/?utm_source=google&utm_medium=cpc&fbclid=IwAR123&ref=afiliado&tag=promo&gclid=abc#especificacoes';

        expect($sanitizer->sanitize($rawUrl))
            ->toBe('https://kabum.com.br/produto/12345/placa-de-video');
    });

    it('preserves legitimate query parameters sorted alphabetically while stripping tracking parameters', function () {
        $sanitizer = new UrlSanitizerService;

        $rawUrl = 'https://www.amazon.com.br/dp/B08N5WRWNW?th=1&psc=1&utm_campaign=blackfriday&pd_rd_r=xyz';

        expect($sanitizer->sanitize($rawUrl))
            ->toBe('https://amazon.com.br/dp/B08N5WRWNW?psc=1&th=1');
    });

    it('returns null for invalid urls, non-http schemes or embedded credentials', function () {
        $sanitizer = new UrlSanitizerService;

        expect($sanitizer->sanitize(''))->toBeNull()
            ->and($sanitizer->sanitize('not-a-valid-url'))->toBeNull()
            ->and($sanitizer->sanitize('ftp://kabum.com.br/produto/1'))->toBeNull()
            ->and($sanitizer->sanitize('javascript:alert(1)'))->toBeNull()
            ->and($sanitizer->sanitize('https://user:pass@kabum.com.br/produto/1'))->toBeNull();
    });
});

describe('SupportedDomainValidator', function () {
    it('accepts all 5 homologated e-commerce domains and resolves their canonical domain (RN-003)', function (string $url, string $expectedCanonical) {
        $validator = new SupportedDomainValidator;

        expect($validator->isSupported($url))->toBeTrue()
            ->and($validator->resolveCanonicalDomain($url))->toBe($expectedCanonical);
    })->with([
        'Amazon Brasil' => ['https://www.amazon.com.br/dp/B08N5WRWNW', 'amazon.com.br'],
        'Kabum' => ['https://www.kabum.com.br/produto/12345', 'kabum.com.br'],
        'Mercado Livre principal' => ['https://www.mercadolivre.com.br/p/MLB12345', 'mercadolivre.com.br'],
        'Mercado Livre subdominio produto' => ['https://produto.mercadolivre.com.br/MLB-123456789-item-_JM', 'mercadolivre.com.br'],
        'Magazine Luiza' => ['https://www.magazineluiza.com.br/produto/p/12345/', 'magazineluiza.com.br'],
        'Magazine Voce' => ['https://www.magazinevoce.com.br/magazineoficial/p/12345/', 'magazineluiza.com.br'],
        'Pichau' => ['https://www.pichau.com.br/placa-de-video-teste', 'pichau.com.br'],
    ]);

    it('rejects unsupported e-commerce domains (RN-003)', function (string $url) {
        $validator = new SupportedDomainValidator;

        expect($validator->isSupported($url))->toBeFalse()
            ->and($validator->resolveCanonicalDomain($url))->toBeNull();
    })->with([
        'Americanas' => ['https://www.americanas.com.br/produto/123'],
        'Shopee' => ['https://shopee.com.br/produto-i.123.456'],
        'AliExpress' => ['https://pt.aliexpress.com/item/100500.html'],
        'Amazon US' => ['https://www.amazon.com/dp/B08N5WRWNW'],
    ]);

    it('blocks SSRF vectors including localhost, private IPs, non-standard ports and non-http schemes (RNF-006)', function (string $unsafeUrl) {
        $validator = new SupportedDomainValidator;

        expect($validator->isSafeHttpUrl($unsafeUrl))->toBeFalse()
            ->and($validator->isSupported($unsafeUrl))->toBeFalse();
    })->with([
        'localhost' => ['http://localhost/admin'],
        'loopback IPv4' => ['http://127.0.0.1/server-status'],
        'private 10.x' => ['http://10.0.0.1/internal'],
        'private 192.168.x' => ['http://192.168.1.1/router'],
        'cloud metadata IP' => ['http://169.254.169.254/latest/meta-data/'],
        'internal suffix' => ['https://kabum.internal/produto/1'],
        'local suffix' => ['https://amazon.local/produto/1'],
        'non-standard port' => ['https://www.kabum.com.br:8080/produto/1'],
        'embedded credentials' => ['https://admin:secret@www.kabum.com.br/produto/1'],
        'file scheme' => ['file:///etc/passwd'],
    ]);
});
