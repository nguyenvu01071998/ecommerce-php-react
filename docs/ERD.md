# Sơ đồ ERD

GitHub tự render khối mermaid bên dưới. Trong VS Code, cài extension "Markdown Preview Mermaid Support" để xem.

```mermaid
erDiagram
    users ||--o{ orders : "đặt"
    categories ||--o{ products : "chứa"
    orders ||--|{ order_items : "gồm"
    products ||--o{ order_items : "được mua trong"

    users {
        bigint id PK
        string name
        string email UK
        string password "bcrypt"
        string role "admin | user (index)"
        timestamp created_at
    }
    categories {
        bigint id PK
        string name
        string slug UK
    }
    products {
        bigint id PK
        string name "index"
        string slug UK
        text description
        decimal price "12,2 (index)"
        int stock
        string image_url
        bigint category_id FK
    }
    orders {
        bigint id PK
        bigint user_id FK
        decimal total "12,2"
        string status "pending | paid | shipped | cancelled"
    }
    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        int quantity
        decimal price "giá lúc mua (snapshot)"
    }
```

## Quan hệ và hành vi khi xoá

| Quan hệ | Loại | Khi xoá bản ghi cha |
| --- | --- | --- |
| `categories` 1 - N `products` | hasMany / belongsTo | **restrict**: không xoá được danh mục còn sản phẩm |
| `users` 1 - N `orders` | hasMany / belongsTo | **restrict**: giữ lịch sử đơn hàng |
| `orders` 1 - N `order_items` | hasMany / belongsTo | **cascade**: xoá đơn thì xoá các dòng chi tiết |
| `products` 1 - N `order_items` | hasMany / belongsTo | **restrict**: không xoá sản phẩm đã từng được bán |
| `orders` N - N `products` | belongsToMany qua `order_items` | - |

## Index

| Bảng | Index | Phục vụ truy vấn |
| --- | --- | --- |
| users | `email` (unique), `role` | đăng nhập, lọc admin |
| categories | `slug` (unique) | lấy danh mục theo URL |
| products | `slug` (unique), `name`, `price`, `category_id` | trang chi tiết, tìm kiếm tiền tố, sắp xếp/lọc giá, lọc theo danh mục |
| orders | `(user_id, status)`, `(status, created_at)` | "đơn của tôi", danh sách đơn theo trạng thái ở trang admin |
| order_items | `(order_id, product_id)` (unique), `product_id` | lấy dòng của đơn, chặn trùng sản phẩm trong đơn |
