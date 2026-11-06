<?php

namespace App\Console\Commands;

use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use Illuminate\Console\Command;

class ScrapeActiveProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:scrape-active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agendar e despachar a coleta periódica de preços para produtos ativos com balanceamento round-robin por domínio (RF-010, RN-005)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $products = Product::query()
            ->activeForScraping()
            ->get();

        if ($products->isEmpty()) {
            $this->info('Nenhum produto ativo ou instável encontrado para coleta periódica.');

            return self::SUCCESS;
        }

        // Group products by domain and interleave in round-robin order (RN-005)
        $grouped = $products->groupBy('domain');
        $maxPerDomain = $grouped->map->count()->max() ?? 0;

        /** @var list<Product> $interleaved */
        $interleaved = [];

        for ($i = 0; $i < $maxPerDomain; $i++) {
            foreach ($grouped as $domainProducts) {
                if (isset($domainProducts[$i])) {
                    $interleaved[] = $domainProducts[$i];
                }
            }
        }

        foreach ($interleaved as $product) {
            CollectProductPriceJob::dispatch($product);
        }

        $this->info(sprintf(
            '%d produto(s) despachado(s) para coleta periódica em %d domínio(s) com balanceamento round-robin.',
            count($interleaved),
            $grouped->count()
        ));

        return self::SUCCESS;
    }
}
