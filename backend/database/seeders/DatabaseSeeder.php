<?php

namespace Database\Seeders;

use App\Models\HoaDon;
use App\Models\HocVien;
use App\Models\TaiKhoan;
use Illuminate\Database\Seeder;

// Dữ liệu mẫu để học viên có cái xem ngay. TOÀN BỘ LÀ DỮ LIỆU GIẢ.
// Chạy lại nhiều lần không tạo trùng (dùng firstOrCreate).
class DatabaseSeeder extends Seeder
{
    // Mật khẩu chung của các tài khoản demo. Chỉ dùng cho máy học, không dùng ở môi trường thật.
    private const MAT_KHAU_DEMO = 'Demo@12345';

    public function run(): void
    {
        // Tài khoản kế toán
        TaiKhoan::firstOrCreate(
            ['email' => 'ketoan@example.com'],
            ['ten' => 'Kế toán Demo', 'password' => self::MAT_KHAU_DEMO, 'vai_tro' => TaiKhoan::VAI_TRO_KE_TOAN]
        );

        // Hai học viên đã có tài khoản
        $an = $this->taoHocVienCoTaiKhoan('Nguyễn Văn An', 'an@example.com');
        $binh = $this->taoHocVienCoTaiKhoan('Trần Thị Bình', 'binh@example.com');

        // Một học viên kế toán đã tạo hồ sơ nhưng chưa đăng ký tài khoản
        $cuong = HocVien::firstOrCreate(
            ['email' => 'cuong@example.com'],
            ['ten' => 'Lê Văn Cường']
        );

        // Hóa đơn mẫu: có cả đã đóng và chưa đóng để thử từng quy tắc
        $this->taoHoaDon('HD001', $an, 2000000, HoaDon::DA_DONG, now()->subDays(5));
        $this->taoHoaDon('HD002', $an, 1500000, HoaDon::CHUA_DONG);
        $this->taoHoaDon('HD003', $binh, 2000000, HoaDon::CHUA_DONG);
        $this->taoHoaDon('HD004', $binh, 1800000, HoaDon::DA_DONG, now()->subDays(2));
        $this->taoHoaDon('HD005', $cuong, 2000000, HoaDon::CHUA_DONG);
    }

    private function taoHocVienCoTaiKhoan(string $ten, string $email): HocVien
    {
        $taiKhoan = TaiKhoan::firstOrCreate(
            ['email' => $email],
            ['ten' => $ten, 'password' => self::MAT_KHAU_DEMO, 'vai_tro' => TaiKhoan::VAI_TRO_HOC_VIEN]
        );

        return HocVien::firstOrCreate(
            ['tai_khoan_id' => $taiKhoan->id],
            ['ten' => $ten, 'email' => $email]
        );
    }

    private function taoHoaDon(string $ma, HocVien $hocVien, int $soTien, string $trangThai, $ngayThu = null): void
    {
        HoaDon::firstOrCreate(
            ['ma_hoa_don' => $ma],
            [
                'hoc_vien_id' => $hocVien->id,
                'so_tien' => $soTien,
                'trang_thai' => $trangThai,
                'ngay_thu' => $ngayThu,
            ]
        );
    }
}
