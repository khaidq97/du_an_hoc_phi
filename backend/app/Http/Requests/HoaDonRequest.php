<?php

namespace App\Http\Requests;

use App\Support\ThongBaoLoi;
use Illuminate\Foundation\Http\FormRequest;

// Quy tắc kiểm tra dữ liệu (validate) khi tạo hóa đơn.
class HoaDonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Việc "ai được tạo hóa đơn" đã chặn ở route (middleware vai_tro:ke_toan).
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_hoa_don' => 'required|string|max:20',
            'hoc_vien_id' => 'required|exists:hoc_vien,id',
            // Số tiền phải là số nguyên và lớn hơn 0.
            'so_tien' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return ThongBaoLoi::thongBao();
    }

    public function attributes(): array
    {
        return ThongBaoLoi::tenTruong();
    }
}
