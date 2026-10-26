<?php

namespace Tests\Feature\Catalog;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_products_with_pagination_meta_and_category(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'price', 'stock', 'category' => ['id', 'name', 'slug']]], 'links', 'meta']);
    }

    public function test_searches_by_name(): void
    {
        Product::factory()->create(['name' => 'iPhone 15', 'slug' => 'iphone-15']);
        Product::factory()->create(['name' => 'Samsung S24', 'slug' => 'samsung-s24']);

        $this->getJson('/api/products?search=iphone')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'iPhone 15');
    }

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        Product::factory()->create(['name' => 'Giảm 50% hôm nay', 'slug' => 'a']);
        Product::factory()->create(['name' => 'Sản phẩm thường', 'slug' => 'b']);

        // Nếu không escape, "%" sẽ khớp mọi sản phẩm
        $this->getJson('/api/products?search=%25')->assertJsonCount(1, 'data');
        $this->getJson('/api/products?search=_')->assertJsonCount(0, 'data');
    }

    public function test_filters_by_category_slug(): void
    {
        $phones = Category::factory()->create(['name' => 'Điện thoại', 'slug' => 'dien-thoai']);
        $laptops = Category::factory()->create(['name' => 'Laptop', 'slug' => 'laptop']);
        Product::factory()->count(2)->for($phones)->create();
        Product::factory()->count(3)->for($laptops)->create();

        $this->getJson('/api/products?category=dien-thoai')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?category=khong-ton-tai')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_sorts_by_price(): void
    {
        Product::factory()->create(['price' => 300000]);
        Product::factory()->create(['price' => 100000]);
        Product::factory()->create(['price' => 200000]);

        $this->getJson('/api/products?sort=price_asc')
            ->assertJsonPath('data.0.price', '100000.00')
            ->assertJsonPath('data.2.price', '300000.00');

        $this->getJson('/api/products?sort=price_desc')
            ->assertJsonPath('data.0.price', '300000.00');
    }

    public function test_sold_count_ignores_cancelled_orders_and_sorts_best_selling(): void
    {
        $popular = Product::factory()->create(['name' => 'Bán chạy']);
        $quiet = Product::factory()->create(['name' => 'Ít người mua']);
        $cancelledOnly = Product::factory()->create(['name' => 'Chỉ có đơn huỷ']);
        Product::factory()->create(['name' => 'Chưa ai mua']);

        $this->orderOf($popular, 5, OrderStatus::Paid);
        $this->orderOf($popular, 2, OrderStatus::Shipped);
        $this->orderOf($quiet, 1, OrderStatus::Pending);
        $this->orderOf($cancelledOnly, 9, OrderStatus::Cancelled); // đơn huỷ không được tính

        $response = $this->getJson('/api/products?sort=best_selling')->assertOk();

        // So sánh giá trị (không phụ thuộc thứ tự giữa các sản phẩm cùng 0 lượt bán)
        $this->assertEquals(
            ['Bán chạy' => 7, 'Ít người mua' => 1, 'Chỉ có đơn huỷ' => 0, 'Chưa ai mua' => 0],
            collect($response->json('data'))->pluck('sold_count', 'name')->all()
        );
        $this->assertSame('Bán chạy', $response->json('data.0.name'));
        $this->assertSame('Ít người mua', $response->json('data.1.name'));

        $this->getJson("/api/products/{$popular->id}")->assertJsonPath('data.sold_count', 7);
    }

    private function orderOf(Product $product, int $quantity, OrderStatus $status): void
    {
        $order = Order::factory()->create(['status' => $status]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => $quantity, 'price' => $product->price]);
    }

    public function test_rejects_invalid_query_params(): void
    {
        // sort nằm ngoài whitelist -> 422, không bao giờ đưa vào ORDER BY
        $this->getJson('/api/products?sort=price;drop table products')->assertUnprocessable()->assertJsonValidationErrors('sort');
        $this->getJson('/api/products?per_page=1000')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_pagination_links_keep_filters(): void
    {
        Product::factory()->count(5)->create();

        $next = $this->getJson('/api/products?per_page=2&sort=price_asc')->json('links.next');

        $this->assertStringContainsString('sort=price_asc', $next);
        $this->assertStringContainsString('per_page=2', $next);
    }

    public function test_shows_single_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.category.id', $product->category_id);

        $this->getJson('/api/products/999999')->assertNotFound();
    }

    public function test_listing_has_no_n_plus_one_queries(): void
    {
        Product::factory()->count(2)->create();
        $few = $this->countQueries(fn () => $this->getJson('/api/products')->assertOk());

        Product::factory()->count(10)->create();
        $many = $this->countQueries(fn () => $this->getJson('/api/products')->assertOk());

        $this->assertSame($few, $many, 'Số query phải không đổi khi số sản phẩm tăng');
    }

    public function test_lists_categories_with_product_count(): void
    {
        $category = Category::factory()->create(['name' => 'Laptop', 'slug' => 'laptop']);
        Product::factory()->count(3)->for($category)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'laptop')
            ->assertJsonPath('data.0.products_count', 3);
    }
}
