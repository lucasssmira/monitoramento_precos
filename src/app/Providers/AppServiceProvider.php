<?php

namespace App\Providers;

use App\Jobs\CollectProductPriceJob;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureDevCommands();
    }

    /**
     * Register queue worker and scheduler in `php artisan dev` / `composer run dev` (RF-010).
     */
    protected function configureDevCommands(): void
    {
        DevCommands::artisan('queue:listen --tries=3 --timeout=60', 'queue');
        DevCommands::artisan('schedule:work', 'schedule');
    }

    /**
     * Configure domain rate limits for asynchronous scraping jobs (RN-005).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('domain-scraping', static function (object $job) {
            if ($job instanceof CollectProductPriceJob) {
                $seconds = (int) config('monitoring.domain_rate_limit_seconds', 3);
                $perMinute = $seconds > 0 ? (int) floor(60 / $seconds) : 20;

                return Limit::perMinute(max(1, $perMinute))->by($job->product->domain);
            }

            return Limit::none();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
