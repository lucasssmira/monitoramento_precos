<?php

use App\Enums\ProductCategory;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('user can list only their own monitored products', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Product::factory()->count(2)->for($user)->create();
    Product::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($user)->get(route('products.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('products/Index')
        ->has('products', 2)
        ->has('categories')
        ->has('statuses')
        ->where('filters.category', 'all')
        ->where('filters.status', 'all')
    );
});

test('user can filter products by category and status (RF-006)', function () {
    $user = User::factory()->create();

    Product::factory()->for($user)->create([
        'category' => ProductCategory::Hardware,
        'status' => ProductStatus::Active,
    ]);

    Product::factory()->for($user)->create([
        'category' => ProductCategory::Hardware,
        'status' => ProductStatus::Paused,
    ]);

    Product::factory()->for($user)->create([
        'category' => ProductCategory::Perifericos,
        'status' => ProductStatus::Active,
    ]);

    // Filtro apenas por categoria
    $responseCategory = $this->actingAs($user)->get(route('products.index', ['category' => 'Hardware']));
    $responseCategory->assertOk();
    $responseCategory->assertInertia(fn ($page) => $page
        ->has('products', 2)
        ->where('filters.category', 'Hardware')
    );

    // Filtro apenas por status
    $responseStatus = $this->actingAs($user)->get(route('products.index', ['status' => 'paused']));
    $responseStatus->assertOk();
    $responseStatus->assertInertia(fn ($page) => $page
        ->has('products', 1)
        ->where('filters.status', 'paused')
    );

    // Filtro combinado categoria + status
    $responseCombined = $this->actingAs($user)->get(route('products.index', [
        'category' => 'Hardware',
        'status' => 'active',
    ]));
    $responseCombined->assertOk();
    $responseCombined->assertInertia(fn ($page) => $page
        ->has('products', 1)
        ->where('products.0.category', 'Hardware')
        ->where('products.0.status', 'active')
        ->where('filters.category', 'Hardware')
        ->where('filters.status', 'active')
    );
});

test('user can update product parameters without losing identification or history (US-003 / RF-007)', function () {
    $user = User::factory()->create();

    $product = Product::factory()->for($user)->create([
        'title' => 'Placa de Vídeo RTX 4060',
        'nickname' => 'GPU Antiga',
        'target_price' => 2000.00,
        'category' => ProductCategory::Hardware,
        'notify_on_stock' => false,
        'url' => 'https://www.kabum.com.br/produto/123/rtx-4060',
        'normalized_url' => 'https://kabum.com.br/produto/123/rtx-4060',
        'domain' => 'kabum.com.br',
    ]);

    $response = $this->actingAs($user)->put(route('products.update', $product), [
        'target_price' => 2100.00,
        'nickname' => 'Placa de Vídeo para Upgrade',
        'category' => ProductCategory::Hardware->value,
        'notify_on_stock' => true,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Produto atualizado com sucesso.');

    $product->refresh();

    expect((float) $product->target_price)->toBe(2100.00)
        ->and($product->nickname)->toBe('Placa de Vídeo para Upgrade')
        ->and($product->category)->toBe(ProductCategory::Hardware)
        ->and($product->notify_on_stock)->toBeTrue()
        ->and($product->title)->toBe('Placa de Vídeo RTX 4060')
        ->and($product->domain)->toBe('kabum.com.br')
        ->and($product->normalized_url)->toBe('https://kabum.com.br/produto/123/rtx-4060');
});

test('updating product with invalid parameters returns validation errors in pt_BR', function () {
    $user = User::factory()->create();
    $product = Product::factory()->for($user)->create();

    $response = $this->actingAs($user)->put(route('products.update', $product), [
        'target_price' => -10,
        'category' => 'CategoriaInexistente',
        'notify_on_stock' => 'nao-booleano',
    ]);

    $response->assertSessionHasErrors(['target_price', 'category']);
});

test('user cannot update a product belonging to another user (403 Forbidden)', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $product = Product::factory()->for($owner)->create([
        'target_price' => 1000.00,
    ]);

    $response = $this->actingAs($attacker)->put(route('products.update', $product), [
        'target_price' => 500.00,
        'category' => ProductCategory::Hardware->value,
        'notify_on_stock' => false,
    ]);

    $response->assertForbidden();

    $product->refresh();
    expect((float) $product->target_price)->toBe(1000.00);
});

test('user can pause an active product monitoring (US-003 / RF-008)', function () {
    $user = User::factory()->create();

    $product = Product::factory()->for($user)->create([
        'status' => ProductStatus::Active,
    ]);

    $response = $this->actingAs($user)->patch(route('products.toggle-status', $product));

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Monitoramento pausado com sucesso.');

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Paused);
});

test('user can resume a paused product monitoring (US-003 / RF-008)', function () {
    $user = User::factory()->create();

    $product = Product::factory()->for($user)->create([
        'status' => ProductStatus::Paused,
    ]);

    $response = $this->actingAs($user)->patch(route('products.toggle-status', $product));

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Monitoramento retomado com sucesso.');

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Active);
});

test('user cannot pause or resume another users product (403 Forbidden)', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $product = Product::factory()->for($owner)->create([
        'status' => ProductStatus::Active,
    ]);

    $response = $this->actingAs($attacker)->patch(route('products.toggle-status', $product));

    $response->assertForbidden();

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Active);
});

test('user can permanently delete a product from monitoring (US-003 / RF-009)', function () {
    $user = User::factory()->create();

    $product = Product::factory()->for($user)->create([
        'nickname' => 'Headphone Bluetooth',
    ]);

    $response = $this->actingAs($user)->delete(route('products.destroy', $product));

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Produto excluído com sucesso.');

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('user cannot delete another users product (403 Forbidden)', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $product = Product::factory()->for($owner)->create();

    $response = $this->actingAs($attacker)->delete(route('products.destroy', $product));

    $response->assertForbidden();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});
