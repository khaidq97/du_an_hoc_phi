// Trang đăng nhập.

// Đã đăng nhập rồi thì vào thẳng trang Tổng quan
if (localStorage.getItem("token")) {
    window.location.replace("tong-quan.html");
}

// Trang trí: logo và các dấu tích trong khung bên trái
document.getElementById("logo-banner").append(taoBieuTuong("logo"));
document.querySelectorAll("[data-bieu-tuong]").forEach(function (li) {
    li.prepend(taoBieuTuong(li.dataset.bieuTuong));
});

// Cho biết đang ở môi trường nào. Chỉ ở Local mới gợi ý tài khoản demo (nơi duy nhất có dữ liệu mẫu).
chenDaiMoiTruong();
taoNhanMoiTruong().then(function (nhan) {
    document.getElementById("cho-moi-truong").append(nhan);
    if (document.body.dataset.moiTruong === "local") {
        document.getElementById("goi-y-demo").hidden = false;
    }
});

document.querySelectorAll("[data-email]").forEach(function (nut) {
    nut.addEventListener("click", function () {
        document.getElementById("email").value = nut.dataset.email;
        document.getElementById("password").focus();
    });
});

document.getElementById("form-dang-nhap").addEventListener("submit", async function (suKien) {
    suKien.preventDefault(); // không để trình duyệt tự tải lại trang

    const nut = this.querySelector("button[type=submit]");

    const ketQua = await khiDangXuLy(nut, () => goiApi("/dang-nhap", {
        method: "POST",
        body: {
            email: this.email.value,
            password: this.password.value,
        },
    }));

    if (!ketQua.ok) {
        hienThongBao(layThongBaoLoi(ketQua), true);
        return;
    }

    // Lưu thẻ ra vào và thông tin người dùng để các trang sau dùng
    localStorage.setItem("token", ketQua.du_lieu.token);
    localStorage.setItem("tai_khoan", JSON.stringify(ketQua.du_lieu.tai_khoan));
    window.location.href = "tong-quan.html";
});
