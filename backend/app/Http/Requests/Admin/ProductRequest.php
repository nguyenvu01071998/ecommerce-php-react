<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dùng chung cho tạo (POST) và cập nhật (PUT/PATCH) sản phẩm.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền admin đã được middleware 'admin' kiểm tra ở route
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Không gửi slug thì server tự sinh từ tên. Khi sửa, bỏ qua chính sản phẩm đang sửa lúc kiểm tra trùng.
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($this->route('product')),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            // Chỉ nhận http/https: chặn các scheme nguy hiểm như javascript: gắn vào thẻ <img src>
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }
}
