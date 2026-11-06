<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display the product monitoring list and registration screen.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $selectedCategory = $request->query('category');
        $selectedStatus = $request->query('status');

        $query = $user->products()->latest();

        if (is_string($selectedCategory) && $selectedCategory !== '' && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        if (is_string($selectedStatus) && $selectedStatus !== '' && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        $products = $query->get()->map(static fn (Product $product): array => [
            'id' => $product->id,
            'url' => $product->url,
            'normalized_url' => $product->normalized_url,
            'domain' => $product->domain,
            'title' => $product->title,
            'nickname' => $product->nickname,
            'category' => $product->category->value,
            'image_url' => $product->image_url,
            'target_price' => (float) $product->target_price,
            'current_price' => $product->current_price !== null ? (float) $product->current_price : null,
            'installment_price' => $product->installment_price !== null ? (float) $product->installment_price : null,
            'in_stock' => $product->in_stock,
            'notify_on_stock' => $product->notify_on_stock,
            'status' => $product->status->value,
            'status_label' => $product->status->label(),
            'last_checked_at' => $product->last_checked_at?->toIso8601String(),
            'last_manual_check_at' => $product->last_manual_check_at?->toIso8601String(),
            'created_at' => $product->created_at?->toIso8601String(),
        ]);

        return Inertia::render('products/Index', [
            'products' => $products,
            'categories' => ProductCategory::values(),
            'statuses' => ProductStatus::options(),
            'filters' => [
                'category' => is_string($selectedCategory) && $selectedCategory !== '' ? $selectedCategory : 'all',
                'status' => is_string($selectedStatus) && $selectedStatus !== '' ? $selectedStatus : 'all',
            ],
            'maxProducts' => (int) config('monitoring.max_products_per_user', 20),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Store a newly registered product and dispatch the initial scraping job (RF-005).
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $product = $user->products()->create([
            'url' => $validated['url'],
            'normalized_url' => $request->normalizedUrl(),
            'domain' => $request->canonicalDomain(),
            'nickname' => $validated['nickname'] ?? null,
            'category' => $validated['category'],
            'target_price' => $validated['target_price'],
            'in_stock' => true,
            'notify_on_stock' => (bool) $validated['notify_on_stock'],
            'status' => ProductStatus::Pending,
        ]);

        CollectProductPriceJob::dispatch($product);

        return to_route('products.index')
            ->with('status', ProductStatus::Pending->label());
    }

    /**
     * Update product monitoring parameters (RF-007).
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validated();

        $product->update([
            'target_price' => $validated['target_price'],
            'nickname' => $validated['nickname'] ?? null,
            'category' => $validated['category'],
            'notify_on_stock' => (bool) $validated['notify_on_stock'],
        ]);

        return to_route('products.index')
            ->with('status', 'Produto atualizado com sucesso.');
    }

    /**
     * Toggle product monitoring status between active and paused (RF-008).
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        Gate::authorize('toggleStatus', $product);

        if ($product->status === ProductStatus::Paused) {
            $product->update(['status' => ProductStatus::Active]);
            $message = 'Monitoramento retomado com sucesso.';
        } else {
            $product->update(['status' => ProductStatus::Paused]);
            $message = 'Monitoramento pausado com sucesso.';
        }

        return to_route('products.index')
            ->with('status', $message);
    }

    /**
     * Request an immediate manual price check on-demand (RF-010, RN-005).
     */
    public function checkNow(Product $product): RedirectResponse
    {
        Gate::authorize('checkNow', $product);

        $cooldownMinutes = (int) config('monitoring.manual_check_cooldown_minutes', 10);

        if ($product->last_manual_check_at !== null) {
            $nextAllowedAt = $product->last_manual_check_at->copy()->addMinutes($cooldownMinutes);

            if (now()->lt($nextAllowedAt)) {
                $remainingMinutes = (int) ceil(now()->diffInSeconds($nextAllowedAt) / 60);

                return back()->withErrors([
                    'manual_check' => "Aguarde {$remainingMinutes} minuto(s) para realizar uma nova verificação manual deste produto.",
                ]);
            }
        }

        $product->update([
            'last_manual_check_at' => now(),
        ]);

        CollectProductPriceJob::dispatch($product);

        return to_route('products.index')
            ->with('status', 'Coleta sob demanda iniciada. Atualizando dados do produto...');
    }

    /**
     * Remove the product from monitoring permanently (RF-009).
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return to_route('products.index')
            ->with('status', 'Produto excluído com sucesso.');
    }
}
