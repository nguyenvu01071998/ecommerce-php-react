<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // restrict: xoá user không được làm mất lịch sử đơn hàng
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('total', 12, 2);
            // Giá trị hợp lệ (pending|paid|shipped|cancelled) được ép bằng enum OrderStatus
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // Truy vấn "đơn của tôi theo trạng thái, mới nhất trước" và lọc đơn theo trạng thái ở trang admin
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
