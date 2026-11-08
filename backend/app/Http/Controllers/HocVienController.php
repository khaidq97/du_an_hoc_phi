<?php

namespace App\Http\Controllers;

use App\Models\HocVien;
use Illuminate\Http\JsonResponse;

class HocVienController extends Controller
{
    // Danh sách học viên để kế toán chọn khi tạo hóa đơn (chỉ kế toán, đã chặn ở route)
    public function index(): JsonResponse
    {
        $hocViens = HocVien::orderBy('ten')->get(['id', 'ten', 'email']);

        return response()->json(['du_lieu' => $hocViens]);
    }
}
