#!/bin/sh
# Chạy mỗi lần container backend khởi động.
# Mục đích: người mới clone repo chỉ cần "docker compose up" là chạy được.
set -e

cd /var/www/html

# 1. Chưa có thư viện (thư mục vendor) thì tải về. vendor không nằm trên repo.
if [ ! -f vendor/autoload.php ]; then
    echo ">> Đang cài thư viện bằng Composer (lần đầu mất vài phút)..."
    composer install --no-interaction --prefer-dist
fi

# 2. Chưa có file .env thì tạo từ bản mẫu. .env thật không nằm trên repo.
if [ ! -f .env ]; then
    echo ">> Chưa có .env, tạo từ .env.example"
    cp .env.example .env
fi

# 3. Chưa có APP_KEY (khóa mã hóa của ứng dụng) thì sinh khóa mới.
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# 4. Tạo bảng và dữ liệu mẫu. Thử lại vài lần vì MySQL cần thời gian để sẵn sàng.
tries=0
until php artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo ">> Không kết nối được database sau 30 lần thử. Kiểm tra DB_HOST, DB_USERNAME, DB_PASSWORD trong .env"
        exit 1
    fi
    echo ">> Database chưa sẵn sàng, thử lại sau 3 giây ($tries/30)..."
    sleep 3
done

# 5. Nạp dữ liệu mẫu (tài khoản demo, hóa đơn giả). CHỈ ở môi trường local.
#    Staging/Production tuyệt đối không nạp: dữ liệu demo có mật khẩu yếu, ai cũng biết.
APP_ENV_HIEN_TAI=$(grep '^APP_ENV=' .env | cut -d= -f2 | tr -d '"')
if [ "$APP_ENV_HIEN_TAI" = "local" ]; then
    php artisan db:seed --force
else
    echo ">> APP_ENV=$APP_ENV_HIEN_TAI: bỏ qua bước nạp dữ liệu mẫu."
fi

# 6. Chạy máy chủ web. Backend nghe ở cổng 8000.
exec php artisan serve --host=0.0.0.0 --port=8000
