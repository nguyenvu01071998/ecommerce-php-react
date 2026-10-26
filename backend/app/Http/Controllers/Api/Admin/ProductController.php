<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function store(ProductRequest $request): ProductResource
    {
        $data = $request->validated();
        $data['slug'] ??= Slug::unique(Product::class, $data['name']);

        return new ProductResource(Product::create($data)->load('category')); // tự trả 201
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        // Không gửi slug thì giữ nguyên slug cũ (đổi tên không làm hỏng link đã chia sẻ)
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product): JsonResponse
    {
        // Sản phẩm đã từng được bán thì giữ lại để bảo toàn lịch sử đơn hàng
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Không thể xoá sản phẩm đã có trong đơn hàng. Hãy đặt tồn kho về 0 thay thế.',
            ], 409);
        }

        $product->delete();

        return response()->json(null, 204);
    }
}
