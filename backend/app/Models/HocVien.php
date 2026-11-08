<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model của bảng hoc_vien: hồ sơ học viên.
class HocVien extends Model
{
    protected $table = 'hoc_vien';

    protected $fillable = ['tai_khoan_id', 'ten', 'email'];

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }

    public function hoaDons(): HasMany
    {
        return $this->hasMany(HoaDon::class, 'hoc_vien_id');
    }
}
