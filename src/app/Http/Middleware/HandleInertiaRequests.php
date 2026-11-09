<?php

namespace App\Http\Middleware;

use App\Enums\ProductCategory;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        $monitoringSummary = null;
        if ($user !== null) {
            $recentAlerts = $user->alerts()
                ->with('product')
                ->latest()
                ->limit(15)
                ->get()
                ->map(static fn (Alert $alert): array => [
                    'id' => $alert->id,
                    'type' => $alert->type->value,
                    'type_label' => $alert->type->label(),
                    'triggered_price' => (float) $alert->triggered_price,
                    'installment_price' => $alert->installment_price !== null ? (float) $alert->installment_price : null,
                    'previous_price' => $alert->previous_price !== null ? (float) $alert->previous_price : null,
                    'target_price' => (float) $alert->target_price,
                    'read_at' => $alert->read_at?->toIso8601String(),
                    'is_read' => $alert->read_at !== null,
                    'created_at' => $alert->created_at?->toIso8601String(),
                    'product' => [
                        'id' => $alert->product->id,
                        'title' => $alert->product->title,
                        'nickname' => $alert->product->nickname,
                        'url' => $alert->product->url,
                        'domain' => $alert->product->domain,
                        'category' => $alert->product->category->value,
                        'image_url' => $alert->product->image_url,
                    ],
                ])
                ->all();

            $monitoringSummary = [
                'monitoredCount' => $user->products()->count(),
                'maxProducts' => (int) config('monitoring.max_products_per_user', 20),
                'unreadAlertsCount' => $user->alerts()->unread()->count(),
                'recentAlerts' => $recentAlerts,
                'categories' => ProductCategory::values(),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'monitoringSummary' => $monitoringSummary,
        ];
    }
}
