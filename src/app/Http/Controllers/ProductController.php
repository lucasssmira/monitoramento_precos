<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Http\Requests\Products\StoreProductRequest;
use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $products = $user->products()
            ->latest()
            ->get()
            ->map(static fn (Product $product): array => [
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
                'created_at' => $product->created_at?->toIso8601String(),
            ]);

        return Inertia::render('products/Index', [
            'products' => $products,
            'categories' => ProductCategory::values(),
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
}
