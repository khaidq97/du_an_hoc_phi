<?php

namespace Tests\Feature;

use App\Mail\NhacNoMail;
use App\Models\HoaDon;
use App\Models\HocVien;
use App\Models\TaiKhoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Mỗi test tương ứng một YÊU CẦU CỦA KHÁCH. Đọc tên test là biết hệ thống đã làm gì.
class HoaDonTest extends TestCase
{
    use RefreshDatabase;

    private TaiKhoan $keToan;
    private TaiKhoan $taiKhoanAn;
    private HocVien $hocVienAn;
    private HocVien $hocVienBinh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keToan = TaiKhoan::create([
            'ten' => 'Ke Toan', 'email' => 'ketoan@example.com',
            'password' => 'matkhau-test-1', 'vai_tro' => TaiKhoan::VAI_TRO_KE_TOAN,
        ]);

        $this->taiKhoanAn = TaiKhoan::create([
            'ten' => 'An', 'email' => 'an@example.com',
            'password' => 'matkhau-test-2', 'vai_tro' => TaiKhoan::VAI_TRO_HOC_VIEN,
        ]);
        $this->hocVienAn = HocVien::create([
            'tai_khoan_id' => $this->taiKhoanAn->id, 'ten' => 'An', 'email' => 'an@example.com',
        ]);

        // Học viên Bình chưa có tài khoản đăng nhập
        $this->hocVienBinh = HocVien::create(['ten' => 'Binh', 'email' => 'binh@example.com']);
    }

    private function taoHoaDon(string $ma, HocVien $hocVien, string $trangThai = HoaDon::CHUA_DONG): HoaDon
    {
        return HoaDon::create([
            'ma_hoa_don' => $ma,
            'hoc_vien_id' => $hocVien->id,
            'so_tien' => 1000000,
            'trang_thai' => $trangThai,
            'ngay_thu' => $trangThai === HoaDon::DA_DONG ? now() : null,
        ]);
    }

    // R1. Mã hóa đơn không được trùng nhau
    public function test_r1_ma_hoa_don_khong_duoc_trung(): void
    {
        Sanctum::actingAs($this->keToan);
        $duLieu = ['ma_hoa_don' => 'HD100', 'hoc_vien_id' => $this->hocVienAn->id, 'so_tien' => 500000];

        $this->postJson('/api/hoa-don', $duLieu)->assertStatus(201);
        $this->postJson('/api/hoa-don', $duLieu)
            ->assertStatus(409)
            ->assertJson(['thong_bao' => 'Mã hóa đơn đã tồn tại']);

        $this->assertSame(1, HoaDon::where('ma_hoa_don', 'HD100')->count());
    }

    // R2. Hóa đơn đã đóng thì không được ghi nhận đóng lần nữa
    public function test_r2_hoa_don_da_dong_khong_ghi_nhan_dong_lan_nua(): void
    {
        Sanctum::actingAs($this->keToan);
        $hoaDon = $this->taoHoaDon('HD101', $this->hocVienAn);

        $this->postJson("/api/hoa-don/{$hoaDon->id}/da-dong")->assertOk();

        $hoaDon->refresh();
        $this->assertSame(HoaDon::DA_DONG, $hoaDon->trang_thai);
        $this->assertNotNull($hoaDon->ngay_thu);
        // Ngày thu tính theo giờ Việt Nam, không phải giờ UTC (chênh 7 tiếng, dễ lệch ngày)
        $this->assertSame('Asia/Ho_Chi_Minh', config('app.timezone'));

        $this->postJson("/api/hoa-don/{$hoaDon->id}/da-dong")
            ->assertStatus(409)
            ->assertJson(['thong_bao' => 'Hóa đơn này đã được đóng trước đó']);
    }

    // R3. Số tiền của hóa đơn phải lớn hơn 0
    public function test_r3_so_tien_phai_lon_hon_0(): void
    {
        Sanctum::actingAs($this->keToan);

        foreach ([0, -5000] as $soTien) {
            $this->postJson('/api/hoa-don', [
                'ma_hoa_don' => 'HD102', 'hoc_vien_id' => $this->hocVienAn->id, 'so_tien' => $soTien,
            ])->assertStatus(422)
                ->assertJsonValidationErrors(['so_tien'])
                ->assertJsonPath('errors.so_tien.0', 'Số tiền phải từ 1 trở lên');
        }

        $this->assertDatabaseMissing('hoa_don', ['ma_hoa_don' => 'HD102']);
    }

    // R4. Chỉ người đã đăng nhập mới xem được danh sách hóa đơn
    public function test_r4_phai_dang_nhap_moi_xem_duoc_danh_sach(): void
    {
        $this->getJson('/api/hoa-don')->assertStatus(401);
    }

    // R5. Học viên chỉ xem được hóa đơn của chính mình
    public function test_r5_hoc_vien_chi_xem_hoa_don_cua_minh(): void
    {
        $this->taoHoaDon('HD201', $this->hocVienAn);
        $this->taoHoaDon('HD202', $this->hocVienBinh);

        Sanctum::actingAs($this->taiKhoanAn);
        $this->getJson('/api/hoa-don')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ma_hoa_don', 'HD201');
    }

    public function test_r5_ke_toan_xem_duoc_tat_ca_hoa_don(): void
    {
        $this->taoHoaDon('HD201', $this->hocVienAn);
        $this->taoHoaDon('HD202', $this->hocVienBinh);

        Sanctum::actingAs($this->keToan);
        $this->getJson('/api/hoa-don')->assertOk()->assertJsonCount(2, 'data');
    }

    // R6. Hệ thống tự gửi email nhắc học viên chưa đóng tiền
    public function test_r6_gui_email_nhac_hoc_vien_chua_dong_tien(): void
    {
        Mail::fake();
        $this->taoHoaDon('HD301', $this->hocVienAn, HoaDon::CHUA_DONG);
        $this->taoHoaDon('HD302', $this->hocVienBinh, HoaDon::DA_DONG);

        $this->artisan('hoa-don:nhac-no')->assertSuccessful();

        Mail::assertSent(NhacNoMail::class, 1);
        Mail::assertSent(NhacNoMail::class, fn ($mail) => $mail->hasTo('an@example.com'));
        Mail::assertNotSent(NhacNoMail::class, fn ($mail) => $mail->hasTo('binh@example.com'));
    }

    public function test_r6_lenh_nhac_no_duoc_hen_gio_chay_moi_ngay(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('hoa-don:nhac-no')
            ->assertSuccessful();
    }

    // Hóa đơn đã đóng tiền thì không được xóa
    public function test_hoa_don_da_dong_khong_duoc_xoa(): void
    {
        Sanctum::actingAs($this->keToan);
        $hoaDon = $this->taoHoaDon('HD401', $this->hocVienAn, HoaDon::DA_DONG);

        $this->deleteJson("/api/hoa-don/{$hoaDon->id}")
            ->assertStatus(409)
            ->assertJson(['thong_bao' => 'Hóa đơn đã đóng tiền, không được xóa']);

        $this->assertDatabaseHas('hoa_don', ['id' => $hoaDon->id]);
    }

    public function test_hoa_don_chua_dong_xoa_duoc(): void
    {
        Sanctum::actingAs($this->keToan);
        $hoaDon = $this->taoHoaDon('HD402', $this->hocVienAn);

        $this->deleteJson("/api/hoa-don/{$hoaDon->id}")->assertOk();

        $this->assertDatabaseMissing('hoa_don', ['id' => $hoaDon->id]);
    }

    // Phân quyền: học viên không được tạo, thu tiền, xóa hóa đơn
    public function test_hoc_vien_khong_duoc_tao_thu_tien_hay_xoa_hoa_don(): void
    {
        $hoaDon = $this->taoHoaDon('HD501', $this->hocVienAn);
        Sanctum::actingAs($this->taiKhoanAn);

        $this->postJson('/api/hoa-don', [
            'ma_hoa_don' => 'HD502', 'hoc_vien_id' => $this->hocVienAn->id, 'so_tien' => 1000,
        ])->assertStatus(403);
        $this->postJson("/api/hoa-don/{$hoaDon->id}/da-dong")->assertStatus(403);
        $this->deleteJson("/api/hoa-don/{$hoaDon->id}")->assertStatus(403);
        $this->getJson('/api/hoc-vien')->assertStatus(403);

        $this->assertSame(HoaDon::CHUA_DONG, $hoaDon->fresh()->trang_thai);
        $this->assertDatabaseHas('hoa_don', ['id' => $hoaDon->id]);
    }

    public function test_hoa_don_khong_ton_tai_bao_khong_tim_thay(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->deleteJson('/api/hoa-don/99999')
            ->assertStatus(404)
            ->assertJson(['thong_bao' => 'Không tìm thấy dữ liệu']);
    }
}
