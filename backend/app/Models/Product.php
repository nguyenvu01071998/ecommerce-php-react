<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image_url',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2', // trả về chuỗi "199000.00", tránh sai số float
            'stock' => 'integer',
        ];
    }

    /**
     * Tìm theo tên (chứa chuỗi). Escape ký tự đại diện % và _ để người dùng gõ "50%" không khớp mọi thứ.
     * Dùng ESCAPE '!' vì cú pháp này chạy giống nhau trên MySQL và SQLite. Giá trị vẫn được bind qua tham số.
     * Lưu ý: LIKE '%abc%' không dùng được index của cột name (muốn nhanh hơn khi dữ liệu lớn: FULLTEXT).
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term));
        $query->whereRaw("name like ? escape '!'", ["%{$escaped}%"]);
    }

    public function scopeInCategory(Builder $query, ?string $categorySlug): void
    {
        if (blank($categorySlug)) {
            return;
        }

        $query->whereIn('category_id', Category::select('id')->where('slug', $categorySlug));
    }

    /**
     * Thêm cột sold_count = tổng số lượng đã bán, không tính đơn đã huỷ.
     * Dùng subquery SUM trong cùng câu SQL nên không gây N+1 và cho phép ORDER BY sold_count.
     */
    public function scopeWithSoldCount(Builder $query): void
    {
        $query->withSum(self::soldCountRelation(), 'quantity');
    }

    /** Giống scopeWithSoldCount nhưng dùng cho model đã có sẵn (trang chi tiết). */
    public function loadSoldCount(): static
    {
        return $this->loadSum(self::soldCountRelation(), 'quantity');
    }

    /** @return array<string, \Closure> */
    private static function soldCountRelation(): array
    {
        return [
            'orderItems as sold_count' => fn ($items) => $items->whereHas(
                'order',
                fn ($order) => $order->where('status', '!=', OrderStatus::Cancelled->value)
            ),
        ];
    }

    /**
     * Chỉ nhận giá trị trong danh sách cho phép (whitelist): không bao giờ đưa chuỗi của client vào orderBy().
     * Luôn thêm id làm tiêu chí phụ để thứ tự ổn định giữa các trang khi nhiều sản phẩm cùng giá.
     */
    public function scopeSortBy(Builder $query, ?string $sort): void
    {
        match ($sort) {
            // Cần scopeWithSoldCount() đã được gọi trước đó để có cột sold_count
            'best_selling' => $query->orderByDesc('sold_count')->orderByDesc('id'),
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            default => $query->orderByDesc('id'), // mới nhất trước
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
