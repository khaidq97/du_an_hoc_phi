<?php

use Illuminate\Support\Facades\Route;

// Backend này chỉ là API, không có trang web. Địa chỉ gốc trả vài dòng giới thiệu.
// Giao diện nằm ở thư mục frontend/.
Route::get('/', function () {
    return response()->json([
        'ten' => 'Hoc Phi API',
        'thong_bao' => 'Backend đang chạy. Các địa chỉ API bắt đầu bằng /api. Giao diện ở thư mục frontend.',
    ]);
});
