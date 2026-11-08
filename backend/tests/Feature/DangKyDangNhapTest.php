<?php

namespace Tests\Feature;

use App\Models\HocVien;
use App\Models\TaiKhoan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DangKyDangNhapTest extends TestCase
{
    use RefreshDatabase;

    private function duLieuDangKy(array $ghiDe = []): array
    {
        return array_merge([
            'ten' => 'Hoc Vien Moi',
            'email' => 'moi@example.com',
            'password' => 'matkhau-test-3',
        ], $ghiDe);
    }

    public function test_dang_ky_thanh_cong_tao_tai_khoan_va_ho_so_hoc_vien(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy())
            ->assertStatus(201)
            ->assertJson(['thong_bao' => 'Tài khoản đã được đăng ký thành công']);

        $taiKhoan = TaiKhoan::where('email', 'moi@example.com')->firstOrFail();
        $this->assertSame(TaiKhoan::VAI_TRO_HOC_VIEN, $taiKhoan->vai_tro);
        $this->assertNotSame('matkhau-test-3', $taiKhoan->password, 'Mật khẩu phải được mã hóa');
        $this->assertSame($taiKhoan->id, HocVien::where('email', 'moi@example.com')->value('tai_khoan_id'));
    }

    public function test_dang_ky_email_da_co_thi_bao_loi(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy())->assertStatus(201);

        $this->postJson('/api/dang-ky', $this->duLieuDangKy())
            ->assertStatus(409)
            ->assertJson(['thong_bao' => 'Email đã được đăng ký']);

        $this->assertSame(1, TaiKhoan::count());
    }

    public function test_dang_ky_du_lieu_khong_hop_le_bi_tu_choi(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy(['email' => 'khong-phai-email']))
            ->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->postJson('/api/dang-ky', $this->duLieuDangKy(['password' => 'ngan']))
            ->assertStatus(422)->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Mật khẩu phải có ít nhất 8 ký tự');

        $this->assertSame(0, TaiKhoan::count());
    }

    public function test_nguoi_dang_ky_khong_tu_nang_quyen_thanh_ke_toan(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy(['vai_tro' => 'ke_toan']))->assertStatus(201);

        $this->assertSame(TaiKhoan::VAI_TRO_HOC_VIEN, TaiKhoan::first()->vai_tro);
    }

    public function test_dang_ky_noi_vao_ho_so_hoc_vien_ke_toan_da_tao_san(): void
    {
        $hoSo = HocVien::create(['ten' => 'Co San', 'email' => 'moi@example.com']);

        $this->postJson('/api/dang-ky', $this->duLieuDangKy())->assertStatus(201);

        $this->assertSame(1, HocVien::count(), 'Không được tạo hồ sơ trùng');
        $this->assertNotNull($hoSo->fresh()->tai_khoan_id);
    }

    public function test_dang_nhap_dung_nhan_token_va_dung_token_goi_duoc_api(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy())->assertStatus(201);

        $phanHoi = $this->postJson('/api/dang-nhap', [
            'email' => 'moi@example.com', 'password' => 'matkhau-test-3',
        ])->assertOk()->assertJsonPath('tai_khoan.vai_tro', 'hoc_vien');

        $this->withHeader('Authorization', 'Bearer '.$phanHoi->json('token'))
            ->getJson('/api/hoa-don')
            ->assertOk();
    }

    public function test_dang_nhap_sai_mat_khau_hoac_sai_email_bao_chung_mot_cau(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy())->assertStatus(201);

        foreach ([
            ['email' => 'moi@example.com', 'password' => 'sai-mat-khau'],
            ['email' => 'khong-co@example.com', 'password' => 'matkhau-test-3'],
        ] as $thongTin) {
            $this->postJson('/api/dang-nhap', $thongTin)
                ->assertStatus(401)
                ->assertJson(['thong_bao' => 'Email hoặc mật khẩu không đúng']);
        }
    }

    public function test_dang_xuat_huy_token(): void
    {
        $this->postJson('/api/dang-ky', $this->duLieuDangKy())->assertStatus(201);
        $token = $this->postJson('/api/dang-nhap', [
            'email' => 'moi@example.com', 'password' => 'matkhau-test-3',
        ])->json('token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dang-xuat')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_du_lieu_mau_chay_lai_nhieu_lan_khong_bi_trung(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('tai_khoan', 3);
        $this->assertDatabaseCount('hoc_vien', 3);
        $this->assertDatabaseCount('hoa_don', 5);
    }
}
