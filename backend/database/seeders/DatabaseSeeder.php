<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Danh mục => danh sách sản phẩm mẫu (5 danh mục x 6 sản phẩm = 30 sản phẩm).
     */
    private const CATALOG = [
        'Điện thoại' => [
            'iPhone 15 128GB', 'Samsung Galaxy S24', 'Xiaomi 14T', 'OPPO Reno 12', 'Realme 12 Pro', 'Google Pixel 8',
        ],
        'Laptop' => [
            'MacBook Air M3', 'Dell XPS 13', 'Lenovo ThinkPad X1 Carbon', 'ASUS Zenbook 14', 'HP Pavilion 15', 'Acer Swift Go 14',
        ],
        'Phụ kiện' => [
            'Tai nghe Sony WH-1000XM5', 'Chuột Logitech MX Master 3S', 'Bàn phím cơ Keychron K2', 'Sạc nhanh Anker 65W', 'Cáp USB-C Baseus 2m', 'Ốp lưng chống sốc',
        ],
        'Đồng hồ' => [
            'Apple Watch Series 9', 'Samsung Galaxy Watch 6', 'Garmin Forerunner 265', 'Xiaomi Smart Band 8', 'Casio G-Shock GA-2100', 'Huawei Watch GT 4',
        ],
        'Gia dụng' => [
            'Nồi chiên không dầu Philips', 'Máy hút bụi Dyson V12', 'Robot hút bụi Ecovacs', 'Máy lọc không khí Xiaomi', 'Ấm siêu tốc Sunhouse', 'Quạt tháp Panasonic',
        ],
    ];

    /**
     * Mô tả mẫu theo danh mục (tiếng Việt, không nêu thương hiệu hay cam kết cụ thể để tránh thông tin sai).
     */
    private const BLURBS = [
        'Điện thoại' => 'Màn hình sắc nét, pin dùng cả ngày, camera cho ảnh và video rõ nét trong nhiều điều kiện ánh sáng.',
        'Laptop' => 'Thiết kế mỏng nhẹ, hiệu năng ổn định cho học tập và làm việc, pin đủ dùng cho nhiều giờ liền.',
        'Phụ kiện' => 'Dễ sử dụng, kết nối ổn định, phù hợp mang theo hằng ngày cho công việc và giải trí.',
        'Đồng hồ' => 'Theo dõi giờ giấc, hoạt động và sức khoẻ, thiết kế gọn gàng dễ phối cùng nhiều phong cách.',
        'Gia dụng' => 'Tiện dụng cho căn bếp và ngôi nhà, dễ vệ sinh, vận hành êm và tiết kiệm thời gian mỗi ngày.',
    ];

    public function run(): void
    {
        // Mật khẩu mẫu 'password' CHỈ dùng cho môi trường dev (UserFactory băm bcrypt)
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Người dùng mẫu',
            'email' => 'user@example.com',
        ]);

        foreach (self::CATALOG as $categoryName => $productNames) {
            $category = Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
            ]);

            foreach ($productNames as $productName) {
                // Factory sinh giá, tồn kho, mô tả ngẫu nhiên; ghi đè tên/slug bằng dữ liệu thật
                Product::factory()->for($category)->create([
                    'name' => $productName,
                    'slug' => Str::slug($productName),
                    'description' => "{$productName}. ".self::BLURBS[$categoryName]."\n\nDữ liệu mẫu phục vụ demo.",
                    'image_url' => 'https://picsum.photos/seed/'.Str::slug($productName).'/600/600',
                ]);
            }
        }
    }
}
