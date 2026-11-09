<?php

namespace Tests\Feature;

use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_shared_monitoring_summary_and_dark_theme_props_are_provided_to_authenticated_pages(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user)->create();

        Alert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'type' => AlertType::PriceDrop,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('class="dark"', false);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('monitoringSummary', fn (Assert $summary) => $summary
                ->where('monitoredCount', 1)
                ->where('maxProducts', 20)
                ->where('unreadAlertsCount', 1)
                ->has('recentAlerts', 1)
                ->has('categories')
            )
        );
    }
}
