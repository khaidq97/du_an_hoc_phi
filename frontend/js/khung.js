// Phần dùng chung của các trang sau khi đăng nhập: khung giao diện (thanh bên, thanh trên),
// nhãn môi trường, thông báo nhanh (toast), hộp thoại xác nhận, định dạng số tiền.
// Cần nạp sau api.js.

// ---------- Biểu tượng ----------
// Các hình vẽ dưới đây là chuỗi CỐ ĐỊNH do ta viết, không chứa dữ liệu của người dùng,
// nên dùng innerHTML ở đây là an toàn. Dữ liệu người dùng luôn đi qua textContent.
const BIEU_TUONG = {
    logo: '<path d="M22 10 12 4 2 10l10 6 10-6z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/><path d="M22 10v6"/>',
    tongquan: '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    hoadon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
    hocvien: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    taixuong: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    them: '<path d="M12 5v14"/><path d="M5 12h14"/>',
    tim: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    khoa: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    dung: '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/>',
    xoa: '<path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
    sua: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    thoat: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    dong: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    vi: '<path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4z"/>',
    dongho: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    xem: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    canhbao: '<path d="m10.3 3.9-8.5 14.7A2 2 0 0 0 3.5 21.6h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    nguoi: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    truoc: '<path d="m15 18-6-6 6-6"/>',
    sau: '<path d="m9 18 6-6-6-6"/>',
};

function taoBieuTuong(ten) {
    const hinh = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    hinh.setAttribute("viewBox", "0 0 24 24");
    hinh.setAttribute("class", "bieu-tuong");
    hinh.setAttribute("aria-hidden", "true");
    hinh.innerHTML = BIEU_TUONG[ten] || "";
    return hinh;
}

// Tạo phần tử HTML ngắn gọn: tao("button", { class: "nut" }, "Chữ", bieuTuong...)
function tao(tenThe, thuocTinh, ...conCai) {
    const phanTu = document.createElement(tenThe);

    Object.entries(thuocTinh || {}).forEach(function ([khoa, giaTri]) {
        if (giaTri === false || giaTri === null || giaTri === undefined) {
            return;
        }
        phanTu.setAttribute(khoa, giaTri === true ? "" : giaTri);
    });
    conCai.flat().forEach(function (con) {
        if (con === null || con === undefined || con === false) {
            return;
        }
        // Chuỗi đi qua createTextNode nên không bao giờ chạy như mã HTML
        phanTu.append(con instanceof Node ? con : document.createTextNode(String(con)));
    });
    return phanTu;
}

// Tạo phần tử SVG (vẽ biểu đồ, vòng tròn...). Phần tử SVG cần "không gian tên" riêng nên không dùng được tao().
function taoSvg(tenThe, thuocTinh, ...con) {
    const phanTu = document.createElementNS("http://www.w3.org/2000/svg", tenThe);
    Object.entries(thuocTinh || {}).forEach(([khoa, giaTri]) => phanTu.setAttribute(khoa, giaTri));
    con.forEach((c) => phanTu.append(c));
    return phanTu;
}

// Một ô <td> của bảng. "nhan" là tên cột: trên điện thoại bảng biến thành thẻ và ô tự hiện tên cột.
function taoO(nhan, noiDung, lop) {
    return tao("td", { "data-nhan": nhan, class: lop || false }, noiDung);
}

// Nhãn trạng thái hóa đơn: Đã đóng (xanh), Chưa đóng (vàng)
function taoNhanTrangThai(trangThai) {
    const daDong = trangThai === "da_dong";
    return tao("span", { class: "nhan " + (daDong ? "da-dong" : "chua-dong") }, daDong ? "Đã đóng" : "Chưa đóng");
}

// ---------- Đăng nhập, quyền ----------

function layTaiKhoan() {
    return JSON.parse(localStorage.getItem("tai_khoan") || "{}");
}

// Trang nào cũng gọi hàm này đầu tiên: chưa đăng nhập thì về trang đăng nhập.
function batBuocDangNhap() {
    if (!localStorage.getItem("token")) {
        window.location.replace("dang-nhap.html");
        return null;
    }
    return layTaiKhoan();
}

function xoaPhienDangNhap() {
    localStorage.removeItem("token");
    localStorage.removeItem("tai_khoan");
}

// Token hết hạn hoặc bị hủy (backend trả 401): xóa thông tin đăng nhập, về trang đăng nhập.
function xuLyHetPhienDangNhap(ketQua) {
    if (ketQua.status === 401) {
        xoaPhienDangNhap();
        window.location.replace("dang-nhap.html");
        return true;
    }
    return false;
}

async function dangXuat() {
    await goiApi("/dang-xuat", { method: "POST" });
    xoaPhienDangNhap();
    window.location.href = "dang-nhap.html";
}

// ---------- Định dạng ----------

function dinhDangTien(soTien) {
    return Number(soTien || 0).toLocaleString("vi-VN");
}

// Số tiền lớn viết gọn cho thẻ số liệu: 26.100.000 -> "26,1 triệu"
function dinhDangTienGon(soTien) {
    const so = Number(soTien || 0);
    if (so >= 1e9) {
        return (so / 1e9).toLocaleString("vi-VN", { maximumFractionDigits: 1 }) + " tỷ";
    }
    if (so >= 1e6) {
        return (so / 1e6).toLocaleString("vi-VN", { maximumFractionDigits: 1 }) + " triệu";
    }
    return so.toLocaleString("vi-VN");
}

function chuCaiDau(ten) {
    const tu = String(ten || "?").trim().split(/\s+/);
    return (tu[tu.length - 1][0] || "?").toUpperCase();
}

function taoAnhDaiDien(ten) {
    return tao("span", { class: "anh-dai-dien", "aria-hidden": "true" }, chuCaiDau(ten));
}

// ---------- Môi trường ----------
// Mỗi môi trường một màu: nhìn một cái là biết đang ở bản nào (bài 5: "khách đang xem bản nào?").
const MOI_TRUONG = {
    local: { ten: "Local", chuThich: "Máy của lập trình viên (Development). Dữ liệu giả, thử thoải mái." },
    testing: { ten: "Test", chuThich: "Môi trường kiểm thử: tester kiểm tra theo tài liệu trước khi phát hành." },
    staging: { ten: "Staging", chuThich: "Bản sao của Production để khách kiểm thử và nghiệm thu (UAT). Dữ liệu phải che thông tin nhạy cảm." },
    production: { ten: "Production", chuThich: "Hệ thống thật, người dùng thật. Không thử nghiệm, không tự sửa." },
};

// Tô màu dải đầu trang và trả về nhãn môi trường để gắn vào giao diện.
async function taoNhanMoiTruong() {
    const thongTin = await taiThongTinHeThong();
    const ma = thongTin ? thongTin.moi_truong : null;
    const mo_ta = MOI_TRUONG[ma] || {
        ten: ma || "Chưa kết nối",
        chuThich: ma ? "Môi trường " + ma : "Không kết nối được backend để biết môi trường.",
    };

    if (ma) {
        document.body.dataset.moiTruong = ma;
    }

    const nhan = tao("span", {
        class: "nhan-moi-truong",
        title: mo_ta.chuThich,
        role: "status",
        "aria-label": "Môi trường: " + mo_ta.ten,
    }, mo_ta.ten);
    nhan.style.setProperty("--mau-mt", "var(--mt-" + (MOI_TRUONG[ma] ? ma : "local") + ")");
    return nhan;
}

// Chèn dải màu mỏng ở đầu trang (mọi trang đều có, kể cả trang đăng nhập)
function chenDaiMoiTruong() {
    if (!document.querySelector(".dai-moi-truong")) {
        document.body.prepend(tao("div", { class: "dai-moi-truong", "aria-hidden": "true" }));
    }
}

// ---------- Khung giao diện ----------

const MUC_MENU = [
    { khoa: "tong-quan", chu: "Tổng quan", trang: "tong-quan.html", bieuTuong: "tongquan", chiKeToan: false },
    { khoa: "hoa-don", chu: "Hóa đơn", trang: "hoa-don.html", bieuTuong: "hoadon", chiKeToan: false },
    { khoa: "hoc-vien", chu: "Học viên", trang: "hoc-vien.html", bieuTuong: "hocvien", chiKeToan: true },
];

// Dựng thanh bên và thanh trên. Trang HTML chỉ cần có sẵn <aside id="thanh-ben"> và <header id="thanh-tren">.
async function veKhung(trangHienTai, taiKhoan) {
    const laKeToan = taiKhoan.vai_tro === "ke_toan";

    chenDaiMoiTruong();

    const logo = function () {
        return tao("a", { class: "logo", href: "tong-quan.html" },
            tao("span", { class: "logo-hinh" }, taoBieuTuong("logo")),
            "Hoc Phi");
    };

    // Thanh bên
    const menu = tao("nav", { class: "menu", "aria-label": "Điều hướng chính" },
        MUC_MENU.filter((muc) => laKeToan || !muc.chiKeToan).map(function (muc) {
            return tao("a", { href: muc.trang, "aria-current": muc.khoa === trangHienTai ? "page" : false },
                taoBieuTuong(muc.bieuTuong), muc.chu);
        }));

    const nutThoat = tao("button", { type: "button", class: "nut-icon", title: "Đăng xuất", "aria-label": "Đăng xuất" }, taoBieuTuong("thoat"));
    nutThoat.addEventListener("click", dangXuat);

    document.getElementById("thanh-ben").replaceChildren(
        logo(),
        menu,
        tao("div", { class: "the-nguoi-dung" },
            taoAnhDaiDien(taiKhoan.ten),
            tao("div", { class: "thong-tin" },
                tao("div", { class: "ten" }, taiKhoan.ten || ""),
                tao("div", { class: "vai-tro" }, laKeToan ? "Kế toán" : "Học viên")),
            nutThoat));

    // Thanh trên: logo (chỉ hiện trên điện thoại), nhãn môi trường, nút đăng xuất nhỏ (điện thoại)
    const nutThoatNho = tao("button", { type: "button", class: "nut-icon nut-dang-xuat-nho", title: "Đăng xuất", "aria-label": "Đăng xuất" }, taoBieuTuong("thoat"));
    nutThoatNho.addEventListener("click", dangXuat);

    const khungTren = document.getElementById("thanh-tren");
    khungTren.replaceChildren(logo(), tao("span", { style: "flex:1" }), nutThoatNho);

    const nhan = await taoNhanMoiTruong();
    khungTren.insertBefore(nhan, nutThoatNho);
}

// ---------- Toast (thông báo nhanh) ----------

function hienToast(noiDung, laLoi) {
    let khung = document.getElementById("toast-khung");
    if (!khung) {
        khung = tao("div", { id: "toast-khung", class: "toast-khung", "aria-live": "polite" });
        document.body.append(khung);
    }

    const toast = tao("div", { class: "toast" + (laLoi ? " loi" : ""), role: laLoi ? "alert" : "status" },
        taoBieuTuong(laLoi ? "canhbao" : "dung"),
        tao("span", {}, noiDung));
    khung.append(toast);

    setTimeout(function () {
        toast.classList.add("dang-an");
        setTimeout(() => toast.remove(), 300);
    }, laLoi ? 6000 : 3500);
}

// ---------- Hộp thoại ----------

// Mở một hộp thoại <dialog> và gỡ nó khỏi trang khi đóng.
function moHopThoai(noiDung, tuyChon = {}) {
    const hop = tao("dialog", { class: tuyChon.rong ? "rong-hon" : false, "aria-labelledby": "tieu-de-hop" }, noiDung);

    hop.addEventListener("close", () => hop.remove());
    // Bấm ra ngoài vùng hộp thì đóng
    hop.addEventListener("click", function (suKien) {
        if (suKien.target === hop) {
            hop.close();
        }
    });

    document.body.append(hop);
    hop.showModal();
    return hop;
}

function taoDauHop(tieuDe, hop) {
    const nutDong = tao("button", { type: "button", class: "nut-icon", "aria-label": "Đóng" }, taoBieuTuong("dong"));
    nutDong.addEventListener("click", () => hop().close());
    return tao("div", { class: "hop-dau" }, tao("h2", { id: "tieu-de-hop" }, tieuDe), nutDong);
}

// Hỏi xác nhận trước khi làm việc không hoàn tác được. Trả Promise<true/false>.
function xacNhan({ tieuDe, noiDung, nutXacNhan = "Đồng ý", nguyHiem = false }) {
    return new Promise(function (xong) {
        let hop;
        const nutHuy = tao("button", { type: "button", class: "nut nut-phu" }, "Hủy");
        const nutDongY = tao("button", { type: "button", class: "nut " + (nguyHiem ? "nut-nguy-hiem" : "nut-chinh") }, nutXacNhan);

        let ketQua = false;
        nutHuy.addEventListener("click", () => hop.close());
        nutDongY.addEventListener("click", function () {
            ketQua = true;
            hop.close();
        });

        hop = moHopThoai([
            tao("div", { class: "hop-than" },
                tao("div", { class: "hop-nguy-hiem" },
                    tao("span", { class: "o-bieu-tuong" }, taoBieuTuong(nguyHiem ? "canhbao" : "dung")),
                    tao("div", {}, tao("h2", { id: "tieu-de-hop" }, tieuDe), tao("p", { class: "ghi-chu", style: "margin-top:6px" }, noiDung)))),
            tao("div", { class: "hop-chan" }, nutHuy, nutDongY),
        ]);
        hop.addEventListener("close", () => xong(ketQua));
    });
}

// ---------- Lỗi từng ô nhập ----------

// Gắn câu lỗi của backend (kết quả 422, dạng { errors: { ten_o: [câu lỗi] } }) vào đúng ô nhập.
// Trả true nếu có ít nhất một ô được gắn lỗi.
function hienLoiTungO(form, ketQua) {
    form.querySelectorAll("[aria-invalid]").forEach((o) => o.removeAttribute("aria-invalid"));
    form.querySelectorAll(".loi-truong").forEach((o) => o.remove());

    const loi = (ketQua.du_lieu || {}).errors;
    if (!loi) {
        return false;
    }

    let coGan = false;
    Object.entries(loi).forEach(function ([ten, cacCau]) {
        const o = form.elements[ten];
        if (!o) {
            return;
        }
        o.setAttribute("aria-invalid", "true");
        o.closest(".truong").append(tao("small", { class: "loi-truong", role: "alert" }, cacCau[0]));
        coGan = true;
    });
    return coGan;
}

// Bật trạng thái "đang xử lý" cho nút bấm trong lúc chờ backend, tránh bấm hai lần.
async function khiDangXuLy(nut, viec) {
    nut.disabled = true;
    nut.classList.add("dang-tai");
    try {
        return await viec();
    } finally {
        nut.disabled = false;
        nut.classList.remove("dang-tai");
    }
}

// Bỏ dấu và về chữ thường: dùng để so khớp chữ tiếng Việt không phân biệt dấu
function boDau(chu) {
    return String(chu).normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D").toLowerCase();
}

// Đổi ngày dd/mm/yyyy (backend trả) sang chữ gọn
function ngayHoacGach(ngay) {
    return ngay || "—";
}
