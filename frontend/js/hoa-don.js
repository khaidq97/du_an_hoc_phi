// Trang danh sách hóa đơn.

// Chưa đăng nhập thì quay về trang đăng nhập
if (!localStorage.getItem("token")) {
    window.location.replace("dang-nhap.html");
}

const taiKhoan = JSON.parse(localStorage.getItem("tai_khoan") || "{}");
const laKeToan = taiKhoan.vai_tro === "ke_toan";
let trangHienTai = 1;

// ---------- Hiển thị ----------

function dinhDangTien(soTien) {
    return Number(soTien).toLocaleString("vi-VN");
}

// Tạo một ô <td> chứa chữ. Dùng textContent để dữ liệu lạ không chạy được như mã HTML.
function taoO(noiDung, tenLop) {
    const o = document.createElement("td");
    o.textContent = noiDung;
    if (tenLop) {
        o.className = tenLop;
    }
    return o;
}

function taoNut(chu, tenLop, khiBam) {
    const nut = document.createElement("button");
    nut.type = "button";
    nut.textContent = chu;
    nut.className = "nut-nho " + tenLop;
    nut.addEventListener("click", khiBam);
    return nut;
}

function veBang(danhSach) {
    const thanBang = document.getElementById("bang-hoa-don");
    thanBang.replaceChildren();

    if (danhSach.length === 0) {
        const hang = document.createElement("tr");
        const o = taoO("Chưa có hóa đơn nào", "rong");
        o.colSpan = 6;
        hang.appendChild(o);
        thanBang.appendChild(hang);
        return;
    }

    danhSach.forEach(function (hoaDon) {
        const daDong = hoaDon.trang_thai === "da_dong";
        const hang = document.createElement("tr");

        hang.appendChild(taoO(hoaDon.ma_hoa_don));
        hang.appendChild(taoO(hoaDon.hoc_vien));
        hang.appendChild(taoO(dinhDangTien(hoaDon.so_tien), "so"));

        const oTrangThai = document.createElement("td");
        const nhan = document.createElement("span");
        nhan.className = "nhan " + (daDong ? "da-dong" : "chua-dong");
        nhan.textContent = daDong ? "Đã đóng" : "Chưa đóng";
        oTrangThai.appendChild(nhan);
        hang.appendChild(oTrangThai);

        hang.appendChild(taoO(hoaDon.ngay_thu || ""));

        // Nút thao tác: chỉ kế toán thấy. Hóa đơn đã đóng thì ẩn cả hai nút.
        // Đây chỉ là tiện cho người dùng; backend mới là nơi chặn thật sự.
        const oThaoTac = document.createElement("td");
        oThaoTac.className = "hanh-dong";
        if (laKeToan && !daDong) {
            oThaoTac.appendChild(taoNut("Đã thu tiền", "", function () { thuTien(hoaDon); }));
            oThaoTac.appendChild(taoNut("Xóa", "nut-xoa", function () { xoaHoaDon(hoaDon); }));
        }
        hang.appendChild(oThaoTac);

        thanBang.appendChild(hang);
    });
}

function capNhatPhanTrang(meta) {
    document.getElementById("so-trang").textContent = "Trang " + meta.current_page + " / " + meta.last_page;
    document.getElementById("nut-trang-truoc").disabled = meta.current_page <= 1;
    document.getElementById("nut-trang-sau").disabled = meta.current_page >= meta.last_page;
}

// ---------- Gọi API ----------

// Token hết hạn hoặc bị hủy: xóa thông tin đăng nhập, về trang đăng nhập.
function xuLyHetPhienDangNhap(ketQua) {
    if (ketQua.status === 401) {
        localStorage.removeItem("token");
        localStorage.removeItem("tai_khoan");
        window.location.replace("dang-nhap.html");
        return true;
    }
    return false;
}

async function taiDanhSach(trang) {
    const ketQua = await goiApi("/hoa-don?page=" + trang);

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    if (!ketQua.ok) {
        hienThongBao(layThongBaoLoi(ketQua), true);
        return;
    }

    trangHienTai = ketQua.du_lieu.meta.current_page;
    veBang(ketQua.du_lieu.data);
    capNhatPhanTrang(ketQua.du_lieu.meta);
}

async function taiDanhSachHocVien() {
    const ketQua = await goiApi("/hoc-vien");
    if (!ketQua.ok) {
        return;
    }

    const oChon = document.getElementById("hoc_vien_id");
    ketQua.du_lieu.du_lieu.forEach(function (hocVien) {
        const luaChon = document.createElement("option");
        luaChon.value = hocVien.id;
        luaChon.textContent = hocVien.ten + " (" + hocVien.email + ")";
        oChon.appendChild(luaChon);
    });
}

async function thuTien(hoaDon) {
    const ketQua = await goiApi("/hoa-don/" + hoaDon.id + "/da-dong", { method: "POST" });

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    hienThongBao(ketQua.ok ? ketQua.du_lieu.thong_bao : layThongBaoLoi(ketQua), !ketQua.ok);
    taiDanhSach(trangHienTai);
}

async function xoaHoaDon(hoaDon) {
    if (!window.confirm("Xóa hóa đơn " + hoaDon.ma_hoa_don + "?")) {
        return;
    }

    const ketQua = await goiApi("/hoa-don/" + hoaDon.id, { method: "DELETE" });

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    hienThongBao(ketQua.ok ? ketQua.du_lieu.thong_bao : layThongBaoLoi(ketQua), !ketQua.ok);
    taiDanhSach(trangHienTai);
}

// ---------- Gắn sự kiện và chạy ----------

document.getElementById("thong-tin-nguoi-dung").textContent =
    (taiKhoan.ten || "") + (laKeToan ? " (kế toán)" : " (học viên)");

document.getElementById("nut-dang-xuat").addEventListener("click", async function () {
    await goiApi("/dang-xuat", { method: "POST" });
    localStorage.removeItem("token");
    localStorage.removeItem("tai_khoan");
    window.location.href = "dang-nhap.html";
});

document.getElementById("nut-trang-truoc").addEventListener("click", function () {
    taiDanhSach(trangHienTai - 1);
});
document.getElementById("nut-trang-sau").addEventListener("click", function () {
    taiDanhSach(trangHienTai + 1);
});

document.getElementById("form-tao-hoa-don").addEventListener("submit", async function (suKien) {
    suKien.preventDefault();

    const ketQua = await goiApi("/hoa-don", {
        method: "POST",
        body: {
            ma_hoa_don: this.ma_hoa_don.value,
            hoc_vien_id: this.hoc_vien_id.value,
            so_tien: this.so_tien.value,
        },
    });

    if (xuLyHetPhienDangNhap(ketQua)) {
        return;
    }
    hienThongBao(ketQua.ok ? ketQua.du_lieu.thong_bao : layThongBaoLoi(ketQua), !ketQua.ok);

    if (ketQua.ok) {
        this.reset();
        taiDanhSach(1);
    }
});

if (laKeToan) {
    document.getElementById("khung-tao-hoa-don").hidden = false;
    taiDanhSachHocVien();
}
taiDanhSach(1);
