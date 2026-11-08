<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Middleware: lớp kiểm tra chạy TRƯỚC controller.
// Ví dụ route khai báo middleware('vai_tro:ke_toan') thì chỉ kế toán đi qua được.
class KiemTraVaiTro
{
    public function handle(Request $request, Closure $next, string ...$vaiTros)
    {
        if (! in_array($request->user()?->vai_tro, $vaiTros, true)) {
            return response()->json(['thong_bao' => 'Bạn không có quyền thực hiện việc này'], 403);
        }

        return $next($request);
    }
}
