# backend: Hoc Phi API (Laravel)

Phần xử lý logic và dữ liệu của hệ thống thu học phí. Không có giao diện: chỉ nhận yêu cầu ở các địa chỉ `/api/...` và trả JSON. Giao diện nằm ở thư mục `../frontend`. Xem hướng dẫn đầy đủ ở [README của dự án](../README.md).

## Đọc code theo thứ tự này

| Muốn biết | Mở file |
|---|---|
| Có những địa chỉ API nào, ai gọi được | `routes/api.php` |
| Logic hóa đơn: tạo, thu tiền, xóa | `app/Http/Controllers/HoaDonController.php` |
| Đăng ký, đăng nhập | `app/Http/Controllers/Auth/` |
| Dữ liệu và quan hệ giữa các bảng | `app/Models/` và `database/migrations/` |
| Quy tắc kiểm tra dữ liệu nhập (validate) | `app/Http/Requests/HoaDonRequest.php` |
| Kiểm tra quyền (kế toán / học viên) | `app/Http/Middleware/KiemTraVaiTro.php` |
| Email nhắc nợ và hẹn giờ | `app/Console/Commands/NhacNoCommand.php`, `routes/console.php` |
| Yêu cầu của khách đã được kiểm tra bằng test chưa | `tests/Feature/` |

## Lệnh thường dùng

Chạy trong container (máy không cần cài PHP), đứng ở thư mục gốc của dự án:

```bash
docker compose exec backend php artisan test
```

```bash
docker compose exec backend php artisan route:list --path=api
```

```bash
docker compose exec backend php artisan hoa-don:nhac-no
```
