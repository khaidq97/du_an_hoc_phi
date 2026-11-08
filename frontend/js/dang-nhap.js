// Trang đăng nhập.

// Đã đăng nhập rồi thì vào thẳng danh sách hóa đơn
if (localStorage.getItem("token")) {
    window.location.replace("hoa-don.html");
}

document.getElementById("form-dang-nhap").addEventListener("submit", async function (suKien) {
    suKien.preventDefault(); // không để trình duyệt tự tải lại trang

    const nut = this.querySelector("button[type=submit]");
    nut.disabled = true;

    const ketQua = await goiApi("/dang-nhap", {
        method: "POST",
        body: {
            email: this.email.value,
            password: this.password.value,
        },
    });

    if (!ketQua.ok) {
        hienThongBao(layThongBaoLoi(ketQua), true);
        nut.disabled = false;
        return;
    }

    // Lưu thẻ ra vào và thông tin người dùng để các trang sau dùng
    localStorage.setItem("token", ketQua.du_lieu.token);
    localStorage.setItem("tai_khoan", JSON.stringify(ketQua.du_lieu.tai_khoan));
    window.location.href = "hoa-don.html";
});
