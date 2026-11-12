<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HeThongController extends Controller
{
    // Cho giao diện biết hệ thống đang chạy ở môi trường nào (local, staging, production...).
    // Ai cũng gọi được, nên CHỈ trả thông tin không bí mật: không trả địa chỉ database, khóa hay mật khẩu.
    public function thongTin(): JsonResponse
    {
        return response()->json([
            'ten_ung_dung' => config('app.name'),
            'moi_truong' => app()->environment(),
        ]);
    }
}
