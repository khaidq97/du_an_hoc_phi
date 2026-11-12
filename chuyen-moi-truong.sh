#!/bin/sh
# Chuyển backend sang một môi trường để THỬ TRÊN MÁY (dùng khi dạy bài 5):
#
#   ./chuyen-moi-truong.sh local         máy dev (APP_ENV=local, bật debug, nạp dữ liệu mẫu)
#   ./chuyen-moi-truong.sh staging       giống Staging (APP_ENV=staging, tắt debug)
#   ./chuyen-moi-truong.sh production    giống Production (APP_ENV=production, tắt debug)
#
# Script chép file mẫu tương ứng thành backend/.env rồi khởi động lại backend.
# Nhãn môi trường ở góc trên bên phải giao diện (http://localhost:8080) đổi theo.
# Đây chỉ là mô phỏng trên máy học: Staging và Production thật là máy chủ riêng, .env riêng, database riêng.
set -e
cd "$(dirname "$0")"

case "$1" in
    local)      MAU=backend/.env.example ;;
    staging)    MAU=backend/.env.staging.example ;;
    production) MAU=backend/.env.production.example ;;
    *) echo "Cách dùng: ./chuyen-moi-truong.sh local|staging|production"; exit 1 ;;
esac

# Giữ lại khóa mã hóa và thông tin kết nối database hiện có của máy học:
# - khóa mã hóa: để không mất đăng nhập;
# - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD: ở máy học mọi môi trường dùng chung một database Docker.
#   (Staging và Production thật mỗi nơi một database riêng, như ghi trong file mẫu.)
if [ -f backend/.env ]; then
    cp backend/.env backend/.env.truoc-khi-chuyen
    echo ">> Đã lưu .env cũ ở backend/.env.truoc-khi-chuyen"
else
    cp backend/.env.example backend/.env
fi

giu() { grep "^$1=" backend/.env | head -1 | cut -d= -f2-; }
KHOA=$(giu APP_KEY); DB_HOST_CU=$(giu DB_HOST); DB_NAME_CU=$(giu DB_DATABASE); DB_USER_CU=$(giu DB_USERNAME); DB_PASS_CU=$(giu DB_PASSWORD)

awk -v mt="$1" -v khoa="$KHOA" -v dbh="$DB_HOST_CU" -v dbn="$DB_NAME_CU" -v dbu="$DB_USER_CU" -v dbp="$DB_PASS_CU" '
    /^APP_KEY=/     && khoa != "" { print "APP_KEY=" khoa; next }
    /^DB_HOST=/     && dbh != ""  { print "DB_HOST=" dbh; next }
    /^DB_DATABASE=/ && dbn != ""  { print "DB_DATABASE=" dbn; next }
    /^DB_USERNAME=/ && dbu != ""  { print "DB_USERNAME=" dbu; next }
    /^DB_PASSWORD=/ && dbp != ""  { print "DB_PASSWORD=" dbp; next }
    # Chạy thử trên máy: frontend ở localhost nên địa chỉ phải là localhost, và email chỉ ghi vào log (không gửi ra ngoài)
    mt != "local" && /^APP_URL=/       { print "APP_URL=http://localhost:8000"; next }
    mt != "local" && /^FRONTEND_URL=/  { print "FRONTEND_URL=http://localhost:8080,http://127.0.0.1:8080"; next }
    mt != "local" && /^MAIL_MAILER=/   { print "MAIL_MAILER=log"; next }
    { print }
' "$MAU" > backend/.env.moi
mv backend/.env.moi backend/.env

echo ">> backend/.env giờ là bản $1. Khởi động lại backend..."
docker compose restart backend scheduler >/dev/null

# Đợi backend khởi động xong (tối đa khoảng 3 phút)
for i in $(seq 1 60); do
    [ "$(docker compose ps backend --format '{{.Health}}')" = "healthy" ] && break
    sleep 3
done

echo ">> Backend báo môi trường: $(curl -s http://localhost:8000/api/he-thong)"
echo ">> Xong. Mở http://localhost:8080 và nhìn nhãn môi trường ở góc trên bên phải."
