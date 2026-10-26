<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    private function productPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tai nghe Sony WH-1000XM5',
            'description' => 'Chống ồn chủ động',
            'price' => 7990000,
            'stock' => 20,
            'image_url' => 'https://example.com/a.jpg',
            'category_id' => $category->id,
        ], $overrides);
    }

    public function test_guest_and_normal_user_cannot_use_admin_endpoints(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/admin/products', $this->productPayload($category))->assertUnauthorized();
        $this->postJson('/api/admin/categories', ['name' => 'X'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/admin/products', $this->productPayload($category))->assertForbidden();
        $this->postJson('/api/admin/categories', ['name' => 'X'])->assertForbidden();
        $this->deleteJson("/api/admin/categories/{$category->id}")->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_admin_can_create_product_with_auto_slug(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();

        $this->postJson('/api/admin/products', $this->productPayload($category))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tai-nghe-sony-wh-1000xm5')
            ->assertJsonPath('data.category.id', $category->id);

        // Trùng tên -> slug tự thêm hậu tố, không lỗi
        $this->postJson('/api/admin/products', $this->productPayload($category))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tai-nghe-sony-wh-1000xm5-2');
    }

    public function test_product_validation(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();

        $this->postJson('/api/admin/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'stock', 'category_id']);

        $this->postJson('/api/admin/products', $this->productPayload($category, ['price' => -1, 'stock' => -5]))
            ->assertJsonValidationErrors(['price', 'stock']);

        $this->postJson('/api/admin/products', $this->productPayload($category, ['category_id' => 9999]))
            ->assertJsonValidationErrors('category_id');

        // Scheme nguy hiểm không được lưu vào image_url
        $this->postJson('/api/admin/products', $this->productPayload($category, ['image_url' => 'javascript:alert(1)']))
            ->assertJsonValidationErrors('image_url');
    }

    public function test_admin_can_update_product_and_keep_own_slug(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['slug' => 'giu-nguyen']);

        $this->putJson("/api/admin/products/{$product->id}", $this->productPayload($product->category, [
            'name' => 'Tên mới',
            'slug' => 'giu-nguyen', // trùng chính nó thì hợp lệ
            'price' => 123000,
        ]))->assertOk()->assertJsonPath('data.name', 'Tên mới');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Tên mới', 'price' => 123000]);

        // Trùng slug của sản phẩm KHÁC thì bị từ chối
        $other = Product::factory()->create(['slug' => 'da-co']);
        $this->putJson("/api/admin/products/{$product->id}", $this->productPayload($product->category, ['slug' => 'da-co']))
            ->assertJsonValidationErrors('slug');
    }

    public function test_admin_can_delete_product_without_orders_but_not_sold_product(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $free = Product::factory()->create();
        $sold = Product::factory()->create();
        OrderItem::create([
            'order_id' => Order::factory()->create()->id,
            'product_id' => $sold->id,
            'quantity' => 1,
            'price' => $sold->price,
        ]);

        $this->deleteJson("/api/admin/products/{$free->id}")->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $free->id]);

        $this->deleteJson("/api/admin/products/{$sold->id}")->assertStatus(409);
        $this->assertDatabaseHas('products', ['id' => $sold->id]);
    }

    public function test_admin_category_crud(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $id = $this->postJson('/api/admin/categories', ['name' => 'Máy ảnh'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'may-anh')
            ->json('data.id');

        $this->postJson('/api/admin/categories', ['name' => 'Máy ảnh'])->assertJsonValidationErrors('name');

        $this->putJson("/api/admin/categories/{$id}", ['name' => 'Máy ảnh số'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Máy ảnh số');

        $this->deleteJson("/api/admin/categories/{$id}")->assertNoContent();
    }

    public function test_cannot_delete_category_that_still_has_products(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->deleteJson("/api/admin/categories/{$category->id}")->assertStatus(409);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
