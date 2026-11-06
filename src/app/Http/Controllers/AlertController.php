<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    /**
     * Display the alerts center with history and notifications (RF-016).
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $alerts = $user->alerts()
            ->with('product')
            ->latest()
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
            ]);

        $unreadCount = $user->alerts()->unread()->count();

        return Inertia::render('alerts/Index', [
            'alerts' => $alerts,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a single alert as read.
     */
    public function markAsRead(Alert $alert): RedirectResponse
    {
        Gate::authorize('update', $alert);

        $alert->markAsRead();

        return back();
    }

    /**
     * Mark all unread alerts of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->alerts()->unread()->update(['read_at' => now()]);

        return back();
    }
}
