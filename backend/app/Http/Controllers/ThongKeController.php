<?php

namespace App\Http\Controllers;

use App\Http\Resources\HoaDonResource;
use App\Models\HoaDon;
use App\Models\HocVien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThongKeController extends Controller
{
    // Số tháng hiện trong biểu đồ
    const SO_THANG = 6;

    // Số liệu cho trang Tổng quan.
    // Kế toán thấy số liệu của cả trung tâm; học viên chỉ thấy số liệu của chính mình.
    public function tongQuan(Request $request): JsonResponse
    {
        $taiKhoan = $request->user();

        $hoaDons = HoaDon::with('hocVien')
            ->when(! $taiKhoan->laKeToan(), function ($query) use ($taiKhoan) {
                $query->whereHas('hocVien', fn ($q) => $q->where('tai_khoan_id', $taiKhoan->id));
            })
            ->orderBy('id', 'desc')
            ->get();

        $daDong = $hoaDons->filter(fn (HoaDon $h) => $h->daDong());
        $chuaDong = $hoaDons->reject(fn (HoaDon $h) => $h->daDong());

        $tongTien = (int) $hoaDons->sum('so_tien');
        $daThu = (int) $daDong->sum('so_tien');

        return response()->json(['du_lieu' => [
            'tong_hoa_don' => $hoaDons->count(),
            'so_da_dong' => $daDong->count(),
            'so_chua_dong' => $chuaDong->count(),
            'tong_tien' => $tongTien,
            'da_thu' => $daThu,
            'chua_thu' => (int) $chuaDong->sum('so_tien'),
            // Tỷ lệ đã thu theo số tiền, làm tròn phần trăm
            'ty_le_thu' => $tongTien > 0 ? (int) round($daThu * 100 / $tongTien) : 0,
            // Học viên không cần biết tổng số học viên của trung tâm
            'so_hoc_vien' => $taiKhoan->laKeToan() ? HocVien::count() : null,
            'theo_thang' => $this->theoThang($hoaDons),
            'gan_day' => HoaDonResource::collection($hoaDons->take(5)),
        ]]);
    }

    // Gom theo tháng trong PHP (không dùng hàm ngày của MySQL) để chạy giống nhau trên mọi database.
    // da_thu: tiền thu được trong tháng (theo ngày thu). da_lap: tiền của các hóa đơn lập trong tháng.
    private function theoThang($hoaDons): array
    {
        $ketQua = [];
        $homNay = Carbon::now()->startOfMonth();

        for ($i = self::SO_THANG - 1; $i >= 0; $i--) {
            $thang = $homNay->copy()->subMonths($i);
            $khoa = $thang->format('Y-m');

            $ketQua[$khoa] = [
                'thang' => $khoa,
                'nhan' => $thang->format('m/Y'),
                'da_thu' => 0,
                'da_lap' => 0,
            ];
        }

        foreach ($hoaDons as $hoaDon) {
            $khoaTao = $hoaDon->created_at?->format('Y-m');
            if ($khoaTao !== null && isset($ketQua[$khoaTao])) {
                $ketQua[$khoaTao]['da_lap'] += $hoaDon->so_tien;
            }

            $khoaThu = $hoaDon->ngay_thu?->format('Y-m');
            if ($khoaThu !== null && isset($ketQua[$khoaThu])) {
                $ketQua[$khoaThu]['da_thu'] += $hoaDon->so_tien;
            }
        }

        return array_values($ketQua);
    }
}
