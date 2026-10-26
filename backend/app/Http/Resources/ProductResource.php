<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price, // chuỗi "199000.00", không dùng float để tránh sai số
            'stock' => $this->stock,
            'image_url' => $this->image_url,
            // Chỉ có khi controller đã tính (danh sách và chi tiết). Không có đơn nào thì trả 0 thay vì null
            'sold_count' => $this->when(
                array_key_exists('sold_count', $this->resource->getAttributes()),
                fn () => (int) $this->sold_count
            ),
            // whenLoaded: chỉ xuất hiện khi controller đã eager load, nếu không sẽ KHÔNG tự query (chống N+1 ngầm)
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
        ];
    }
}
