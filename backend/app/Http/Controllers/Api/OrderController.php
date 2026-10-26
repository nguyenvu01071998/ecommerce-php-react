<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * GET /api/orders - đơn hàng CỦA TÔI. Lọc theo user ở đây nên không thể xem đơn người khác.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $orders = $request->user()
            ->orders()
            ->with('items.product') // 2 query cho cả trang (items + products) thay vì 1 + N + N*M
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order); // OrderPolicy: chủ đơn hoặc admin

        return new OrderResource($order->load('items.product', 'user'));
    }

    public function store(StoreOrderRequest $request): OrderResource
    {
        $order = $this->orders->place($request->user(), $request->validated('items'));

        return new OrderResource($order); // tự trả 201
    }
}
