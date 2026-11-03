<?php

namespace Tests\Feature;

use Monolog\Formatter\JsonFormatter;
use Tests\TestCase;

class ScraperLoggingConfigTest extends TestCase
{
    public function test_scraper_log_channel_is_configured_with_json_formatter(): void
    {
        $config = config('logging.channels.scraper');

        $this->assertIsArray($config);
        $this->assertSame('daily', $config['driver']);
        $this->assertSame(JsonFormatter::class, $config['formatter']);
        $this->assertSame(storage_path('logs/scraper.log'), $config['path']);
    }
}
