// Trang Học viên (chỉ kế toán): danh sách, tìm kiếm, thêm, sửa, xem hóa đơn của từng học viên.

const taiKhoan = batBuocDangNhap();

// Khai báo trước khi gọi chay() ở bên dưới (let chưa khai báo thì chưa dùng được)
let hanGioTim = null;   // hẹn giờ chờ người dùng gõ xong mới tìm
let soLanTai = 0;       // đánh số mỗi lần tải để bỏ qua kết quả cũ về muộn
let tuKhoa = "";

// Học viên không có trang này. Backend cũng chặn (403), đây chỉ để khỏi hiện trang trống.
if (taiKhoan && taiKhoan.vai_tro !== "ke_toan") {
    window.location.replace("tong-quan.html");
} else if (taiKhoan) {
    chay();
}

function chay() {
    veKhung("hoc-vien", taiKhoan);

    document.getElementById("bieu-tuong-tim").append(taoBieuTuong("tim"));
    document.getElementById("nut-them").append(taoBieuTuong("them"), "Thêm học viên");
    document.getElementById("nut-them").addEventListener("click", () => moHopSoanHocVien(null));

    document.getElementById("o-tim").addEventListener("input", function () {
        clearTimeout(hanGioTim);
        const chu = this.value.trim();
        hanGioTim = setTimeout(function () {
            tuKhoa = chu;
            taiDanhSach();
        }, 300);
    });

    taiDanhSach();
}

async function taiDanhSach() {
    const luot = ++soLanTai;
    const than = document.getElementById("bang-hoc-vien");

    document.getElementById("trang-thai-rong").hidden = true;
    document.querySelector("table").hidden = false;
    than.replaceChildren(...[0, 1, 2, 3].map(() =>
        tao("tr", {}, tao("td", { colspan: 6 }, tao("div", { class: "xuong", style: "height:20px" })))));

    const ketQua = await goiApi("/hoc-vien" + (tuKhoa ? "?q=" + encodeURIComponent(tuKhoa) : ""));

    if (luot !== soLanTai) {
        return;
    }
    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    if (!ketQua.ok) {
        than.replaceChildren();
        veTrangThaiRong("Không tải được danh sách", layThongBaoLoi(ketQua), "Thử lại", taiDanhSach);
        return;
    }

    const danhSach = ketQua.du_lieu.du_lieu;
    document.getElementById("dem-hoc-vien").textContent = danhSach.length + " học viên";

    if (danhSach.length === 0) {
        than.replaceChildren();
        if (tuKhoa) {
            veTrangThaiRong("Không tìm thấy học viên nào", "Thử tên hoặc email khác.");
        } else {
            veTrangThaiRong("Chưa có học viên nào", "Hãy thêm hồ sơ học viên đầu tiên.", "Thêm học viên", () => moHopSoanHocVien(null));
        }
        return;
    }

    veBang(danhSach);
}

function veTrangThaiRong(tieuDe, moTa, chuNut, khiBam) {
    document.querySelector("table").hidden = true;
    const noiDung = [tao("h3", {}, tieuDe), tao("p", {}, moTa)];

    if (chuNut) {
        const nut = tao("button", { type: "button", class: "nut nut-chinh" }, chuNut);
        nut.addEventListener("click", khiBam);
        noiDung.push(nut);
    }

    const khung = document.getElementById("trang-thai-rong");
    khung.replaceChildren(tao("div", { class: "rong" }, ...noiDung));
    khung.hidden = false;
}

function veBang(danhSach) {
    document.getElementById("bang-hoc-vien").replaceChildren(...danhSach.map(function (hocVien) {
        const nutXem = tao("button", { type: "button", class: "nut nut-phu nut-nho" }, taoBieuTuong("xem"), "Xem");
        const nutSua = tao("button", { type: "button", class: "nut nut-phu nut-nho", "aria-label": "Sửa " + hocVien.ten }, taoBieuTuong("sua"), "Sửa");
        nutXem.addEventListener("click", () => moChiTiet(hocVien.id));
        nutSua.addEventListener("click", () => moHopSoanHocVien(hocVien));

        return tao("tr", {},
            taoO("Học viên", tao("div", { class: "o-hoc-vien" }, taoAnhDaiDien(hocVien.ten),
                tao("div", {}, tao("span", { class: "ten" }, hocVien.ten), tao("small", {}, hocVien.email || "Chưa có email")))),
            taoO("Tài khoản", tao("span", { class: "nhan trung-tinh" }, hocVien.co_tai_khoan ? "Có tài khoản" : "Chỉ có hồ sơ")),
            taoO("Hóa đơn", String(hocVien.so_hoa_don), "so"),
            taoO("Đã thu", dinhDangTien(hocVien.da_thu) + " đ", "so"),
            taoO("Chưa thu", hocVien.chua_thu > 0 ? tao("b", { style: "color:var(--vang)" }, dinhDangTien(hocVien.chua_thu) + " đ") : "0 đ", "so"),
            tao("td", { class: "hanh-dong" }, nutXem, nutSua));
    }));
}

// ---------- Xem chi tiết ----------

async function moChiTiet(id) {
    const ketQua = await goiApi("/hoc-vien/" + id);

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    if (!ketQua.ok) {
        hienToast(layThongBaoLoi(ketQua), true);
        return;
    }

    const h = ketQua.du_lieu.du_lieu;
    let hop;

    const danhSachHoaDon = h.hoa_don.length === 0
        ? tao("p", { class: "ghi-chu" }, "Học viên này chưa có hóa đơn.")
        : tao("div", { class: "danh-sach-nho" }, h.hoa_don.map((hd) =>
            tao("div", { class: "dong" },
                tao("span", {}, tao("b", {}, hd.ma_hoa_don), " ", tao("span", { class: "ghi-chu" }, hd.ngay_tao || "")),
                tao("span", {}, tao("b", {}, dinhDangTien(hd.so_tien) + " đ"), " ", taoNhanTrangThai(hd.trang_thai)))));

    const nutDong = tao("button", { type: "button", class: "nut nut-phu" }, "Đóng");
    const nutXemHoaDon = tao("a", { class: "nut nut-chinh", href: "hoa-don.html?q=" + encodeURIComponent(h.ten) }, "Xem trong trang Hóa đơn");
    nutDong.addEventListener("click", () => hop.close());

    hop = moHopThoai([
        taoDauHop("Hồ sơ học viên", () => hop),
        tao("div", { class: "hop-than" },
            tao("div", { class: "o-hoc-vien" }, taoAnhDaiDien(h.ten),
                tao("div", {}, tao("span", { class: "ten", style: "font-size:1.1rem" }, h.ten), tao("small", {}, h.email || "Chưa có email"))),
            tao("div", { class: "so-nho" },
                tao("div", {}, tao("b", {}, String(h.so_hoa_don)), tao("span", {}, "hóa đơn")),
                tao("div", {}, tao("b", {}, dinhDangTien(h.da_thu) + " đ"), tao("span", {}, "đã thu")),
                tao("div", {}, tao("b", {}, dinhDangTien(h.chua_thu) + " đ"), tao("span", {}, "chưa thu"))),
            danhSachHoaDon),
        tao("div", { class: "hop-chan" }, nutDong, h.so_hoa_don > 0 ? nutXemHoaDon : null),
    ], { rong: true });
}

// ---------- Thêm, sửa ----------

// hocVien = null: thêm mới. Có giá trị: sửa hồ sơ đó.
function moHopSoanHocVien(hocVien) {
    const dangSua = hocVien !== null;
    const khoaEmail = dangSua && hocVien.co_tai_khoan;

    const oTen = tao("input", { name: "ten", type: "text", required: true, maxlength: 100, placeholder: "Nguyễn Văn A", autocomplete: "off" });
    const oEmail = tao("input", { name: "email", type: "email", maxlength: 150, placeholder: "ten@example.com", disabled: khoaEmail, autocomplete: "off" });

    if (dangSua) {
        oTen.value = hocVien.ten;
        oEmail.value = hocVien.email || "";
    }

    const form = tao("form", { id: "form-hoc-vien", class: "hop-than" },
        tao("label", { class: "truong" }, tao("span", { class: "ten" }, "Họ và tên"), oTen),
        tao("label", { class: "truong" },
            tao("span", { class: "ten" }, "Email"),
            oEmail,
            tao("small", {}, khoaEmail
                ? "Học viên đã có tài khoản nên email phải khớp với email đăng nhập, không đổi ở đây."
                : "Có thể để trống. Khi học viên đăng ký bằng đúng email này, hệ thống tự nối tài khoản vào hồ sơ.")));

    const nutHuy = tao("button", { type: "button", class: "nut nut-phu" }, "Hủy");
    const nutLuu = tao("button", { type: "submit", form: "form-hoc-vien", class: "nut nut-chinh" }, dangSua ? "Lưu thay đổi" : "Thêm học viên");

    let hop;
    hop = moHopThoai([taoDauHop(dangSua ? "Sửa học viên" : "Thêm học viên", () => hop), form, tao("div", { class: "hop-chan" }, nutHuy, nutLuu)]);
    nutHuy.addEventListener("click", () => hop.close());
    oTen.focus();

    form.addEventListener("submit", async function (suKien) {
        suKien.preventDefault();

        // Ô email bị khóa thì không gửi (backend cũng bỏ qua)
        const duLieu = { ten: oTen.value.trim(), email: khoaEmail ? hocVien.email : oEmail.value.trim() };
        const ketQua = await khiDangXuLy(nutLuu, () => goiApi(dangSua ? "/hoc-vien/" + hocVien.id : "/hoc-vien", {
            method: dangSua ? "PUT" : "POST",
            body: duLieu,
        }));

        if (xuLyHetPhienDangNhap(ketQua)) {
            return;
        }
        if (!ketQua.ok) {
            if (!hienLoiTungO(form, ketQua)) {
                hienToast(layThongBaoLoi(ketQua), true);
            }
            return;
        }

        hop.close();
        hienToast(ketQua.du_lieu.thong_bao, false);
        taiDanhSach();
    });
}
