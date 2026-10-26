<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * GET /api/products?search=&category=&sort=price_asc&per_page=12&page=1
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $products = Product::query()
            ->with('category') // eager loading: 1 query lấy danh mục cho cả trang thay vì 1 query/sản phẩm (N+1)
            ->withSoldCount()
            ->search($filters['search'] ?? null)
            ->inCategory($filters['category'] ?? null)
            ->sortBy($filters['sort'] ?? null)
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString(); // giữ search/sort/category trong link phân trang

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category')->loadSoldCount());
    }
}
