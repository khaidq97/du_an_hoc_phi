<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model của bảng hoa_don: hóa đơn học phí.
class HoaDon extends Model
{
    const CHUA_DONG = 'chua_dong';
    const DA_DONG = 'da_dong';

    protected $table = 'hoa_don';

    protected $fillable = ['ma_hoa_don', 'hoc_vien_id', 'so_tien', 'trang_thai', 'ngay_thu'];

    protected function casts(): array
    {
        return [
            'so_tien' => 'integer',
            'ngay_thu' => 'datetime',
        ];
    }

    public function hocVien(): BelongsTo
    {
        return $this->belongsTo(HocVien::class, 'hoc_vien_id');
    }

    public function daDong(): bool
    {
        return $this->trang_thai === self::DA_DONG;
    }
}
