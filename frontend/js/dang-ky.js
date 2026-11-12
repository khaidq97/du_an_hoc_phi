// Trang đăng ký.

document.getElementById("logo-banner").append(taoBieuTuong("logo"));
document.querySelectorAll("[data-bieu-tuong]").forEach(function (li) {
    li.prepend(taoBieuTuong(li.dataset.bieuTuong));
});

chenDaiMoiTruong();
taoNhanMoiTruong().then((nhan) => document.getElementById("cho-moi-truong").append(nhan));

document.getElementById("form-dang-ky").addEventListener("submit", async function (suKien) {
    suKien.preventDefault();

    const nut = this.querySelector("button[type=submit]");

    const ketQua = await khiDangXuLy(nut, () => goiApi("/dang-ky", {
        method: "POST",
        body: {
            ten: this.ten.value,
            email: this.email.value,
            password: this.password.value,
        },
    }));

    if (!ketQua.ok) {
        hienThongBao(layThongBaoLoi(ketQua), true);
        return;
    }

    nut.disabled = true;
    hienThongBao(ketQua.du_lieu.thong_bao + ". Đang chuyển sang trang đăng nhập...", false);
    setTimeout(function () {
        window.location.href = "dang-nhap.html";
    }, 1500);
});
