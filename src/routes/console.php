<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agendamento periódico de coleta de preços para produtos ativos (RF-010, RN-005)
$scrapeInterval = (int) config('monitoring.scrape_interval_minutes', 60);

if ($scrapeInterval <= 1) {
    Schedule::command('products:scrape-active')->everyMinute()->withoutOverlapping();
} elseif ($scrapeInterval === 60) {
    Schedule::command('products:scrape-active')->hourly()->withoutOverlapping();
} else {
    Schedule::command('products:scrape-active')->cron("*/{$scrapeInterval} * * * *")->withoutOverlapping();
}
