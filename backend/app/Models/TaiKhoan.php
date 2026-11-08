<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Model của bảng tai_khoan: người đăng nhập.
class TaiKhoan extends Authenticatable
{
    use HasApiTokens;

    const VAI_TRO_KE_TOAN = 'ke_toan';
    const VAI_TRO_HOC_VIEN = 'hoc_vien';

    protected $table = 'tai_khoan';

    protected $fillable = ['ten', 'email', 'password', 'vai_tro'];

    // Không bao giờ trả mật khẩu ra ngoài khi chuyển sang JSON.
    protected $hidden = ['password'];

    protected function casts(): array
    {
        // Gán mật khẩu thường vào đây, Laravel tự mã hóa trước khi lưu.
        return ['password' => 'hashed'];
    }

    public function hocVien(): HasOne
    {
        return $this->hasOne(HocVien::class, 'tai_khoan_id');
    }

    public function laKeToan(): bool
    {
        return $this->vai_tro === self::VAI_TRO_KE_TOAN;
    }
}
