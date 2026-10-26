<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    /** Tạo đơn có $itemCount dòng cho $user, giá 100.000đ mỗi sản phẩm. */
    private function createOrder(User $user, int $itemCount = 2, OrderStatus $status = OrderStatus::Pending): Order
    {
        $order = Order::factory()->for($user)->create(['status' => $status, 'total' => $itemCount * 100000]);

        Product::factory()->count($itemCount)->create(['price' => 100000, 'stock' => 10])->each(
            fn (Product $product) => $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100000,
            ])
        );

        return $order;
    }

    public function test_user_sees_only_own_orders(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->createOrder($me);
        $this->createOrder($other);

        Sanctum::actingAs($me);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonCount(2, 'data.0.items');
    }

    public function test_user_can_filter_own_orders_by_status(): void
    {
        $me = User::factory()->create();
        $this->createOrder($me, 1, OrderStatus::Pending);
        $this->createOrder($me, 1, OrderStatus::Paid);
        $this->createOrder(User::factory()->create(), 1, OrderStatus::Paid); // của người khác

        Sanctum::actingAs($me);

        $this->getJson('/api/orders')->assertJsonCount(2, 'data');
        $this->getJson('/api/orders?status=paid')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
        $this->getJson('/api/orders?status=bay-ba')->assertJsonValidationErrors('status');
    }

    public function test_user_cannot_view_someone_elses_order_but_admin_can(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrder($owner);

        Sanctum::actingAs($owner);
        $this->getJson("/api/orders/{$order->id}")->assertOk();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/orders/{$order->id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson("/api/orders/{$order->id}")->assertOk()->assertJsonPath('data.user.id', $owner->id);
    }

    public function test_order_lists_have_no_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->createOrder($user, 2);

        Sanctum::actingAs($user);
        $mineFew = $this->countQueries(fn () => $this->getJson('/api/orders')->assertOk());
        Sanctum::actingAs($admin);
        $adminFew = $this->countQueries(fn () => $this->getJson('/api/admin/orders')->assertOk());

        foreach (range(1, 5) as $_) {
            $this->createOrder($user, 3);
        }

        Sanctum::actingAs($user);
        $mineMany = $this->countQueries(fn () => $this->getJson('/api/orders')->assertOk());
        Sanctum::actingAs($admin);
        $adminMany = $this->countQueries(fn () => $this->getJson('/api/admin/orders')->assertOk());

        $this->assertSame($mineFew, $mineMany);
        $this->assertSame($adminFew, $adminMany);
    }

    public function test_admin_lists_all_orders_and_filters_by_status(): void
    {
        $this->createOrder(User::factory()->create(), 1, OrderStatus::Pending);
        $this->createOrder(User::factory()->create(), 1, OrderStatus::Paid);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/orders')->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/admin/orders')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/orders?status=paid')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
        $this->getJson('/api/admin/orders?status=bay-ba')->assertJsonValidationErrors('status');
    }

    public function test_admin_moves_order_through_valid_status_flow(): void
    {
        $order = $this->createOrder(User::factory()->create());
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()->assertJsonPath('data.status', 'shipped');
    }

    public function test_rejects_invalid_status_transitions(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $pending = $this->createOrder(User::factory()->create(), 1, OrderStatus::Pending);
        $shipped = $this->createOrder(User::factory()->create(), 1, OrderStatus::Shipped);

        // Chưa thanh toán mà đòi giao hàng
        $this->patchJson("/api/admin/orders/{$pending->id}/status", ['status' => 'shipped'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        // Đơn đã giao không huỷ được
        $this->patchJson("/api/admin/orders/{$shipped->id}/status", ['status' => 'cancelled'])
            ->assertUnprocessable();
        // Giá trị không tồn tại
        $this->patchJson("/api/admin/orders/{$pending->id}/status", ['status' => 'abc'])
            ->assertJsonValidationErrors('status');

        $this->assertSame(OrderStatus::Pending, $pending->fresh()->status);
    }

    public function test_cancelling_order_restores_stock_only_once(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = Product::factory()->create(['stock' => 10]);
        $orderId = $this->postJson('/api/orders', ['items' => [['product_id' => $product->id, 'quantity' => 4]]])->json('data.id');
        $this->assertSame(6, $product->fresh()->stock);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->patchJson("/api/admin/orders/{$orderId}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(10, $product->fresh()->stock);

        // Huỷ lần 2 bị từ chối, kho không bị cộng thêm
        $this->patchJson("/api/admin/orders/{$orderId}/status", ['status' => 'cancelled'])->assertUnprocessable();
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_normal_user_cannot_change_order_status(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        Sanctum::actingAs($user);
        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'paid'])->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
