<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
 * Health check: dùng để kiểm tra API sống và kết nối được MySQL.
 * Frontend gọi endpoint này ở bước 1 để chứng minh 3 service nói chuyện được với nhau.
 */
Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
    } catch (Throwable $e) {
        $database = 'error';
    }

    return response()->json([
        'status' => $database === 'ok' ? 'ok' : 'degraded',
        'database' => $database,
        'time' => now()->toIso8601String(),
    ], $database === 'ok' ? 200 : 503);
});

/*
 * Xác thực. register/login công khai nhưng có rate limit; logout và me cần Bearer token.
 */
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

/*
 * Catalog công khai: ai cũng xem được sản phẩm và danh mục.
 */
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

/*
 * Đơn hàng của người dùng đăng nhập. show() kiểm tra quyền bằng OrderPolicy.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/orders/{order}', [OrderController::class, 'show']);
});

/*
 * Khu vực admin: phải đăng nhập (auth:sanctum) VÀ là admin (middleware 'admin').
 * ->names('admin.x') để tên route không trùng với route công khai.
 */
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('products', AdminProductController::class)->only(['store', 'update', 'destroy'])->names('admin.products');
    Route::apiResource('categories', AdminCategoryController::class)->only(['store', 'update', 'destroy'])->names('admin.categories');

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
});
