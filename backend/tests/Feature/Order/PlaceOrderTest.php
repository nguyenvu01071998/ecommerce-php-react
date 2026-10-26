<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_guest_cannot_place_order(): void
    {
        $this->app['auth']->forgetGuards(); // bỏ user đã đăng nhập bằng actingAs ở setUp

        $product = Product::factory()->create();

        $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertUnauthorized();
    }

    public function test_places_order_decrements_stock_and_calculates_total_on_server(): void
    {
        $a = Product::factory()->create(['price' => 100000, 'stock' => 10]);
        $b = Product::factory()->create(['price' => 250500, 'stock' => 5]);

        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 3],
                ['product_id' => $b->id, 'quantity' => 2],
            ],
        ]);

        // 3 x 100000 + 2 x 250500 = 801000
        $response->assertCreated()
            ->assertJsonPath('data.total', '801000.00')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.subtotal', '300000.00');

        $this->assertSame(7, $a->fresh()->stock);
        $this->assertSame(3, $b->fresh()->stock);
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total' => 801000, 'status' => OrderStatus::Pending->value]);
        $this->assertDatabaseHas('order_items', ['product_id' => $a->id, 'quantity' => 3, 'price' => 100000]);
    }

    public function test_ignores_price_and_total_sent_by_client(): void
    {
        $product = Product::factory()->create(['price' => 500000, 'stock' => 5]);

        $this->postJson('/api/orders', [
            'total' => 1,                       // client cố gửi tổng tiền giả
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 1,                   // client cố gửi giá giả
            ]],
        ])->assertCreated()->assertJsonPath('data.total', '1000000.00');

        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'price' => 500000]);
    }

    public function test_order_item_keeps_price_snapshot_after_product_price_changes(): void
    {
        $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);
        $orderId = $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->json('data.id');

        $product->update(['price' => 999000]);

        $this->getJson("/api/orders/{$orderId}")
            ->assertJsonPath('data.items.0.price', '100000.00')
            ->assertJsonPath('data.total', '100000.00');
    }

    public function test_can_buy_exactly_all_remaining_stock(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 3]]])->assertCreated();

        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_rejects_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 3]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_whole_order_is_rolled_back_if_any_item_fails(): void
    {
        $enough = Product::factory()->create(['stock' => 10]);
        $notEnough = Product::factory()->create(['stock' => 1]);

        // Sản phẩm 1 đủ hàng, sản phẩm 2 thiếu -> KHÔNG được trừ kho sản phẩm 1, không tạo đơn
        $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $enough->id, 'quantity' => 4],
                ['product_id' => $notEnough->id, 'quantity' => 5],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.1.quantity');

        $this->assertSame(10, $enough->fresh()->stock);
        $this->assertSame(1, $notEnough->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_validates_items_payload(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/orders', [])->assertJsonValidationErrors('items');
        $this->postJson('/api/orders', ['items' => []])->assertJsonValidationErrors('items');
        $this->postJson('/api/orders', ['items' => [['product_id' => 999999, 'quantity' => 1]]])
            ->assertJsonValidationErrors('items.0.product_id');
        $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 0]]])
            ->assertJsonValidationErrors('items.0.quantity');
        $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => -3]]])
            ->assertJsonValidationErrors('items.0.quantity');
        // Cùng một sản phẩm xuất hiện 2 lần
        $this->postJson('/api/orders', ['items' => [
            ['product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $product->id, 'quantity' => 1],
        ]])->assertJsonValidationErrors('items.0.product_id');

        $this->assertDatabaseCount('orders', 0);
    }
}
