<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use App\Support\ThongBaoLoi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], ThongBaoLoi::thongBao(), ThongBaoLoi::tenTruong());

        $taiKhoan = TaiKhoan::where('email', $data['email'])->first();

        // Sai email hoặc sai mật khẩu đều báo chung một câu, không tiết lộ email có tồn tại hay không.
        if (! $taiKhoan || ! Hash::check($data['password'], $taiKhoan->password)) {
            return response()->json(['thong_bao' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        // Token: "thẻ ra vào". Frontend giữ thẻ này và gửi kèm mọi yêu cầu sau đó.
        $token = $taiKhoan->createToken('hoc-phi-web')->plainTextToken;

        return response()->json([
            'thong_bao' => 'Đăng nhập thành công',
            'token' => $token,
            'tai_khoan' => [
                'ten' => $taiKhoan->ten,
                'email' => $taiKhoan->email,
                'vai_tro' => $taiKhoan->vai_tro,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Hủy thẻ đang dùng: thẻ này không còn vào được nữa.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['thong_bao' => 'Đã đăng xuất']);
    }
}
