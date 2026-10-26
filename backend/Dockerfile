# Image dành cho môi trường DEV (chạy bằng `php artisan serve`).
# Bản production (php-fpm + nginx, multi-stage build) sẽ làm ở bước DevOps sau.
FROM php:8.3-cli

# git + unzip: Composer cần để tải package. pdo_mysql: Laravel kết nối MySQL.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Lấy binary composer từ image chính thức, không cần cài thủ công
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
