// Trang Hóa đơn: tìm kiếm, lọc, phân trang, tạo hóa đơn, ghi nhận đóng tiền, xóa, tải CSV.
// Bộ lọc nằm trên thanh địa chỉ (?q=...&trang_thai=...) nên tải lại trang hay gửi link vẫn giữ nguyên.

const taiKhoan = batBuocDangNhap();
const laKeToan = taiKhoan && taiKhoan.vai_tro === "ke_toan";

// Bộ lọc hiện tại. Rỗng nghĩa là không lọc.
const boLoc = { q: "", trang_thai: "", tu_ngay: "", den_ngay: "", per_page: "15", page: 1 };
let soLanTai = 0;           // đánh số mỗi lần tải để bỏ qua kết quả cũ về muộn
let hhenGioTim = null;     // hẹn giờ chờ người dùng gõ xong mới tìm

if (taiKhoan) {
    chay();
}

function chay() {
    veKhung("hoa-don", taiKhoan);

    document.getElementById("bieu-tuong-tim").append(taoBieuTuong("tim"));
    document.getElementById("mo-ta-trang").textContent = laKeToan
        ? "Toàn bộ hóa đơn của trung tâm."
        : "Hóa đơn của bạn. Bạn chỉ thấy hóa đơn của chính mình.";

    if (laKeToan) {
        document.getElementById("nut-ke-toan").hidden = false;
        document.getElementById("nut-tai-csv").append(taoBieuTuong("taixuong"), "Tải CSV");
        document.getElementById("nut-tao").append(taoBieuTuong("them"), "Tạo hóa đơn");
        document.getElementById("nut-tao").addEventListener("click", moHopTaoHoaDon);
        document.getElementById("nut-tai-csv").addEventListener("click", taiCsv);
    }

    document.getElementById("nut-trang-truoc").append(taoBieuTuong("truoc"));
    document.getElementById("nut-trang-sau").append(taoBieuTuong("sau"));

    docBoLocTuDiaChi();
    ganSuKien();
    taiDanhSach();

    // Từ trang Tổng quan bấm "Tạo hóa đơn" sẽ mở sẵn hộp thoại
    if (laKeToan && new URLSearchParams(location.search).get("tao") === "1") {
        moHopTaoHoaDon();
    }
}

// ---------- Bộ lọc ----------

function docBoLocTuDiaChi() {
    const thamSo = new URLSearchParams(location.search);
    Object.keys(boLoc).forEach(function (khoa) {
        if (thamSo.has(khoa)) {
            boLoc[khoa] = thamSo.get(khoa);
        }
    });
    boLoc.page = parseInt(boLoc.page, 10) || 1;

    document.getElementById("o-tim").value = boLoc.q;
    document.getElementById("tu-ngay").value = boLoc.tu_ngay;
    document.getElementById("den-ngay").value = boLoc.den_ngay;
    document.getElementById("so-dong").value = boLoc.per_page;
    capNhatNutTrangThai();
}

// Chuỗi tham số gửi lên backend và ghi lên thanh địa chỉ. Bỏ qua ô trống.
function taoThamSo(kemTrang) {
    const thamSo = new URLSearchParams();
    ["q", "trang_thai", "tu_ngay", "den_ngay"].forEach(function (khoa) {
        if (boLoc[khoa]) {
            thamSo.set(khoa, boLoc[khoa]);
        }
    });
    if (kemTrang) {
        thamSo.set("per_page", boLoc.per_page);
        thamSo.set("page", boLoc.page);
    }
    return thamSo;
}

function coBoLoc() {
    return Boolean(boLoc.q || boLoc.trang_thai || boLoc.tu_ngay || boLoc.den_ngay);
}

function capNhatNutTrangThai() {
    document.querySelectorAll("#loc-trang-thai button").forEach(function (nut) {
        nut.setAttribute("aria-pressed", String(nut.dataset.giaTri === boLoc.trang_thai));
    });
    document.getElementById("nut-xoa-loc").hidden = !coBoLoc();
}

// Đổi bộ lọc thì quay về trang 1
function datBoLoc(thayDoi) {
    Object.assign(boLoc, thayDoi, { page: 1 });
    capNhatNutTrangThai();
    taiDanhSach();
}

function ganSuKien() {
    // Gõ xong 300 mili giây mới tìm, đỡ gọi backend sau mỗi phím
    document.getElementById("o-tim").addEventListener("input", function () {
        clearTimeout(hhenGioTim);
        const chu = this.value.trim();
        hhenGioTim = setTimeout(() => datBoLoc({ q: chu }), 300);
    });

    document.querySelectorAll("#loc-trang-thai button").forEach(function (nut) {
        nut.addEventListener("click", () => datBoLoc({ trang_thai: nut.dataset.giaTri }));
    });
    document.getElementById("tu-ngay").addEventListener("change", function () { datBoLoc({ tu_ngay: this.value }); });
    document.getElementById("den-ngay").addEventListener("change", function () { datBoLoc({ den_ngay: this.value }); });
    document.getElementById("so-dong").addEventListener("change", function () { datBoLoc({ per_page: this.value }); });

    document.getElementById("nut-xoa-loc").addEventListener("click", function () {
        document.getElementById("o-tim").value = "";
        document.getElementById("tu-ngay").value = "";
        document.getElementById("den-ngay").value = "";
        datBoLoc({ q: "", trang_thai: "", tu_ngay: "", den_ngay: "" });
    });

    document.getElementById("nut-trang-truoc").addEventListener("click", () => doiTrang(boLoc.page - 1));
    document.getElementById("nut-trang-sau").addEventListener("click", () => doiTrang(boLoc.page + 1));
}

function doiTrang(trang) {
    boLoc.page = trang;
    taiDanhSach();
    window.scrollTo({ top: 0, behavior: "smooth" });
}

// ---------- Tải và vẽ danh sách ----------

function veKhungCho() {
    const than = document.getElementById("bang-hoa-don");
    than.replaceChildren();
    for (let i = 0; i < 6; i++) {
        than.append(tao("tr", {}, tao("td", { colspan: 7 }, tao("div", { class: "xuong", style: "height:20px" }))));
    }
}

async function taiDanhSach() {
    const luot = ++soLanTai;
    const vung = document.getElementById("vung-bang");

    vung.setAttribute("aria-busy", "true");
    document.getElementById("trang-thai-rong").hidden = true;
    document.querySelector("#vung-bang table").hidden = false;
    veKhungCho();

    const ketQua = await goiApi("/hoa-don?" + taoThamSo(true));

    // Có lần tải mới hơn rồi thì bỏ kết quả này
    if (luot !== soLanTai) {
        return;
    }
    vung.setAttribute("aria-busy", "false");

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    if (!ketQua.ok) {
        veLoi(layThongBaoLoi(ketQua));
        return;
    }

    const { data, meta } = ketQua.du_lieu;

    // Đang ở trang không còn dữ liệu (ví dụ vừa xóa hết dòng của trang cuối): lùi về trang cuối
    if (data.length === 0 && meta.current_page > 1) {
        boLoc.page = meta.last_page;
        taiDanhSach();
        return;
    }

    history.replaceState(null, "", "?" + taoThamSo(true) + (location.hash || ""));
    veBang(data);
    veTrangThaiRong(data.length === 0);
    capNhatPhanTrang(meta);
}

function veLoi(noiDung) {
    document.getElementById("bang-hoa-don").replaceChildren();
    document.querySelector("#vung-bang table").hidden = true;
    document.getElementById("phan-trang").hidden = true;

    const nutThuLai = tao("button", { type: "button", class: "nut nut-phu" }, "Thử lại");
    nutThuLai.addEventListener("click", taiDanhSach);

    const khung = document.getElementById("trang-thai-rong");
    khung.replaceChildren(tao("div", { class: "rong" }, tao("h3", {}, "Không tải được danh sách"), tao("p", {}, noiDung), nutThuLai));
    khung.hidden = false;
}

function veTrangThaiRong(rong) {
    const khung = document.getElementById("trang-thai-rong");
    document.querySelector("#vung-bang table").hidden = rong;
    khung.hidden = !rong;

    if (!rong) {
        return;
    }

    const noiDung = [
        tao("h3", {}, coBoLoc() ? "Không tìm thấy hóa đơn nào" : "Chưa có hóa đơn nào"),
        tao("p", {}, coBoLoc() ? "Thử đổi từ khóa hoặc bỏ bớt bộ lọc." : (laKeToan ? "Hãy tạo hóa đơn đầu tiên." : "Khi trung tâm lập hóa đơn cho bạn, chúng sẽ hiện ở đây.")),
    ];

    if (coBoLoc()) {
        const nut = tao("button", { type: "button", class: "nut nut-phu" }, "Xóa bộ lọc");
        nut.addEventListener("click", () => document.getElementById("nut-xoa-loc").click());
        noiDung.push(nut);
    } else if (laKeToan) {
        const nut = tao("button", { type: "button", class: "nut nut-chinh" }, taoBieuTuong("them"), "Tạo hóa đơn");
        nut.addEventListener("click", moHopTaoHoaDon);
        noiDung.push(nut);
    }

    khung.replaceChildren(tao("div", { class: "rong" }, minhHoaRong(), ...noiDung));
}

// Hình minh họa đơn giản cho trạng thái rỗng (một tờ giấy và kính lúp)
function minhHoaRong() {
    const hinh = taoSvg("svg", { viewBox: "0 0 120 90", class: "hinh", "aria-hidden": "true", fill: "none", stroke: "currentColor", "stroke-width": "3", "stroke-linecap": "round", "stroke-linejoin": "round" });
    hinh.innerHTML = '<rect x="24" y="8" width="52" height="68" rx="8" opacity=".35"/><path d="M36 28h28M36 40h28M36 52h16" opacity=".6"/><circle cx="82" cy="58" r="16"/><path d="m94 70 12 12"/>';
    return hinh;
}

function capNhatPhanTrang(meta) {
    const nav = document.getElementById("phan-trang");
    nav.hidden = meta.total === 0;

    document.getElementById("thong-ke-trang").textContent =
        "Hiển thị " + meta.from + "–" + meta.to + " trên " + meta.total + " hóa đơn";
    document.getElementById("so-trang").textContent = meta.current_page + " / " + meta.last_page;
    document.getElementById("nut-trang-truoc").disabled = meta.current_page <= 1;
    document.getElementById("nut-trang-sau").disabled = meta.current_page >= meta.last_page;
}

function veBang(danhSach) {
    document.getElementById("bang-hoa-don").replaceChildren(...danhSach.map(function (hoaDon) {
        const daDong = hoaDon.trang_thai === "da_dong";

        // Nút thao tác chỉ kế toán thấy. Hóa đơn đã đóng thì khóa lại: không thu lần nữa, không xóa.
        // Đây chỉ là tiện cho người dùng; backend mới là nơi chặn thật sự.
        let thaoTac = null;
        if (laKeToan) {
            if (daDong) {
                thaoTac = tao("span", { class: "nut-khoa", title: "Hóa đơn đã đóng tiền, không thể xóa" }, taoBieuTuong("khoa"), "Đã khóa");
            } else {
                const nutThu = tao("button", { type: "button", class: "nut nut-phu nut-nho" }, taoBieuTuong("dung"), "Đã thu tiền");
                const nutXoa = tao("button", { type: "button", class: "nut nut-nguy-hiem nut-nho", title: "Xóa hóa đơn " + hoaDon.ma_hoa_don, "aria-label": "Xóa hóa đơn " + hoaDon.ma_hoa_don }, taoBieuTuong("xoa"));
                nutThu.addEventListener("click", () => thuTien(hoaDon, nutThu));
                nutXoa.addEventListener("click", () => xoaHoaDon(hoaDon, nutXoa));
                thaoTac = [nutThu, nutXoa];
            }
        }

        return tao("tr", {},
            taoO("Mã", hoaDon.ma_hoa_don, "ma"),
            taoO("Học viên", tao("div", { class: "o-hoc-vien" }, taoAnhDaiDien(hoaDon.hoc_vien), tao("span", { class: "ten" }, hoaDon.hoc_vien || ""))),
            taoO("Số tiền", dinhDangTien(hoaDon.so_tien) + " đ", "so tien"),
            taoO("Trạng thái", taoNhanTrangThai(hoaDon.trang_thai)),
            taoO("Ngày tạo", ngayHoacGach(hoaDon.ngay_tao)),
            taoO("Ngày thu", ngayHoacGach(hoaDon.ngay_thu)),
            tao("td", { class: "hanh-dong" }, thaoTac));
    }));
}

// ---------- Thao tác ----------

async function thuTien(hoaDon, nut) {
    const dongY = await xacNhan({
        tieuDe: "Ghi nhận đã thu tiền?",
        noiDung: "Hóa đơn " + hoaDon.ma_hoa_don + " của " + hoaDon.hoc_vien + " (" + dinhDangTien(hoaDon.so_tien) + " đ) sẽ chuyển sang Đã đóng. Hóa đơn đã đóng thì không thể xóa.",
        nutXacNhan: "Ghi nhận",
    });
    if (!dongY) {
        return;
    }

    const ketQua = await khiDangXuLy(nut, () => goiApi("/hoa-don/" + hoaDon.id + "/da-dong", { method: "POST" }));

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    hienToast(ketQua.ok ? ketQua.du_lieu.thong_bao : layThongBaoLoi(ketQua), !ketQua.ok);
    taiDanhSach();
}

async function xoaHoaDon(hoaDon, nut) {
    const dongY = await xacNhan({
        tieuDe: "Xóa hóa đơn " + hoaDon.ma_hoa_don + "?",
        noiDung: "Thao tác này không hoàn tác được.",
        nutXacNhan: "Xóa hóa đơn",
        nguyHiem: true,
    });
    if (!dongY) {
        return;
    }

    const ketQua = await khiDangXuLy(nut, () => goiApi("/hoa-don/" + hoaDon.id, { method: "DELETE" }));

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    hienToast(ketQua.ok ? ketQua.du_lieu.thong_bao : layThongBaoLoi(ketQua), !ketQua.ok);
    taiDanhSach();
}

// Tải CSV theo đúng bộ lọc đang chọn. Backend cần token nên không thể dùng thẻ <a href> thường:
// ta gọi bằng fetch, nhận về tệp rồi cho trình duyệt tải xuống.
async function taiCsv() {
    const nut = document.getElementById("nut-tai-csv");

    await khiDangXuLy(nut, async function () {
        try {
            const phanHoi = await fetch(API_URL + "/hoa-don/xuat-csv?" + taoThamSo(false), {
                headers: { Authorization: "Bearer " + localStorage.getItem("token"), Accept: "text/csv" },
            });

            if (phanHoi.status === 401) {
                xuLyHetPhienDangNhap({ status: 401 });
                return;
            }
            if (!phanHoi.ok) {
                const loi = await phanHoi.json().catch(() => ({}));
                hienToast(layThongBaoLoi({ du_lieu: loi }), true);
                return;
            }

            const tep = await phanHoi.blob();
            const lienKet = tao("a", { href: URL.createObjectURL(tep), download: "hoa-don-" + new Date().toISOString().slice(0, 10) + ".csv" });
            document.body.append(lienKet);
            lienKet.click();
            lienKet.remove();
            URL.revokeObjectURL(lienKet.href);
            hienToast("Đã tải danh sách hóa đơn" + (coBoLoc() ? " (theo bộ lọc hiện tại)" : ""), false);
        } catch (loi) {
            hienToast("Không kết nối được máy chủ. Backend đã chạy chưa?", true);
        }
    });
}

// ---------- Hộp thoại tạo hóa đơn ----------

async function moHopTaoHoaDon() {
    const form = tao("form", { id: "form-tao-hoa-don", novalidate: false, class: "hop-than" },
        tao("label", { class: "truong" },
            tao("span", { class: "ten" }, "Mã hóa đơn"),
            tao("input", { name: "ma_hoa_don", type: "text", required: true, maxlength: 20, placeholder: "HD014", autocomplete: "off" })),
        tao("label", { class: "truong" },
            tao("span", { class: "ten" }, "Học viên"),
            tao("select", { name: "hoc_vien_id", required: true }, tao("option", { value: "" }, "Đang tải danh sách...")),
            ),
        tao("label", { class: "truong" },
            tao("span", { class: "ten" }, "Số tiền (đồng)"),
            tao("input", { name: "so_tien", type: "number", required: true, min: 1, step: 1, placeholder: "2000000", inputmode: "numeric" }),
            tao("small", { id: "xem-truoc-tien" }, "")));

    const nutHuy = tao("button", { type: "button", class: "nut nut-phu" }, "Hủy");
    const nutTao = tao("button", { type: "submit", form: "form-tao-hoa-don", class: "nut nut-chinh" }, "Tạo hóa đơn");

    let hop;
    const dau = taoDauHop("Tạo hóa đơn mới", () => hop);
    hop = moHopThoai([dau, form, tao("div", { class: "hop-chan" }, nutHuy, nutTao)]);
    nutHuy.addEventListener("click", () => hop.close());

    // Hiện số tiền đã định dạng ngay khi gõ để kế toán khỏi đếm số 0
    form.so_tien.addEventListener("input", function () {
        const so = Number(this.value);
        document.getElementById("xem-truoc-tien").textContent = so > 0 ? "= " + dinhDangTien(so) + " đ" : "";
    });

    form.addEventListener("submit", async function (suKien) {
        suKien.preventDefault();

        const ketQua = await khiDangXuLy(nutTao, () => goiApi("/hoa-don", {
            method: "POST",
            body: { ma_hoa_don: this.ma_hoa_don.value.trim(), hoc_vien_id: this.hoc_vien_id.value, so_tien: this.so_tien.value },
        }));

        if (xuLyHetPhienDangNhap(ketQua)) {
            return;
        }
        if (!ketQua.ok) {
            // Lỗi 422: gắn vào từng ô. Lỗi 409 (trùng mã): gắn vào ô mã. Lỗi khác: báo toast.
            if (!hienLoiTungO(this, ketQua)) {
                if (ketQua.status === 409) {
                    hienLoiTungO(this, { du_lieu: { errors: { ma_hoa_don: [layThongBaoLoi(ketQua)] } } });
                } else {
                    hienToast(layThongBaoLoi(ketQua), true);
                }
            }
            return;
        }

        hop.close();
        hienToast(ketQua.du_lieu.thong_bao, false);
        document.getElementById("nut-xoa-loc").click();
    });

    form.ma_hoa_don.focus();

    // Nạp danh sách học viên vào ô chọn
    const dsHocVien = await goiApi("/hoc-vien");
    const oChon = form.hoc_vien_id;
    if (!dsHocVien.ok) {
        oChon.replaceChildren(tao("option", { value: "" }, "Không tải được danh sách học viên"));
        return;
    }
    oChon.replaceChildren(
        tao("option", { value: "" }, "Chọn học viên"),
        ...dsHocVien.du_lieu.du_lieu.map((hocVien) => tao("option", { value: hocVien.id }, hocVien.ten + (hocVien.email ? " (" + hocVien.email + ")" : ""))));
}
