<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); // xoá đơn thì xoá luôn dòng chi tiết
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            // Lưu giá TẠI THỜI ĐIỂM MUA (snapshot): sau này sản phẩm đổi giá thì đơn cũ không bị sai
            $table->decimal('price', 12, 2);
            $table->timestamps();

            // Mỗi sản phẩm chỉ xuất hiện 1 dòng trong 1 đơn (cũng là index cho order_id)
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
