<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\HeThongController;
use App\Http\Controllers\HocVienController;
use App\Http\Controllers\ThongKeController;
use Illuminate\Support\Facades\Route;

// Bảng chỉ đường (route): địa chỉ nào do controller nào xử lý.
// Mọi địa chỉ trong file này tự có tiền tố /api (ví dụ POST /api/dang-nhap).

// Ai cũng gọi được
Route::post('/dang-ky', [RegisterController::class, 'register'])->name('register');
Route::post('/dang-nhap', [LoginController::class, 'login'])->name('login');

// Cho giao diện biết đang chạy ở môi trường nào (chỉ trả thông tin không bí mật)
Route::get('/he-thong', [HeThongController::class, 'thongTin'])->name('he-thong');

// Phải đăng nhập (gửi kèm token) mới gọi được
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dang-xuat', [LoginController::class, 'logout'])->name('logout');

    // Số liệu trang Tổng quan: kế toán thấy cả trung tâm, học viên chỉ thấy của mình
    Route::get('/thong-ke', [ThongKeController::class, 'tongQuan'])->name('thong-ke');

    // Kế toán thấy tất cả hóa đơn, học viên chỉ thấy hóa đơn của mình (lọc trong controller)
    Route::get('/hoa-don', [HoaDonController::class, 'index'])->name('hoa-don.index');

    // Chỉ kế toán mới gọi được các địa chỉ dưới đây
    Route::middleware('vai_tro:ke_toan')->group(function () {
        Route::get('/hoc-vien', [HocVienController::class, 'index'])->name('hoc-vien.index');
        Route::post('/hoc-vien', [HocVienController::class, 'store'])->name('hoc-vien.store');
        Route::get('/hoc-vien/{id}', [HocVienController::class, 'show'])->name('hoc-vien.show');
        Route::put('/hoc-vien/{id}', [HocVienController::class, 'update'])->name('hoc-vien.update');
        Route::get('/hoa-don/xuat-csv', [HoaDonController::class, 'xuatCsv'])->name('hoa-don.xuat-csv');
        Route::post('/hoa-don', [HoaDonController::class, 'store'])->name('hoa-don.store');
        Route::post('/hoa-don/{id}/da-dong', [HoaDonController::class, 'markPaid'])->name('hoa-don.paid');
        Route::delete('/hoa-don/{id}', [HoaDonController::class, 'destroy'])->name('hoa-don.destroy');
    });
});
