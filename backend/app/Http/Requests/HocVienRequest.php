<?php

namespace App\Http\Requests;

use App\Support\ThongBaoLoi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Quy tắc kiểm tra dữ liệu (validate) khi kế toán tạo hoặc sửa hồ sơ học viên.
class HocVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Việc "ai được quản lý học viên" đã chặn ở route (middleware vai_tro:ke_toan).
        return true;
    }

    public function rules(): array
    {
        // Khi sửa, email của chính hồ sơ này không bị tính là trùng.
        $idDangSua = $this->route('id');

        return [
            'ten' => 'required|string|max:100',
            'email' => [
                'nullable', 'email', 'max:150',
                Rule::unique('hoc_vien', 'email')->ignore($idDangSua),
            ],
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
