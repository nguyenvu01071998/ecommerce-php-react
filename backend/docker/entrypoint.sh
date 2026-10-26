#!/bin/sh
# Chạy mỗi lần container backend khởi động: chuẩn bị Laravel rồi mới chạy lệnh chính.
set -e

# Lần đầu: tạo .env từ file mẫu (.env không bao giờ được commit)
if [ ! -f .env ]; then
    cp .env.example .env
fi

# vendor/ nằm trong named volume nên không đồng bộ qua OneDrive/Windows (nhanh hơn nhiều).
# Lệnh này gần như tức thì nếu package đã đủ.
composer install --no-interaction --prefer-dist

# Sinh APP_KEY nếu chưa có (dùng để mã hoá session/cookie)
if ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force
fi

exec "$@"
