<?php

namespace Tests\Feature;

use App\Models\HoaDon;
use App\Models\HocVien;
use App\Models\TaiKhoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Các chức năng thêm sau bản đầu: môi trường, thống kê, quản lý học viên, tìm kiếm và lọc, tải CSV.
class TongQuanHocVienLocTest extends TestCase
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
            'tai_khoan_id' => $this->taiKhoanAn->id, 'ten' => 'Nguyen An', 'email' => 'an@example.com',
        ]);
        $this->hocVienBinh = HocVien::create(['ten' => 'Tran Binh', 'email' => 'binh@example.com']);
    }

    private function taoHoaDon(string $ma, HocVien $hocVien, int $soTien, string $trangThai = HoaDon::CHUA_DONG): HoaDon
    {
        return HoaDon::create([
            'ma_hoa_don' => $ma,
            'hoc_vien_id' => $hocVien->id,
            'so_tien' => $soTien,
            'trang_thai' => $trangThai,
            'ngay_thu' => $trangThai === HoaDon::DA_DONG ? now() : null,
        ]);
    }

    // ---------- Môi trường ----------

    public function test_he_thong_cho_biet_moi_truong_va_khong_lo_bi_mat(): void
    {
        $phanHoi = $this->getJson('/api/he-thong')->assertOk();

        $phanHoi->assertJsonStructure(['ten_ung_dung', 'moi_truong']);
        $this->assertSame('testing', $phanHoi->json('moi_truong'));
        // Chỉ đúng hai trường: không có chỗ nào lọt địa chỉ database, khóa, mật khẩu
        $this->assertSame(['ten_ung_dung', 'moi_truong'], array_keys($phanHoi->json()));
    }

    // ---------- Thống kê ----------

    public function test_thong_ke_phai_dang_nhap(): void
    {
        $this->getJson('/api/thong-ke')->assertStatus(401);
    }

    public function test_thong_ke_ke_toan_thay_ca_trung_tam(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 2000000, HoaDon::DA_DONG);
        $this->taoHoaDon('HD2', $this->hocVienAn, 1000000);
        $this->taoHoaDon('HD3', $this->hocVienBinh, 1000000);

        Sanctum::actingAs($this->keToan);
        $du_lieu = $this->getJson('/api/thong-ke')->assertOk()->json('du_lieu');

        $this->assertSame(3, $du_lieu['tong_hoa_don']);
        $this->assertSame(1, $du_lieu['so_da_dong']);
        $this->assertSame(2, $du_lieu['so_chua_dong']);
        $this->assertSame(4000000, $du_lieu['tong_tien']);
        $this->assertSame(2000000, $du_lieu['da_thu']);
        $this->assertSame(2000000, $du_lieu['chua_thu']);
        $this->assertSame(50, $du_lieu['ty_le_thu']);
        $this->assertSame(2, $du_lieu['so_hoc_vien']);
        $this->assertCount(6, $du_lieu['theo_thang']);
        $this->assertCount(3, $du_lieu['gan_day']);

        // Tháng cuối trong biểu đồ là tháng hiện tại: có 2 triệu đã thu và 4 triệu đã lập
        $thangNay = end($du_lieu['theo_thang']);
        $this->assertSame(now()->format('Y-m'), $thangNay['thang']);
        $this->assertSame(2000000, $thangNay['da_thu']);
        $this->assertSame(4000000, $thangNay['da_lap']);
    }

    public function test_thong_ke_hoc_vien_chi_thay_so_lieu_cua_minh(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 2000000, HoaDon::DA_DONG);
        $this->taoHoaDon('HD3', $this->hocVienBinh, 9000000);

        Sanctum::actingAs($this->taiKhoanAn);
        $du_lieu = $this->getJson('/api/thong-ke')->assertOk()->json('du_lieu');

        $this->assertSame(1, $du_lieu['tong_hoa_don']);
        $this->assertSame(2000000, $du_lieu['tong_tien']);
        $this->assertNull($du_lieu['so_hoc_vien'], 'Học viên không cần biết tổng số học viên của trung tâm');
    }

    public function test_thong_ke_khi_chua_co_hoa_don_khong_loi(): void
    {
        Sanctum::actingAs($this->keToan);
        $du_lieu = $this->getJson('/api/thong-ke')->assertOk()->json('du_lieu');

        $this->assertSame(0, $du_lieu['tong_hoa_don']);
        $this->assertSame(0, $du_lieu['ty_le_thu'], 'Không chia cho 0');
    }

    // ---------- Quản lý học viên ----------

    public function test_ke_toan_them_hoc_vien(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->postJson('/api/hoc-vien', ['ten' => 'Le Cuong', 'email' => 'cuong@example.com'])
            ->assertStatus(201)
            ->assertJson(['thong_bao' => 'Đã thêm học viên', 'du_lieu' => ['ten' => 'Le Cuong', 'co_tai_khoan' => false]]);

        $this->assertDatabaseHas('hoc_vien', ['email' => 'cuong@example.com', 'tai_khoan_id' => null]);
    }

    public function test_them_hoc_vien_email_trung_bi_tu_choi(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->postJson('/api/hoc-vien', ['ten' => 'Ai Do', 'email' => 'an@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertSame(2, HocVien::count());
    }

    public function test_them_hoc_vien_thieu_ten_bi_tu_choi(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->postJson('/api/hoc-vien', ['ten' => '', 'email' => 'x@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors(['ten']);
    }

    public function test_hoc_vien_khong_duoc_quan_ly_hoc_vien(): void
    {
        Sanctum::actingAs($this->taiKhoanAn);

        $this->postJson('/api/hoc-vien', ['ten' => 'Ai Do'])->assertStatus(403);
        $this->putJson('/api/hoc-vien/'.$this->hocVienBinh->id, ['ten' => 'Sua'])->assertStatus(403);
        $this->getJson('/api/hoc-vien/'.$this->hocVienBinh->id)->assertStatus(403);
    }

    public function test_sua_hoc_vien_chua_co_tai_khoan_duoc_doi_ca_ten_va_email(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->putJson('/api/hoc-vien/'.$this->hocVienBinh->id, ['ten' => 'Tran Binh Moi', 'email' => 'moi@example.com'])
            ->assertOk()->assertJson(['du_lieu' => ['ten' => 'Tran Binh Moi', 'email' => 'moi@example.com']]);
    }

    public function test_sua_hoc_vien_da_co_tai_khoan_khong_doi_duoc_email(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->putJson('/api/hoc-vien/'.$this->hocVienAn->id, ['ten' => 'An Moi', 'email' => 'khac@example.com'])
            ->assertOk();

        $this->hocVienAn->refresh();
        $this->assertSame('An Moi', $this->hocVienAn->ten);
        $this->assertSame('an@example.com', $this->hocVienAn->email, 'Email phải khớp email đăng nhập');
    }

    public function test_sua_hoc_vien_giu_nguyen_email_cua_chinh_minh_khong_bi_bao_trung(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->putJson('/api/hoc-vien/'.$this->hocVienBinh->id, ['ten' => 'Tran Binh', 'email' => 'binh@example.com'])
            ->assertOk();
    }

    public function test_chi_tiet_hoc_vien_co_hoa_don_va_tong_tien(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 2000000, HoaDon::DA_DONG);
        $this->taoHoaDon('HD2', $this->hocVienAn, 500000);

        Sanctum::actingAs($this->keToan);
        $du_lieu = $this->getJson('/api/hoc-vien/'.$this->hocVienAn->id)->assertOk()->json('du_lieu');

        $this->assertSame(2, $du_lieu['so_hoa_don']);
        $this->assertSame(2000000, $du_lieu['da_thu']);
        $this->assertSame(500000, $du_lieu['chua_thu']);
        $this->assertCount(2, $du_lieu['hoa_don']);
        $this->assertTrue($du_lieu['co_tai_khoan']);
    }

    public function test_hoc_vien_khong_ton_tai_bao_khong_tim_thay(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->getJson('/api/hoc-vien/9999')->assertStatus(404);
    }

    public function test_tim_hoc_vien_theo_ten_hoac_email(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->assertCount(1, $this->getJson('/api/hoc-vien?q=Binh')->assertOk()->json('du_lieu'));
        $this->assertCount(1, $this->getJson('/api/hoc-vien?q=an@exam')->assertOk()->json('du_lieu'));
        $this->assertCount(2, $this->getJson('/api/hoc-vien')->assertOk()->json('du_lieu'));
    }

    // ---------- Tìm kiếm và lọc hóa đơn ----------

    public function test_loc_hoa_don_theo_trang_thai(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 1000000, HoaDon::DA_DONG);
        $this->taoHoaDon('HD2', $this->hocVienAn, 1000000);

        Sanctum::actingAs($this->keToan);

        $ma = array_column($this->getJson('/api/hoa-don?trang_thai=da_dong')->assertOk()->json('data'), 'ma_hoa_don');
        $this->assertSame(['HD1'], $ma);
    }

    public function test_tim_hoa_don_theo_ma_hoac_ten_hoc_vien(): void
    {
        $this->taoHoaDon('HD100', $this->hocVienAn, 1000000);
        $this->taoHoaDon('HD200', $this->hocVienBinh, 1000000);

        Sanctum::actingAs($this->keToan);

        $this->assertSame(['HD200'], array_column($this->getJson('/api/hoa-don?q=HD2')->json('data'), 'ma_hoa_don'));
        $this->assertSame(['HD200'], array_column($this->getJson('/api/hoa-don?q=Binh')->json('data'), 'ma_hoa_don'));
        $this->assertCount(0, $this->getJson('/api/hoa-don?q=khong-co')->json('data'));
    }

    public function test_loc_hoa_don_theo_khoang_ngay_tao(): void
    {
        $cu = $this->taoHoaDon('HD1', $this->hocVienAn, 1000000);
        $cu->forceFill(['created_at' => now()->subDays(40)])->save();
        $this->taoHoaDon('HD2', $this->hocVienAn, 1000000);

        Sanctum::actingAs($this->keToan);

        $tu = now()->subDays(7)->format('Y-m-d');
        $this->assertSame(['HD2'], array_column($this->getJson("/api/hoa-don?tu_ngay={$tu}")->json('data'), 'ma_hoa_don'));

        $den = now()->subDays(7)->format('Y-m-d');
        $this->assertSame(['HD1'], array_column($this->getJson("/api/hoa-don?den_ngay={$den}")->json('data'), 'ma_hoa_don'));
    }

    public function test_bo_loc_sai_dang_bi_tu_choi(): void
    {
        Sanctum::actingAs($this->keToan);

        $this->getJson('/api/hoa-don?trang_thai=linh-tinh')->assertStatus(422)->assertJsonValidationErrors(['trang_thai']);
        $this->getJson('/api/hoa-don?tu_ngay=12/11/2026')->assertStatus(422)->assertJsonValidationErrors(['tu_ngay']);
        $this->getJson('/api/hoa-don?per_page=1000')->assertStatus(422)->assertJsonValidationErrors(['per_page']);
    }

    public function test_loc_van_giu_quyen_hoc_vien_chi_thay_hoa_don_cua_minh(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 1000000);
        $this->taoHoaDon('HD2', $this->hocVienBinh, 1000000);

        Sanctum::actingAs($this->taiKhoanAn);

        // Học viên cố tìm hóa đơn của người khác: không thấy
        $this->assertCount(0, $this->getJson('/api/hoa-don?q=HD2')->json('data'));
        $this->assertCount(1, $this->getJson('/api/hoa-don')->json('data'));
    }

    public function test_per_page_dieu_chinh_so_dong_moi_trang(): void
    {
        foreach (range(1, 5) as $i) {
            $this->taoHoaDon('HD'.$i, $this->hocVienAn, 1000000);
        }

        Sanctum::actingAs($this->keToan);
        $phanHoi = $this->getJson('/api/hoa-don?per_page=2')->assertOk();

        $this->assertCount(2, $phanHoi->json('data'));
        $this->assertSame(3, $phanHoi->json('meta.last_page'));
    }

    // ---------- Tải CSV ----------

    public function test_ke_toan_tai_csv_theo_bo_loc(): void
    {
        $this->taoHoaDon('HD1', $this->hocVienAn, 2000000, HoaDon::DA_DONG);
        $this->taoHoaDon('HD2', $this->hocVienBinh, 1500000);

        Sanctum::actingAs($this->keToan);
        $phanHoi = $this->get('/api/hoa-don/xuat-csv?trang_thai=chua_dong')->assertOk();

        $noiDung = $phanHoi->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $noiDung, 'Có BOM để Excel đọc đúng tiếng Việt');
        $this->assertStringContainsString('HD2', $noiDung);
        $this->assertStringNotContainsString('HD1', $noiDung, 'CSV phải theo đúng bộ lọc');
        $this->assertStringContainsString('Chưa đóng', $noiDung);
        $this->assertStringContainsString('text/csv', $phanHoi->headers->get('Content-Type'));
    }

    public function test_hoc_vien_khong_tai_duoc_csv(): void
    {
        Sanctum::actingAs($this->taiKhoanAn);

        $this->get('/api/hoa-don/xuat-csv', ['Accept' => 'application/json'])->assertStatus(403);
    }

    public function test_csv_chan_cong_thuc_doc_hai(): void
    {
        $doc = HocVien::create(['ten' => '=HYPERLINK("http://x.example")', 'email' => 'x@example.com']);
        $this->taoHoaDon('HD9', $doc, 1000);

        Sanctum::actingAs($this->keToan);
        $noiDung = $this->get('/api/hoa-don/xuat-csv')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $noiDung, 'Ô bắt đầu bằng = phải được thêm dấu nháy đơn');
    }
}
