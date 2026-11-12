// Trang Tổng quan: thẻ số liệu, biểu đồ theo tháng, vòng tỷ lệ thu, hóa đơn gần đây.
// Số liệu do backend tính (GET /api/thong-ke); trang này chỉ vẽ lại.

const taiKhoan = batBuocDangNhap();
const laKeToan = taiKhoan && taiKhoan.vai_tro === "ke_toan";

if (taiKhoan) {
    chay();
}

async function chay() {
    veKhung("tong-quan", taiKhoan);

    const tenGoi = String(taiKhoan.ten || "").trim().split(/\s+/).pop();
    document.getElementById("loi-chao").textContent = "Xin chào, " + (tenGoi || "bạn");
    document.getElementById("ngay-hom-nay").textContent = new Date().toLocaleDateString("vi-VN", {
        weekday: "long", day: "numeric", month: "long", year: "numeric",
    });

    if (laKeToan) {
        const nut = document.getElementById("nut-tao-hoa-don");
        nut.append(taoBieuTuong("them"), "Tạo hóa đơn");
        document.getElementById("nut-nhanh").hidden = false;
    }

    veKhungCho();
    await taiSoLieu();
}

// Khung chờ trong lúc đợi backend trả số liệu
function veKhungCho() {
    const luoi = document.getElementById("luoi-so");
    luoi.replaceChildren();
    for (let i = 0; i < 4; i++) {
        luoi.append(tao("div", { class: "the the-so" },
            tao("div", { class: "xuong", style: "height:16px;width:50%" }),
            tao("div", { class: "xuong", style: "height:30px;width:70%" }),
            tao("div", { class: "xuong", style: "height:12px;width:40%" })));
    }
}

async function taiSoLieu() {
    const ketQua = await goiApi("/thong-ke");

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    if (!ketQua.ok) {
        const khung = document.getElementById("thong-bao");
        khung.textContent = layThongBaoLoi(ketQua);
        khung.hidden = false;
        document.getElementById("luoi-so").replaceChildren();
        document.getElementById("khung-bieu-do").replaceChildren();
        document.getElementById("khung-vong").replaceChildren();
        return;
    }

    const du_lieu = ketQua.du_lieu.du_lieu;
    veTheSo(du_lieu);
    veBieuDo(du_lieu.theo_thang);
    veVong(du_lieu);
    veGanDay(du_lieu.gan_day);
}

// ---------- Thẻ số liệu ----------

function taoTheSo({ nhan, giaTri, donVi, phu, bieuTuong, mau, tienDo }) {
    return tao("div", { class: "the the-so" },
        tao("div", { class: "hang" }, nhan, tao("span", { class: "o-bieu-tuong " + (mau || "") }, taoBieuTuong(bieuTuong))),
        tao("div", { class: "gia-tri" }, giaTri, donVi ? tao("small", {}, donVi) : null),
        tienDo !== undefined
            ? tao("div", { class: "thanh-tien-do", role: "img", "aria-label": tienDo + " phần trăm" }, tao("i", { style: "width:" + tienDo + "%" }))
            : null,
        tao("div", { class: "phu" }, phu));
}

function veTheSo(d) {
    const luoi = document.getElementById("luoi-so");
    luoi.replaceChildren(
        taoTheSo({ nhan: "Tổng đã lập", giaTri: dinhDangTienGon(d.tong_tien), donVi: "đ", phu: d.tong_hoa_don + " hóa đơn", bieuTuong: "hoadon", mau: "mau-tim" }),
        taoTheSo({ nhan: "Đã thu", giaTri: dinhDangTienGon(d.da_thu), donVi: "đ", phu: d.so_da_dong + " hóa đơn đã đóng (" + d.ty_le_thu + "%)", bieuTuong: "dung", mau: "mau-xanh", tienDo: d.ty_le_thu }),
        taoTheSo({ nhan: "Chưa thu", giaTri: dinhDangTienGon(d.chua_thu), donVi: "đ", phu: d.so_chua_dong + " hóa đơn chưa đóng", bieuTuong: "dongho", mau: "mau-vang" }),
        laKeToan
            ? taoTheSo({ nhan: "Học viên", giaTri: String(d.so_hoc_vien), phu: "hồ sơ đang quản lý", bieuTuong: "hocvien", mau: "mau-tim" })
            : taoTheSo({ nhan: "Hóa đơn của tôi", giaTri: String(d.tong_hoa_don), phu: d.so_chua_dong > 0 ? "còn " + d.so_chua_dong + " hóa đơn chưa đóng" : "bạn đã đóng đủ", bieuTuong: "vi", mau: "mau-tim" }));
}

// ---------- Biểu đồ cột (vẽ bằng SVG, không cần thư viện) ----------

// Làm tròn mức tối đa của trục lên con số "đẹp": 4,3 triệu -> 5 triệu
function mucToiDaDep(giaTri) {
    if (giaTri <= 0) {
        return 1000000;
    }
    const muMuoi = Math.pow(10, Math.floor(Math.log10(giaTri)));
    const phan = giaTri / muMuoi;
    const buoc = phan <= 1 ? 1 : phan <= 2 ? 2 : phan <= 5 ? 5 : 10;
    return buoc * muMuoi;
}

function veBieuDo(cacThang) {
    const R = 640, C = 250, le = { trai: 52, phai: 8, tren: 12, duoi: 30 };
    const toiDa = mucToiDaDep(Math.max(...cacThang.map((t) => Math.max(t.da_lap, t.da_thu))));
    const caoVung = C - le.tren - le.duoi;
    const rongNhom = (R - le.trai - le.phai) / cacThang.length;
    const rongCot = Math.min(26, rongNhom * 0.3);
    const y = (giaTri) => le.tren + caoVung - (giaTri / toiDa) * caoVung;

    const hinh = taoSvg("svg", { viewBox: "0 0 " + R + " " + C, class: "bieu-do", role: "img",
        "aria-label": "Biểu đồ cột tiền đã lập và đã thu của 6 tháng gần nhất" });

    // Đường kẻ ngang và nhãn trục dọc
    for (let i = 0; i <= 4; i++) {
        const giaTri = (toiDa / 4) * i;
        const vi = y(giaTri);
        hinh.append(taoSvg("line", { class: "luoi", x1: le.trai, x2: R - le.phai, y1: vi, y2: vi }));
        const nhan = taoSvg("text", { x: le.trai - 8, y: vi + 4, "text-anchor": "end" });
        nhan.textContent = giaTri === 0 ? "0" : (giaTri / 1e6).toLocaleString("vi-VN") + " tr";
        hinh.append(nhan);
    }

    cacThang.forEach(function (thang, i) {
        const tam = le.trai + rongNhom * i + rongNhom / 2;
        const nhom = taoSvg("g", { class: "nhom" });

        // Cột "đã lập" và cột "đã thu". Cột thấp nhất vẫn cao 2px nếu có tiền, để nhìn thấy được.
        [["cot-lap", thang.da_lap, tam - rongCot - 2], ["cot-thu", thang.da_thu, tam + 2]].forEach(function ([lop, giaTri, x]) {
            const cao = giaTri > 0 ? Math.max(2, le.tren + caoVung - y(giaTri)) : 0;
            nhom.append(taoSvg("rect", { class: lop, x: x, y: le.tren + caoVung - cao, width: rongCot, height: cao, rx: 5 }));
        });

        const goiY = taoSvg("title");
        goiY.textContent = "Tháng " + thang.nhan + ": đã lập " + dinhDangTien(thang.da_lap) + " đ, đã thu " + dinhDangTien(thang.da_thu) + " đ";
        nhom.append(goiY);
        // Vùng rê chuột phủ cả nhóm để hiện chú thích dễ hơn
        nhom.append(taoSvg("rect", { x: tam - rongNhom / 2, y: le.tren, width: rongNhom, height: caoVung, fill: "transparent" }));

        const nhanThang = taoSvg("text", { x: tam, y: C - 8, "text-anchor": "middle" });
        nhanThang.textContent = thang.nhan;
        nhom.append(nhanThang);
        hinh.append(nhom);
    });

    document.getElementById("khung-bieu-do").replaceChildren(hinh);
}

// ---------- Vòng tỷ lệ thu ----------

function veVong(d) {
    const BAN_KINH = 70, CHU_VI = 2 * Math.PI * BAN_KINH;
    const thu = taoSvg("circle", { class: "vong-thu", cx: 90, cy: 90, r: BAN_KINH, "stroke-dasharray": "0 " + CHU_VI });
    const hinh = taoSvg("svg", { viewBox: "0 0 180 180", role: "img", "aria-label": "Đã thu " + d.ty_le_thu + " phần trăm" },
        taoSvg("circle", { class: "nen-vong", cx: 90, cy: 90, r: BAN_KINH }), thu);

    document.getElementById("khung-vong").replaceChildren(
        tao("div", { class: "vong" },
            tao("div", { class: "vong-boc" },
                hinh,
                tao("div", { class: "giua" }, tao("b", {}, d.ty_le_thu + "%"), tao("span", {}, "đã thu")))),
        tao("div", { class: "chu-thich-doc" },
            tao("div", {}, tao("span", {}, tao("i"), "Đã thu"), tao("b", {}, dinhDangTien(d.da_thu) + " đ")),
            tao("div", {}, tao("span", {}, tao("i", { class: "chua" }), "Chưa thu"), tao("b", {}, dinhDangTien(d.chua_thu) + " đ"))));

    // Đợi một nhịp rồi mới đặt độ dài cung để CSS chạy hiệu ứng vẽ vòng
    requestAnimationFrame(() => requestAnimationFrame(function () {
        thu.setAttribute("stroke-dasharray", (CHU_VI * d.ty_le_thu / 100) + " " + CHU_VI);
    }));
}

// ---------- Hóa đơn gần đây ----------

function veGanDay(danhSach) {
    const than = document.getElementById("bang-gan-day");

    if (danhSach.length === 0) {
        than.replaceChildren(tao("tr", {}, tao("td", { colspan: 5, class: "rong" }, "Chưa có hóa đơn nào")));
        return;
    }

    than.replaceChildren(...danhSach.map(function (hoaDon) {
        return tao("tr", {},
            taoO("Mã", tao("a", { href: "hoa-don.html?q=" + encodeURIComponent(hoaDon.ma_hoa_don) }, hoaDon.ma_hoa_don), "ma"),
            taoO("Học viên", tao("div", { class: "o-hoc-vien" }, taoAnhDaiDien(hoaDon.hoc_vien), tao("span", { class: "ten" }, hoaDon.hoc_vien || ""))),
            taoO("Số tiền", dinhDangTien(hoaDon.so_tien) + " đ", "so tien"),
            taoO("Trạng thái", taoNhanTrangThai(hoaDon.trang_thai)),
            taoO("Ngày tạo", hoaDon.ngay_tao || ""));
    }));
}
