<?php

return [
    'max_products_per_user' => (int) env('MAX_PRODUCTS_PER_USER', 20),
    'scrape_interval_minutes' => (int) env('SCRAPE_INTERVAL_MINUTES', 60),
    'manual_check_cooldown_minutes' => (int) env('MANUAL_CHECK_COOLDOWN_MINUTES', 10),
    'domain_rate_limit_seconds' => (int) env('DOMAIN_RATE_LIMIT_SECONDS', 3),
];
