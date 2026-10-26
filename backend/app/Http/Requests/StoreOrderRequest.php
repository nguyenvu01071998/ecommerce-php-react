<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã yêu cầu đăng nhập bằng auth:sanctum ở route
    }

    /**
     * Client CHỈ được gửi product_id và quantity. Các trường khác (price, total...) bị bỏ qua hoàn toàn
     * vì controller chỉ lấy dữ liệu qua validated().
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            // distinct: mỗi sản phẩm chỉ xuất hiện 1 lần, số lượng gộp ở phía client
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
