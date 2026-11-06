<?php

use App\Enums\AlertType;
use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use App\Notifications\BackInStockNotification;
use App\Notifications\PriceDropNotification;
use App\Services\AlertEvaluatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('AlertEvaluatorService triggers price drop alert and email when price drops to or below target with stock (RF-016, RN-008)', function () {
    Notification::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'target_price' => 300.00,
        'current_price' => 320.00,
        'in_stock' => true,
    ]);

    $service = new AlertEvaluatorService;

    // Price drops to R$ 280.00 (below target of R$ 300.00) and is in stock
    $service->evaluate(
        product: $product,
        previousPrice: 320.00,
        previousInStock: true,
        newPrice: 280.00,
        newInstallmentPrice: 300.00,
        newInStock: true,
    );

    Notification::assertSentTo($user, PriceDropNotification::class, function (PriceDropNotification $notification) use ($product) {
        $mail = $notification->toMail($product->user);
        expect($mail->subject)->toContain('Alerta de Preço')
            ->and((float) $notification->alert->triggered_price)->toBe(280.00)
            ->and((float) $notification->alert->target_price)->toBe(300.00);

        return $notification->alert->product_id === $product->id
            && $notification->alert->type === AlertType::PriceDrop;
    });

    expect(Alert::query()->count())->toBe(1);
    $savedAlert = Alert::query()->first();
    expect($savedAlert)->not->toBeNull()
        ->and($savedAlert->type)->toBe(AlertType::PriceDrop)
        ->and((float) $savedAlert->triggered_price)->toBe(280.00)
        ->and((float) $savedAlert->previous_price)->toBe(320.00)
        ->and((float) $savedAlert->target_price)->toBe(300.00)
        ->and($savedAlert->read_at)->toBeNull();
});

test('AlertEvaluatorService suppresses price drop alert if item is out of stock (RN-008)', function () {
    Notification::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'target_price' => 300.00,
        'current_price' => 350.00,
        'in_stock' => false,
    ]);

    $service = new AlertEvaluatorService;

    // Price dropped below target, but item is NOT in stock
    $service->evaluate(
        product: $product,
        previousPrice: 350.00,
        previousInStock: false,
        newPrice: 250.00,
        newInstallmentPrice: null,
        newInStock: false,
    );

    Notification::assertNothingSent();
    expect(Alert::query()->count())->toBe(0);
});

test('AlertEvaluatorService suppresses repetitive alerts within 6-hour cooldown unless further drop >= 5% (RN-009)', function () {
    Notification::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'target_price' => 300.00,
        'current_price' => 280.00,
        'in_stock' => true,
    ]);

    // An alert was already sent 2 hours ago at R$ 280.00
    Alert::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'type' => AlertType::PriceDrop,
        'triggered_price' => 280.00,
        'target_price' => 300.00,
        'created_at' => Carbon::now()->subHours(2),
    ]);

    $service = new AlertEvaluatorService;

    // Scenario 1: Price drops to R$ 275.00 -> drop of (280 - 275) / 280 = 1.78% (< 5%)
    // Should be suppressed due to cooldown!
    $service->evaluate(
        product: $product,
        previousPrice: 280.00,
        previousInStock: true,
        newPrice: 275.00,
        newInstallmentPrice: null,
        newInStock: true,
    );

    Notification::assertNothingSent();
    expect(Alert::query()->count())->toBe(1);

    // Scenario 2: Price drops to R$ 260.00 -> drop of (280 - 260) / 280 = 7.14% (>= 5%)
    // Bypasses cooldown and sends new alert!
    $service->evaluate(
        product: $product,
        previousPrice: 275.00,
        previousInStock: true,
        newPrice: 260.00,
        newInstallmentPrice: null,
        newInStock: true,
    );

    Notification::assertSentTo($user, PriceDropNotification::class, 1);
    expect(Alert::query()->count())->toBe(2);

    // Scenario 3: 7 hours later, price remains at R$ 260.00 (cooldown expired)
    Carbon::setTestNow(Carbon::now()->addHours(7));

    $service->evaluate(
        product: $product,
        previousPrice: 260.00,
        previousInStock: true,
        newPrice: 260.00,
        newInstallmentPrice: null,
        newInStock: true,
    );

    Notification::assertSentTo($user, PriceDropNotification::class, 2);
    expect(Alert::query()->count())->toBe(3);

    Carbon::setTestNow();
});

test('AlertEvaluatorService triggers back in stock alert when product transitions from out-of-stock to available (RF-017)', function () {
    Notification::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'target_price' => 1000.00,
        'current_price' => 1200.00,
        'in_stock' => false,
        'notify_on_stock' => true,
    ]);

    $service = new AlertEvaluatorService;

    // Product was out-of-stock and now is back in stock
    $service->evaluate(
        product: $product,
        previousPrice: 1200.00,
        previousInStock: false,
        newPrice: 1200.00,
        newInstallmentPrice: 1300.00,
        newInStock: true,
    );

    Notification::assertSentTo($user, BackInStockNotification::class, function (BackInStockNotification $notification) use ($product) {
        $mail = $notification->toMail($product->user);
        expect($mail->subject)->toContain('Produto de Volta ao Estoque')
            ->and($notification->alert->type)->toBe(AlertType::BackInStock);

        return true;
    });

    expect(Alert::query()->count())->toBe(1);
    $alert = Alert::query()->first();
    expect($alert->type)->toBe(AlertType::BackInStock);
});

test('AlertEvaluatorService does not trigger back in stock alert if notify_on_stock is false', function () {
    Notification::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'target_price' => 1000.00,
        'current_price' => 1200.00,
        'in_stock' => false,
        'notify_on_stock' => false,
    ]);

    $service = new AlertEvaluatorService;

    $service->evaluate(
        product: $product,
        previousPrice: 1200.00,
        previousInStock: false,
        newPrice: 1200.00,
        newInstallmentPrice: null,
        newInStock: true,
    );

    Notification::assertNothingSent();
    expect(Alert::query()->count())->toBe(0);
});

test('authenticated user can view alerts list and unread count in alerts center (RF-016)', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    Alert::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'type' => AlertType::PriceDrop,
        'read_at' => null,
    ]);

    Alert::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'type' => AlertType::BackInStock,
        'read_at' => Carbon::now()->subDay(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('alerts.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('alerts/Index')
        ->has('alerts', 2)
        ->where('unreadCount', 1)
    );
});

test('user can mark a single alert as read', function () {
    $user = User::factory()->create();
    $alert = Alert::factory()->create([
        'user_id' => $user->id,
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('alerts.mark-as-read', $alert));

    $response->assertRedirect();
    $alert->refresh();
    expect($alert->read_at)->not->toBeNull();
});

test('user can mark all alerts as read', function () {
    $user = User::factory()->create();
    Alert::factory()->count(3)->create([
        'user_id' => $user->id,
        'read_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('alerts.mark-all-as-read'));

    $response->assertRedirect();
    expect($user->alerts()->unread()->count())->toBe(0);
});

test('unauthorized user cannot mark another users alert as read', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $alert = Alert::factory()->create([
        'user_id' => $owner->id,
        'read_at' => null,
    ]);

    $response = $this->actingAs($attacker)
        ->patch(route('alerts.mark-as-read', $alert));

    $response->assertForbidden();
    $alert->refresh();
    expect($alert->read_at)->toBeNull();
});
