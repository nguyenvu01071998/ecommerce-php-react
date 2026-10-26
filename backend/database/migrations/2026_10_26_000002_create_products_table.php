<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // tìm kiếm theo tiền tố tên (LIKE 'abc%') dùng được index
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Tiền dùng DECIMAL, không dùng FLOAT để tránh sai số làm tròn
            $table->decimal('price', 12, 2)->index(); // index để lọc/sắp xếp theo giá
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_url')->nullable();
            // restrict: không cho xoá danh mục còn sản phẩm. foreignId() tự tạo index cho category_id
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
