<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Tạo đơn hàng từ danh sách [['product_id' => 1, 'quantity' => 2], ...].
     *
     * Mọi thứ nằm trong MỘT transaction: có lỗi ở bất kỳ sản phẩm nào thì toàn bộ được rollback
     * (không tạo đơn, không trừ kho sản phẩm nào).
     *
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function place(User $user, array $items): Order
    {
        return DB::transaction(function () use ($user, $items) {
            // Khoá các dòng sản phẩm (SELECT ... FOR UPDATE) cho tới hết transaction: 2 người cùng mua món
            // cuối cùng sẽ phải xếp hàng, người sau thấy stock đã giảm -> không bị bán lố (overselling).
            // Luôn khoá theo thứ tự id tăng dần để 2 transaction không khoá chéo nhau gây deadlock.
            $products = Product::query()
                ->whereIn('id', array_column($items, 'product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalCents = 0;
            $lines = [];

            foreach ($items as $index => $item) {
                $product = $products[$item['product_id']];
                $quantity = $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} trong kho.",
                    ]);
                }

                // GIÁ LẤY TỪ DATABASE, không bao giờ tin giá client gửi lên.
                // Tính bằng số nguyên "xu" (price x 100) để tránh sai số số thực.
                $totalCents += $this->toCents($product->price) * $quantity;
                $lines[] = [$product, $quantity];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total' => number_format($totalCents / 100, 2, '.', ''),
                'status' => OrderStatus::Pending,
            ]);

            foreach ($lines as [$product, $quantity]) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price, // snapshot giá tại thời điểm mua
                ]);
                $product->decrement('stock', $quantity);
            }

            return $order->load('items.product');
        });
    }

    /**
     * Đổi trạng thái đơn theo máy trạng thái trong OrderStatus. Huỷ đơn thì hoàn lại tồn kho.
     */
    public function changeStatus(Order $order, OrderStatus $next): Order
    {
        return DB::transaction(function () use ($order, $next) {
            // Đọc lại đơn với khoá để 2 admin cùng bấm "huỷ" không hoàn kho 2 lần
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->status->canTransitionTo($next)) {
                throw ValidationException::withMessages([
                    'status' => "Không thể chuyển đơn từ \"{$order->status->value}\" sang \"{$next->value}\".",
                ]);
            }

            if ($next === OrderStatus::Cancelled) {
                foreach ($order->items()->orderBy('product_id')->get() as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => $next]);

            return $order;
        });
    }

    private function toCents(string $price): int
    {
        return (int) round(((float) $price) * 100);
    }
}
