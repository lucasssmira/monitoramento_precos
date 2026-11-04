<?php

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Jobs\CollectProductPriceJob;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('authenticated and verified users can view the products page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
});

test('unverified users cannot access the products page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertRedirect(route('verification.notice'));
});

test('product can be registered asynchronously with a supported e-commerce URL (US-002 / RF-005)', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('products.store'), [
        'url' => 'https://www.kabum.com.br/produto/123456/placa-de-video?utm_source=google&fbclid=abc123',
        'target_price' => 1500.00,
        'nickname' => 'Placa de Vídeo GPU',
        'category' => ProductCategory::Hardware->value,
        'notify_on_stock' => true,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Pendente / Coletando dados...');

    $product = Product::query()->where('user_id', $user->id)->first();

    expect($product)->not->toBeNull()
        ->and($product->domain)->toBe('kabum.com.br')
        ->and($product->normalized_url)->toBe('https://kabum.com.br/produto/123456/placa-de-video')
        ->and($product->category)->toBe(ProductCategory::Hardware)
        ->and((float) $product->target_price)->toBe(1500.00)
        ->and($product->nickname)->toBe('Placa de Vídeo GPU')
        ->and($product->notify_on_stock)->toBeTrue()
        ->and($product->status)->toBe(ProductStatus::Pending)
        ->and($product->status->label())->toBe('Pendente / Coletando dados...');

    Queue::assertPushed(CollectProductPriceJob::class, fn (CollectProductPriceJob $job) => $job->product->is($product));
});

test('product registration with unsupported domain is rejected with pt_BR message (RN-003)', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.store'), [
            'url' => 'https://www.americanas.com.br/produto/999999',
            'target_price' => 1500.00,
            'category' => ProductCategory::Hardware->value,
        ]);

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors([
            'url' => 'O domínio informado ainda não é suportado pelo sistema.',
        ]);

    expect(Product::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('product registration blocks SSRF internal addresses with unsupported domain message (RNF-006)', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.store'), [
            'url' => 'http://127.0.0.1/internal-admin',
            'target_price' => 100.00,
            'category' => ProductCategory::Outros->value,
        ]);

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors([
            'url' => 'O domínio informado ainda não é suportado pelo sistema.',
        ]);

    expect(Product::query()->count())->toBe(0);
});

test('duplicate product URL with tracking parameters is rejected after sanitization for the same user (RN-004)', function () {
    Queue::fake();

    $user = User::factory()->create();

    Product::factory()->for($user)->create([
        'url' => 'https://www.pichau.com.br/processador-amd-ryzen-7',
        'normalized_url' => 'https://pichau.com.br/processador-amd-ryzen-7',
        'domain' => 'pichau.com.br',
    ]);

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.store'), [
            'url' => 'https://pichau.com.br/processador-amd-ryzen-7/?utm_source=newsletter&ref=home&fbclid=xyz987',
            'target_price' => 1200.00,
            'category' => ProductCategory::Hardware->value,
        ]);

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors([
            'url' => 'Este produto já está sendo monitorado na sua conta.',
        ]);

    expect($user->products()->count())->toBe(1);
});

test('different users can monitor the same product URL independently (RN-004)', function () {
    Queue::fake();

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Product::factory()->for($firstUser)->create([
        'url' => 'https://www.magazineluiza.com.br/smartphone/p/12345',
        'normalized_url' => 'https://magazineluiza.com.br/smartphone/p/12345',
        'domain' => 'magazineluiza.com.br',
    ]);

    $response = $this->actingAs($secondUser)->post(route('products.store'), [
        'url' => 'https://www.magazineluiza.com.br/smartphone/p/12345?utm_source=app',
        'target_price' => 1999.90,
        'category' => ProductCategory::Smartphones->value,
    ]);

    $response->assertSessionHasNoErrors();
    expect($secondUser->products()->count())->toBe(1);
});

test('user cannot register more than the maximum allowed products (RN-010)', function () {
    Queue::fake();

    $user = User::factory()->create();
    Product::factory()->count(20)->for($user)->create();

    $response = $this->actingAs($user)
        ->from(route('products.index'))
        ->post(route('products.store'), [
            'url' => 'https://www.amazon.com.br/dp/B09V3KXJPB',
            'target_price' => 450.00,
            'category' => ProductCategory::Perifericos->value,
        ]);

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors([
            'url' => 'Você atingiu o limite máximo de 20 produtos monitorados.',
        ]);

    expect($user->products()->count())->toBe(20);
    Queue::assertNothingPushed();
});
