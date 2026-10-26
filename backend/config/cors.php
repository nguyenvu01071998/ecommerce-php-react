<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Chỉ cho phép đúng origin của frontend (không dùng '*'). Có thể khai báo nhiều origin, cách nhau bằng dấu phẩy.
    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    // Liệt kê rõ header thay vì '*': trình duyệt không coi '*' là bao gồm Authorization
    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => ['Retry-After'], // để frontend đọc được thời gian chờ khi bị rate limit

    'max_age' => 600, // trình duyệt cache kết quả preflight (OPTIONS) 10 phút

    // Dùng Bearer token trong header, không dùng cookie nên không cần gửi credentials
    'supports_credentials' => false,

    'supports_credentials' => false,

];
