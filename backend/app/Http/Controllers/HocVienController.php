<?php

namespace App\Http\Controllers;

use App\Http\Requests\HocVienRequest;
use App\Http\Resources\HoaDonResource;
use App\Models\HoaDon;
use App\Models\HocVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Quản lý hồ sơ học viên. Tất cả các địa chỉ ở đây chỉ kế toán gọi được (đã chặn ở route).
class HocVienController extends Controller
{
    // Danh sách học viên, kèm số hóa đơn và số tiền đã thu, chưa thu.
    // Dùng cho cả ô chọn học viên khi tạo hóa đơn lẫn trang Học viên.
    // ?q=chữ cần tìm: tìm theo tên hoặc email.
    public function index(Request $request): JsonResponse
    {
        $tuKhoa = trim((string) $request->query('q', ''));

        $hocViens = HocVien::query()
            ->withCount('hoaDons')
            ->withSum(['hoaDons as da_thu' => fn ($q) => $q->where('trang_thai', HoaDon::DA_DONG)], 'so_tien')
            ->withSum(['hoaDons as chua_thu' => fn ($q) => $q->where('trang_thai', HoaDon::CHUA_DONG)], 'so_tien')
            ->when($tuKhoa !== '', function ($query) use ($tuKhoa) {
                $mau = '%'.addcslashes($tuKhoa, '%_\\').'%';
                $query->where(fn ($q) => $q->where('ten', 'like', $mau)->orWhere('email', 'like', $mau));
            })
            ->orderBy('ten')
            ->get();

        return response()->json(['du_lieu' => $hocViens->map(fn (HocVien $h) => $this->dinhDang($h))]);
    }

    // Chi tiết một học viên và các hóa đơn của họ.
    public function show($id): JsonResponse
    {
        $hocVien = HocVien::withCount('hoaDons')
            ->withSum(['hoaDons as da_thu' => fn ($q) => $q->where('trang_thai', HoaDon::DA_DONG)], 'so_tien')
            ->withSum(['hoaDons as chua_thu' => fn ($q) => $q->where('trang_thai', HoaDon::CHUA_DONG)], 'so_tien')
            ->findOrFail($id);

        $hoaDons = $hocVien->hoaDons()->with('hocVien')->orderBy('id', 'desc')->get();

        return response()->json(['du_lieu' => array_merge($this->dinhDang($hocVien), [
            'hoa_don' => HoaDonResource::collection($hoaDons),
        ])]);
    }

    // Tạo hồ sơ học viên (chưa có tài khoản). Khi học viên đăng ký bằng đúng email này,
    // hệ thống tự nối tài khoản vào hồ sơ (xem RegisterController).
    public function store(HocVienRequest $request): JsonResponse
    {
        $hocVien = HocVien::create([
            'ten' => $request->ten,
            'email' => $request->email ?: null,
        ]);

        return response()->json([
            'thong_bao' => 'Đã thêm học viên',
            'du_lieu' => $this->dinhDang($hocVien->loadCount('hoaDons')),
        ], 201);
    }

    // Sửa hồ sơ học viên.
    public function update(HocVienRequest $request, $id): JsonResponse
    {
        $hocVien = HocVien::findOrFail($id);

        $hocVien->ten = $request->ten;

        // Học viên đã có tài khoản thì email của hồ sơ phải khớp email đăng nhập: không cho đổi ở đây.
        if ($hocVien->tai_khoan_id === null) {
            $hocVien->email = $request->email ?: null;
        }

        $hocVien->save();

        return response()->json([
            'thong_bao' => 'Đã cập nhật học viên',
            'du_lieu' => $this->dinhDang($hocVien->loadCount('hoaDons')),
        ]);
    }

    // Hình dạng dữ liệu học viên trả về cho frontend.
    private function dinhDang(HocVien $hocVien): array
    {
        return [
            'id' => $hocVien->id,
            'ten' => $hocVien->ten,
            'email' => $hocVien->email,
            'co_tai_khoan' => $hocVien->tai_khoan_id !== null,
            'so_hoa_don' => (int) ($hocVien->hoa_dons_count ?? 0),
            'da_thu' => (int) ($hocVien->da_thu ?? 0),
            'chua_thu' => (int) ($hocVien->chua_thu ?? 0),
        ];
    }
}
