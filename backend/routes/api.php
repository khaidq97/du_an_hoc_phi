<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\HocVienController;
use Illuminate\Support\Facades\Route;

// Bảng chỉ đường (route): địa chỉ nào do controller nào xử lý.
// Mọi địa chỉ trong file này tự có tiền tố /api (ví dụ POST /api/dang-nhap).

// Ai cũng gọi được
Route::post('/dang-ky', [RegisterController::class, 'register'])->name('register');
Route::post('/dang-nhap', [LoginController::class, 'login'])->name('login');

// Phải đăng nhập (gửi kèm token) mới gọi được
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dang-xuat', [LoginController::class, 'logout'])->name('logout');

    // Kế toán thấy tất cả hóa đơn, học viên chỉ thấy hóa đơn của mình (lọc trong controller)
    Route::get('/hoa-don', [HoaDonController::class, 'index'])->name('hoa-don.index');

    // Chỉ kế toán mới gọi được các địa chỉ dưới đây
    Route::middleware('vai_tro:ke_toan')->group(function () {
        Route::get('/hoc-vien', [HocVienController::class, 'index'])->name('hoc-vien.index');
        Route::post('/hoa-don', [HoaDonController::class, 'store'])->name('hoa-don.store');
        Route::post('/hoa-don/{id}/da-dong', [HoaDonController::class, 'markPaid'])->name('hoa-don.paid');
        Route::delete('/hoa-don/{id}', [HoaDonController::class, 'destroy'])->name('hoa-don.destroy');
    });
});
