<?php

namespace App\Support;

// Thông báo lỗi kiểm tra dữ liệu (validate) bằng tiếng Việt, dùng chung cho mọi nơi.
class ThongBaoLoi
{
    public static function thongBao(): array
    {
        return [
            'required' => ':attribute không được để trống',
            'email' => ':attribute phải đúng dạng email',
            // min và max là luật về "kích thước": câu lỗi khác nhau tùy dữ liệu là chữ hay số
            'min' => [
                'string' => ':attribute phải có ít nhất :min ký tự',
                'numeric' => ':attribute phải từ :min trở lên',
            ],
            'max' => [
                'string' => ':attribute không được dài quá :max ký tự',
                'numeric' => ':attribute không được lớn hơn :max',
            ],
            'integer' => ':attribute phải là số nguyên',
            'string' => ':attribute phải là chữ',
            'exists' => ':attribute không tồn tại',
            'unique' => ':attribute đã được sử dụng',
            'in' => ':attribute không hợp lệ',
            'date_format' => ':attribute phải đúng dạng năm-tháng-ngày (ví dụ 2026-11-12)',
        ];
    }

    public static function tenTruong(): array
    {
        return [
            'ten' => 'Tên',
            'email' => 'Email',
            'password' => 'Mật khẩu',
            'ma_hoa_don' => 'Mã hóa đơn',
            'hoc_vien_id' => 'Học viên',
            'so_tien' => 'Số tiền',
            'q' => 'Từ khóa tìm kiếm',
            'trang_thai' => 'Trạng thái',
            'tu_ngay' => 'Từ ngày',
            'den_ngay' => 'Đến ngày',
            'per_page' => 'Số dòng mỗi trang',
        ];
    }
}
