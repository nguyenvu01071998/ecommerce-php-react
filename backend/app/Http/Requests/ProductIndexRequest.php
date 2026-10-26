<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'], // slug danh mục
            'sort' => ['nullable', 'in:newest,best_selling,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'], // chặn client xin 1 triệu dòng/lần
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
