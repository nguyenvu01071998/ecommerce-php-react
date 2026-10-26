<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function store(CategoryRequest $request): CategoryResource
    {
        $data = $request->validated();
        $data['slug'] ??= Slug::unique(Category::class, $data['name']);

        return new CategoryResource(Category::create($data)); // tự trả 201
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Không thể xoá danh mục còn sản phẩm. Hãy chuyển hoặc xoá sản phẩm trước.',
            ], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
