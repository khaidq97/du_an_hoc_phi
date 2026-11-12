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

        // Lịch sử các tháng trước, để trang Tổng quan có biểu đồ nhiều tháng.
        // Hai học viên này chỉ có hồ sơ (chưa đăng ký tài khoản), nên không đụng tới tài khoản demo ở trên.
        $ha = HocVien::firstOrCreate(['email' => 'ha@example.com'], ['ten' => 'Phạm Thu Hà']);
        $khoa = HocVien::firstOrCreate(['email' => 'khoa@example.com'], ['ten' => 'Đỗ Minh Khoa']);

        $this->taoHoaDon('HD006', $ha, 1800000, HoaDon::DA_DONG, now()->subMonths(4)->addDays(3), now()->subMonths(4));
        $this->taoHoaDon('HD007', $khoa, 2200000, HoaDon::DA_DONG, now()->subMonths(4)->addDays(9), now()->subMonths(4));
        $this->taoHoaDon('HD008', $ha, 2000000, HoaDon::DA_DONG, now()->subMonths(3)->addDays(2), now()->subMonths(3));
        $this->taoHoaDon('HD009', $khoa, 2500000, HoaDon::DA_DONG, now()->subMonths(3)->addDays(12), now()->subMonths(3));
        $this->taoHoaDon('HD010', $ha, 2100000, HoaDon::DA_DONG, now()->subMonths(2)->addDays(4), now()->subMonths(2));
        $this->taoHoaDon('HD011', $khoa, 1900000, HoaDon::CHUA_DONG, null, now()->subMonths(2));
        $this->taoHoaDon('HD012', $ha, 2300000, HoaDon::DA_DONG, now()->subMonth()->addDays(5), now()->subMonth());
        $this->taoHoaDon('HD013', $khoa, 2000000, HoaDon::CHUA_DONG, null, now()->subMonth());
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

    // $ngayTao để trống thì hóa đơn có ngày tạo là hôm nay. unguarded: cho phép gán created_at (dữ liệu mẫu).
    private function taoHoaDon(string $ma, HocVien $hocVien, int $soTien, string $trangThai, $ngayThu = null, $ngayTao = null): void
    {
        HoaDon::unguarded(fn () => HoaDon::firstOrCreate(
            ['ma_hoa_don' => $ma],
            array_filter([
                'hoc_vien_id' => $hocVien->id,
                'so_tien' => $soTien,
                'trang_thai' => $trangThai,
                'ngay_thu' => $ngayThu,
                'created_at' => $ngayTao,
            ], fn ($giaTri) => $giaTri !== null)
        ));
    }
}
