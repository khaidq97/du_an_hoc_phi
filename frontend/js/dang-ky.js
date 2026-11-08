// Trang đăng ký.
document.getElementById("form-dang-ky").addEventListener("submit", async function (suKien) {
    suKien.preventDefault();

    const nut = this.querySelector("button[type=submit]");
    nut.disabled = true;

    const ketQua = await goiApi("/dang-ky", {
        method: "POST",
        body: {
            ten: this.ten.value,
            email: this.email.value,
            password: this.password.value,
        },
    });

    if (!ketQua.ok) {
        hienThongBao(layThongBaoLoi(ketQua), true);
        nut.disabled = false;
        return;
    }

    hienThongBao(ketQua.du_lieu.thong_bao + ". Đang chuyển sang trang đăng nhập...", false);
    setTimeout(function () {
        window.location.href = "dang-nhap.html";
    }, 1500);
});
