<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductSkuValidationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('clients')]
    public function test_create_rejects_a_sku_owned_by_another_account(bool $mobile): void
    {
        $existing = Product::factory()->create(['sku' => 'GLOBAL-SKU']);
        $this->authenticateClient($mobile);

        $this->postJson(route($mobile ? 'mobile.products.store' : 'products.store'), [
            'name' => 'Outro produto', 'sku' => $existing->sku, 'price' => 100,
        ])->assertUnprocessable()->assertJsonValidationErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }

    #[DataProvider('clients')]
    public function test_update_keeps_own_sku_but_rejects_another_products_sku(bool $mobile): void
    {
        $existing = Product::factory()->create(['sku' => 'OTHER-SKU']);
        $user = $this->authenticateClient($mobile);
        $product = Product::factory()->for($user)->create(['sku' => 'MY-SKU']);
        $url = route($mobile ? 'mobile.products.update' : 'products.update', $product);

        $response = $this->putJson($url, ['name' => 'Produto atualizado', 'sku' => $product->sku, 'price' => 120]);
        $mobile ? $response->assertOk() : $response->assertRedirect();

        $this->putJson($url, ['name' => 'Conflito', 'sku' => $existing->sku, 'price' => 150])
            ->assertUnprocessable()->assertJsonValidationErrors('sku');

        $this->assertSame('MY-SKU', $product->fresh()->sku);
        $this->assertSame('Produto atualizado', $product->fresh()->name);
    }

    #[DataProvider('clients')]
    public function test_multiple_products_can_omit_the_sku(bool $mobile): void
    {
        Product::factory()->create(['sku' => null]);
        $this->authenticateClient($mobile);

        $response = $this->postJson(route($mobile ? 'mobile.products.store' : 'products.store'), [
            'name' => 'Sem SKU', 'sku' => null, 'price' => 100,
        ]);
        $mobile ? $response->assertCreated() : $response->assertRedirect();
        $this->assertDatabaseCount('products', 2);
    }

    public function test_admin_can_keep_a_foreign_products_sku_but_cannot_duplicate_another(): void
    {
        $admin = $this->createBackendAdmin();
        $product = Product::factory()->create(['sku' => 'ADMIN-EDIT']);
        $other = Product::factory()->create(['sku' => 'OCCUPIED']);
        $this->actingAs($admin);

        $this->putJson(route('backend.products.update', $product), [
            'name' => $product->name, 'sku' => $product->sku, 'price' => 100,
        ])->assertRedirect();

        $this->putJson(route('backend.products.update', $product), [
            'name' => $product->name, 'sku' => $other->sku, 'price' => 100,
        ])->assertUnprocessable()->assertJsonValidationErrors('sku');
    }

    /** @return array<string, array{bool}> */
    public static function clients(): array
    {
        return ['web' => [false], 'mobile' => [true]];
    }

    private function authenticateClient(bool $mobile): User
    {
        $user = User::factory()->withoutTwoFactor()->create();

        if ($mobile) {
            $user->forceFill([
                'api_token_hash' => hash('sha256', 'sku-test-token'),
                'api_token_expires_at' => now()->addDay(),
            ])->save();
            $this->withToken('sku-test-token');
        } else {
            $this->actingAs($user);
        }

        return $user;
    }
}
