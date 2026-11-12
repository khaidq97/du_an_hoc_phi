// Các hàm dùng chung: gọi API, hiện thông báo.

// Gọi một địa chỉ API. Trả về { ok, status, du_lieu }.
// Nếu đã đăng nhập, tự gửi kèm token ("thẻ ra vào") trong header Authorization.
async function goiApi(duongDan, { method = "GET", body = null } = {}) {
    const headers = { Accept: "application/json" };
    const token = localStorage.getItem("token");

    if (token) {
        headers.Authorization = "Bearer " + token;
    }
    if (body) {
        headers["Content-Type"] = "application/json";
    }

    try {
        const phanHoi = await fetch(API_URL + duongDan, {
            method: method,
            headers: headers,
            body: body ? JSON.stringify(body) : undefined,
        });
        const du_lieu = await phanHoi.json().catch(() => ({}));

        return { ok: phanHoi.ok, status: phanHoi.status, du_lieu: du_lieu };
    } catch (loi) {
        // Không nối được tới backend (tắt máy chủ, sai địa chỉ, bị chặn CORS...)
        return {
            ok: false,
            status: 0,
            du_lieu: { thong_bao: "Không kết nối được máy chủ. Backend đã chạy chưa?" },
        };
    }
}

// Lấy câu thông báo lỗi từ kết quả API để hiện cho người dùng.
function layThongBaoLoi(ketQua) {
    const du_lieu = ketQua.du_lieu || {};

    // Lỗi 422 (kiểm tra dữ liệu): gom mọi câu lỗi của từng ô nhập
    if (du_lieu.errors) {
        return Object.values(du_lieu.errors).flat().join(". ");
    }

    return du_lieu.thong_bao || du_lieu.message || "Có lỗi xảy ra";
}

// Hiện thông báo trong khung có id "thong-bao" (trang đăng nhập, đăng ký).
// Thành công màu xanh, lỗi màu đỏ. Trang không có khung đó thì hiện dạng toast (xem khung.js).
function hienThongBao(noiDung, laLoi) {
    const khung = document.getElementById("thong-bao");

    if (!khung) {
        hienToast(noiDung, laLoi);
        return;
    }

    khung.textContent = noiDung; // textContent: không bao giờ chạy mã HTML lạ
    khung.className = "thong-bao " + (laLoi ? "loi" : "thanh-cong");
    khung.hidden = false;
}

// Hỏi backend đang chạy ở môi trường nào (local, testing, staging, production).
// Trả null nếu không kết nối được. Backend chỉ trả thông tin không bí mật.
async function taiThongTinHeThong() {
    const ketQua = await goiApi("/he-thong");
    return ketQua.ok ? ketQua.du_lieu : null;
}
