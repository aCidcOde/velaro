<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_register_me_and_logout_flow_works(): void
    {
        $response = $this->postJson(route('mobile.auth.register'), [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'phone' => '(11) 98888-0000',
            'document' => 'DOC-MOBILE-01',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'mobile@example.com')
            ->assertJsonPath('user.document', 'DOCMOBILE01');

        $token = $response->json('access_token');

        $this->withToken($token)
            ->getJson(route('mobile.auth.me'))
            ->assertOk()
            ->assertJsonPath('user.email', 'mobile@example.com');

        $this->withToken($token)
            ->postJson(route('mobile.auth.logout'))
            ->assertOk();

        $this->withToken($token)
            ->getJson(route('mobile.auth.me'))
            ->assertUnauthorized();
    }

    public function test_blocked_user_cannot_login_via_mobile_api(): void
    {
        $user = User::factory()->withoutTwoFactor()->blocked()->create([
            'email' => 'blocked@example.com',
        ]);

        $this->postJson(route('mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', User::BLOCKED_MESSAGE);
    }

    public function test_mobile_api_crud_is_scoped_to_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $headers = $this->mobileHeadersFor($user, 'mobile-token-1');
        $foreignCustomer = Customer::factory()->for($otherUser)->create();

        $customerResponse = $this->withHeaders($headers)->postJson(route('mobile.customers.store'), [
            'name' => 'Cliente API',
            'document' => 'DOC-API-01',
        ]);

        $customerResponse->assertCreated()
            ->assertJsonPath('data.name', 'Cliente API')
            ->assertJsonPath('data.document', 'DOCAPI01');

        $productResponse = $this->withHeaders($headers)->postJson(route('mobile.products.store'), [
            'name' => 'Produto API',
            'sku' => 'SKU-API-01',
            'price' => 80.5,
            'is_active' => true,
        ]);

        $productResponse->assertCreated()
            ->assertJsonPath('data.name', 'Produto API');

        $customerId = (int) $customerResponse->json('data.id');
        $productId = (int) $productResponse->json('data.id');

        $orderResponse = $this->withHeaders($headers)->postJson(route('mobile.orders.store'), [
            'customer_id' => $customerId,
            'reference' => 'API-REF-01',
            'status' => 'submitted',
            'items' => [
                [
                    'product_id' => $productId,
                    'quantity' => 3,
                    'unit_price' => 80.5,
                    'status' => 'processing',
                ],
            ],
        ]);

        $orderResponse->assertCreated()
            ->assertJsonPath('data.reference', 'API-REF-01')
            ->assertJsonPath('data.total_amount', 241.5);

        $this->withHeaders($headers)
            ->getJson(route('mobile.dashboard'))
            ->assertOk()
            ->assertJsonPath('stats.customers', 1)
            ->assertJsonPath('stats.products', 1)
            ->assertJsonPath('stats.orders', 1);

        $this->withHeaders($headers)
            ->getJson(route('mobile.customers.show', $foreignCustomer))
            ->assertNotFound();
    }

    public function test_mobile_forgot_password_endpoint_returns_generic_success_message(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset-mobile@example.com',
        ]);

        $this->postJson(route('mobile.auth.forgot-password'), [
            'email' => $user->email,
        ])->assertOk()
            ->assertJsonPath('message', 'Se o e-mail existir, o link de recuperacao foi enviado.');

        $this->assertNotNull(Password::broker()->createToken($user));
    }

    /**
     * @return array<string, string>
     */
    private function mobileHeadersFor(User $user, string $token): array
    {
        $user->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'api_token_expires_at' => now()->addDay(),
        ])->save();

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }
}
