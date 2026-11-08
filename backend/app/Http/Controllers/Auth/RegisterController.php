<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HocVien;
use App\Models\TaiKhoan;
use App\Support\ThongBaoLoi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    // Học viên tự đăng ký tài khoản.
    public function register(Request $request): JsonResponse
    {
        // 1. validate: kiểm tra dữ liệu nhập vào
        $data = $request->validate([
            'ten' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8',
        ], ThongBaoLoi::thongBao(), ThongBaoLoi::tenTruong());

        // 2. Email đã đăng ký trước đó thì báo lỗi
        if (TaiKhoan::where('email', $data['email'])->exists()) {
            return response()->json(['thong_bao' => 'Email đã được đăng ký'], 409);
        }

        // 3. Lưu mới (tài khoản và hồ sơ học viên cùng thành công hoặc cùng hủy)
        DB::transaction(function () use ($data) {
            $taiKhoan = TaiKhoan::create([
                'ten' => $data['ten'],
                'email' => $data['email'],
                'password' => $data['password'],
                // Người tự đăng ký luôn là học viên, không nhận vai trò từ dữ liệu gửi lên.
                'vai_tro' => TaiKhoan::VAI_TRO_HOC_VIEN,
            ]);

            // Kế toán có thể đã tạo sẵn hồ sơ học viên theo email này: nối vào, không tạo trùng.
            $hocVien = HocVien::where('email', $data['email'])->whereNull('tai_khoan_id')->first();

            if ($hocVien) {
                $hocVien->update(['tai_khoan_id' => $taiKhoan->id]);
            } else {
                HocVien::create([
                    'tai_khoan_id' => $taiKhoan->id,
                    'ten' => $data['ten'],
                    'email' => $data['email'],
                ]);
            }
        });

        return response()->json(['thong_bao' => 'Tài khoản đã được đăng ký thành công'], 201);
    }
}
