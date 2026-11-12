# Hoc Phi: dự án mẫu thu học phí

Dự án mẫu dùng trong lớp BrSE, bài 4 "Source code". Đây là một hệ thống chạy thật gồm giao diện web, backend và database, viết riêng cho lớp, **toàn bộ là dữ liệu giả**.

## 1. Thông tin cơ bản

Hệ thống thu học phí cho một trung tâm đào tạo.

| Vai trò | Làm được gì |
|---|---|
| Kế toán | Xem mọi hóa đơn, tạo hóa đơn, ghi nhận đã thu tiền, xóa hóa đơn chưa đóng |
| Học viên | Tự đăng ký tài khoản, đăng nhập, chỉ xem hóa đơn của chính mình |
| Hệ thống | Mỗi ngày 08:00 tự gửi email nhắc học viên có hóa đơn chưa đóng |

**Chức năng trên giao diện** (có đủ ở máy tính và điện thoại, tự đổi sáng/tối theo máy):

| Trang | Ai dùng | Làm được gì |
|---|---|---|
| Tổng quan (`tong-quan.html`) | Kế toán, học viên | Thẻ số liệu (đã lập, đã thu, chưa thu), biểu đồ 6 tháng, vòng tỷ lệ thu, 5 hóa đơn gần đây. Học viên chỉ thấy số liệu của mình |
| Hóa đơn (`hoa-don.html`) | Kế toán, học viên | Tìm theo mã hoặc tên học viên, lọc theo trạng thái và khoảng ngày, phân trang, chọn số dòng. Kế toán: tạo hóa đơn, ghi nhận đã thu, xóa (hóa đơn đã đóng bị khóa), **tải CSV** theo đúng bộ lọc. Bộ lọc nằm trên thanh địa chỉ nên gửi link là gửi cả bộ lọc |
| Học viên (`hoc-vien.html`) | Chỉ kế toán | Danh sách, tìm kiếm, thêm, sửa hồ sơ, xem hóa đơn và tiền đã thu, chưa thu của từng học viên |
| Nhãn môi trường | Mọi trang | Góc trên bên phải và dải màu đầu trang cho biết đang ở **Local, Test, Staging hay Production** (backend trả về `APP_ENV` qua `GET /api/he-thong`). Dùng để minh họa bài 5: "khách đang xem bản nào?" |

**Quy tắc nghiệp vụ đã được cài đặt** (mỗi quy tắc có test trong `backend/tests/Feature/`):

1. Mã hóa đơn không được trùng nhau.
2. Hóa đơn đã đóng thì không ghi nhận đóng lần nữa.
3. Số tiền hóa đơn phải lớn hơn 0.
4. Chỉ người đã đăng nhập mới xem được danh sách hóa đơn.
5. Học viên chỉ xem được hóa đơn của chính mình.
6. Hệ thống tự gửi email nhắc học viên chưa đóng tiền.
7. Hóa đơn đã đóng tiền thì không được xóa.

## 2. Công nghệ

| Phần | Công nghệ |
|---|---|
| Backend | PHP 8.4, Laravel 13, Laravel Sanctum (đăng nhập bằng token) |
| Database | MySQL 8.4 |
| Frontend | HTML, CSS, JavaScript thuần (không framework, không cần build) |
| Chạy dự án | Docker Compose (nginx cho frontend, Adminer để xem database) |
| Test | PHPUnit (có sẵn trong Laravel), chạy trên SQLite trong bộ nhớ |

## 3. Kiến trúc hệ thống

```
Trình duyệt ──► frontend (nginx, :8080) : chỉ phát file HTML/CSS/JS
     │
     └─ JS gọi API ──► backend (Laravel, :8000) ──► db (MySQL, :3306)
                              │
                              └─ scheduler: chạy việc hẹn giờ (email nhắc nợ)

Adminer (:8081): trang web để xem bảng dữ liệu trong db
```

- Frontend và backend là **hai ứng dụng tách rời**, nói chuyện với nhau qua địa chỉ `/api`. Vì chạy ở hai cổng khác nhau nên backend phải cho phép địa chỉ frontend (CORS, xem `backend/config/cors.php`).
- Đăng nhập: backend trả một **token** (thẻ ra vào). Frontend giữ token và gửi kèm mọi yêu cầu. Quyền (kế toán hay học viên) luôn được backend kiểm tra lại, không tin giao diện.
- Backend theo mô hình **MVC**: Model là `app/Models`, Controller là `app/Http/Controllers`. Vì backend chỉ trả JSON, phần "View" (hiển thị) là frontend; riêng email có View ở `backend/resources/views/emails/`.

## 4. Thư mục và file cần lưu ý

```
project/
├── docker-compose.yml        chạy cả dự án (include db/docker-compose.yml)
├── .env.example              mẫu cấu hình cho Docker Compose
├── db/
│   └── docker-compose.yml    database MySQL và Adminer
├── backend/                  Laravel
│   ├── routes/api.php        bảng chỉ đường: địa chỉ API nào do controller nào xử lý
│   ├── routes/console.php    việc hẹn giờ (email nhắc nợ 08:00 mỗi ngày)
│   ├── app/Http/Controllers/ HoaDonController, HocVienController, Auth/Register, Auth/Login
│   ├── app/Http/Requests/    quy tắc kiểm tra dữ liệu nhập (validate)
│   ├── app/Http/Middleware/  kiểm tra vai trò (chạy trước controller)
│   ├── app/Models/           TaiKhoan, HocVien, HoaDon
│   ├── database/migrations/  khai báo các bảng
│   ├── database/seeders/     dữ liệu mẫu (giả)
│   ├── tests/Feature/        test cho từng quy tắc nghiệp vụ
│   ├── config/               cấu hình, đọc giá trị từ .env
│   ├── .env.example          mẫu cấu hình backend (.env thật không có trên repo)
│   ├── composer.json         danh sách thư viện PHP (nhận diện Laravel)
│   └── artisan               công cụ dòng lệnh của Laravel (nhận diện Laravel)
└── frontend/
    ├── *.html                các trang: đăng nhập, đăng ký, tổng quan, hóa đơn, học viên
    ├── css/style.css         giao diện (bảng màu và cỡ chữ ở đầu file; tự đổi sáng/tối)
    └── js/                   config.js (địa chỉ backend), api.js (gọi API), khung.js (phần dùng chung:
                              thanh bên, nhãn môi trường, thông báo, hộp thoại), mỗi trang một file
```

Hai file cấu hình cần phân biệt, giống ví dụ trong bài giảng:

| File | Trên repo? | Chứa gì |
|---|---|---|
| `backend/.env.example` | Có | Bản mẫu: tên các biến, giá trị cho máy học |
| `backend/.env` | **Không** (nằm trong `.gitignore`) | Giá trị thật của từng môi trường, kể cả mật khẩu database |
| `backend/config/database.php` | Có | Cách đọc giá trị từ biến `DB_...`. Không sửa file này để đổi database |

## 5. Cài đặt và triển khai

### Chạy trên máy (cách dùng trong lớp)

Cần cài **Docker Desktop**. Không cần cài PHP, Composer hay MySQL.

```bash
docker compose up --build
```

Lần đầu mất vài phút vì phải tải thư viện (cần Internet). Các lần sau chạy trong vài giây. Khi chạy xong:

| Địa chỉ | Là gì |
|---|---|
| http://localhost:8080 | Giao diện web (bắt đầu ở đây) |
| http://localhost:8000 | Backend (API) |
| http://localhost:8081 | Adminer: xem bảng dữ liệu. Hệ thống: MySQL, máy chủ `db`, user và mật khẩu như trong `.env.example` |

Tắt dự án (giữ dữ liệu):

```bash
docker compose down
```

Tắt và xóa luôn dữ liệu để bắt đầu lại từ dữ liệu mẫu:

```bash
docker compose down -v
```

### Tài khoản demo (dữ liệu giả)

| Email | Vai trò |
|---|---|
| `ketoan@example.com` | Kế toán |
| `an@example.com` | Học viên (có 2 hóa đơn) |
| `binh@example.com` | Học viên (có 2 hóa đơn) |
| `cuong@example.com` | Chưa có tài khoản: hồ sơ do kế toán tạo sẵn. Đăng ký bằng đúng email này sẽ thấy hóa đơn của mình |

Mật khẩu chung của các tài khoản demo nằm ở hằng `MAT_KHAU_DEMO` trong `backend/database/seeders/DatabaseSeeder.php`. Chỉ dùng cho máy học.

### Chạy test

```bash
docker compose exec backend php artisan test
```

### Chạy backend trực tiếp trên máy (không dùng Docker cho backend)

Cần PHP 8.4 và Composer. Chỉ chạy database bằng Docker:

```bash
docker compose -f db/docker-compose.yml up -d
```

```bash
cd backend
cp .env.example .env
```

Mở `backend/.env`, đổi `DB_HOST=db` thành `DB_HOST=127.0.0.1`, rồi:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Frontend: mở thư mục `frontend` bằng một máy chủ web bất kỳ ở cổng 8080 (không mở file HTML trực tiếp bằng đường dẫn `file://`, trình duyệt sẽ chặn gọi API).

### Đổi database

Muốn trỏ hệ thống sang database khác, sửa các dòng `DB_...` trong **`backend/.env`**, rồi khởi động lại backend. Không sửa `backend/config/database.php`. Mật khẩu thật do Team lead hoặc dev cung cấp, không gửi qua chat.

### Các môi trường

| Môi trường | Dành cho | Dự án mẫu này |
|---|---|---|
| Local / Dev | Developer | Chạy bằng `docker compose up` như trên |
| Testing | Tester | `php artisan test` (database SQLite tạm trong bộ nhớ) |
| Staging | Khách hàng kiểm tra | Chưa có. Cần máy chủ riêng, `.env` riêng, database riêng |
| Production | Người dùng cuối | Chưa có |

Khi lập Staging hoặc Production, những việc **bắt buộc** khác với máy học:

- Tạo `backend/.env` riêng cho môi trường đó: `APP_ENV=staging` hoặc `production`, `APP_DEBUG=false`, mật khẩu database mạnh và riêng, `FRONTEND_URL` là địa chỉ web thật, `MAIL_MAILER=smtp` kèm thông tin máy chủ mail.
- **Không nạp dữ liệu mẫu**: dữ liệu demo có mật khẩu mà ai đọc repo cũng biết. Script khởi động `backend/docker-entrypoint.sh` chỉ nạp khi `APP_ENV=local`.
- Sửa `API_URL` trong `frontend/js/config.js` thành địa chỉ backend thật.
- Chạy `php artisan migrate --force` để tạo bảng, và bật tiến trình hẹn giờ (`php artisan schedule:work`, hoặc cron gọi `php artisan schedule:run` mỗi phút) thì email nhắc nợ mới tự gửi.
- Ở máy học, `MAIL_MAILER=log` nên email **không được gửi thật**, chỉ ghi nội dung vào `backend/storage/logs/laravel.log`.

Dự án mẫu **chưa có** quy trình triển khai tự động (CI/CD) và chưa có máy chủ Staging hay Production. Cách triển khai thật phải hỏi Team lead.
