<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Hình dạng dữ liệu hóa đơn trả về cho frontend: chỉ chọn những trường cần thiết.
class HoaDonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ma_hoa_don' => $this->ma_hoa_don,
            'hoc_vien' => $this->hocVien?->ten,
            'so_tien' => $this->so_tien,
            'trang_thai' => $this->trang_thai,
            'ngay_thu' => $this->ngay_thu?->format('d/m/Y'),
        ];
    }
}
