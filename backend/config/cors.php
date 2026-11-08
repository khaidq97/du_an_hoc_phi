<?php

// CORS: trình duyệt chỉ cho trang web ở địa chỉ A gọi API ở địa chỉ B nếu B cho phép.
// Frontend chạy ở cổng 8080, backend ở cổng 8000 nên backend phải cho phép địa chỉ frontend.
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // FRONTEND_URL lấy từ .env; có thể liệt kê nhiều địa chỉ, ngăn cách bằng dấu phẩy.
    'allowed_origins' => array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:8080'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
