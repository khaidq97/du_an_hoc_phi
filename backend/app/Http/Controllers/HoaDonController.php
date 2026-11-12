<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoaDonRequest;
use App\Http\Resources\HoaDonResource;
use App\Models\HoaDon;
use App\Support\ThongBaoLoi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HoaDonController extends Controller
{
    const DEFAULT_PAGINATE = 15;

    // Danh sách hóa đơn, mới nhất ở trên.
    // Kế toán thấy tất cả; học viên chỉ thấy hóa đơn của chính mình.
    // Bộ lọc (đều không bắt buộc): q, trang_thai, tu_ngay, den_ngay, per_page. Xem locDanhSach().
    public function index(Request $request)
    {
        $loc = $this->kiemTraBoLoc($request);

        $hoaDons = $this->locDanhSach($request, $loc)
            ->paginate((int) ($loc['per_page'] ?? self::DEFAULT_PAGINATE))
            ->withQueryString();

        return HoaDonResource::collection($hoaDons);
    }

    // Tải danh sách hóa đơn dạng CSV (mở được bằng Excel), áp dụng cùng bộ lọc với index().
    // Chỉ kế toán (đã chặn ở route).
    public function xuatCsv(Request $request): StreamedResponse
    {
        $loc = $this->kiemTraBoLoc($request);
        $query = $this->locDanhSach($request, $loc);

        return response()->streamDownload(function () use ($query) {
            $tep = fopen('php://output', 'w');

            // BOM giúp Excel hiểu đây là UTF-8, tiếng Việt không bị lỗi font.
            fwrite($tep, "\xEF\xBB\xBF");
            fputcsv($tep, ['Mã hóa đơn', 'Học viên', 'Email', 'Số tiền', 'Trạng thái', 'Ngày tạo', 'Ngày thu'], ',', '"', '');

            foreach ($query->cursor() as $hoaDon) {
                fputcsv($tep, array_map([$this, 'lamSachOCsv'], [
                    $hoaDon->ma_hoa_don,
                    $hoaDon->hocVien?->ten,
                    $hoaDon->hocVien?->email,
                    $hoaDon->so_tien,
                    $hoaDon->daDong() ? 'Đã đóng' : 'Chưa đóng',
                    $hoaDon->created_at?->format('d/m/Y'),
                    $hoaDon->ngay_thu?->format('d/m/Y'),
                ]), ',', '"', '');
            }

            fclose($tep);
        }, 'hoa-don.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Tạo hóa đơn mới (chỉ kế toán, đã chặn ở route)
    public function store(HoaDonRequest $request): JsonResponse
    {
        // Mã hóa đơn đã có thì không tạo nữa
        if (HoaDon::where('ma_hoa_don', $request->ma_hoa_don)->exists()) {
            return response()->json(['thong_bao' => 'Mã hóa đơn đã tồn tại'], 409);
        }

        $hoaDon = HoaDon::create([
            'ma_hoa_don' => $request->ma_hoa_don,
            'hoc_vien_id' => $request->hoc_vien_id,
            'so_tien' => $request->so_tien,
            'trang_thai' => HoaDon::CHUA_DONG,
        ]);

        return response()->json([
            'thong_bao' => 'Đã tạo hóa đơn thành công',
            'du_lieu' => new HoaDonResource($hoaDon->load('hocVien')),
        ], 201);
    }

    // Đánh dấu hóa đơn đã đóng tiền (chỉ kế toán)
    public function markPaid($id): JsonResponse
    {
        $hoaDon = HoaDon::findOrFail($id);

        // Đã đóng rồi thì không ghi nhận lần nữa
        if ($hoaDon->daDong()) {
            return response()->json(['thong_bao' => 'Hóa đơn này đã được đóng trước đó'], 409);
        }

        $hoaDon->trang_thai = HoaDon::DA_DONG;
        $hoaDon->ngay_thu = now();
        $hoaDon->save();

        return response()->json([
            'thong_bao' => 'Đã ghi nhận đóng tiền',
            'du_lieu' => new HoaDonResource($hoaDon->load('hocVien')),
        ]);
    }

    // Xóa hóa đơn (chỉ kế toán)
    public function destroy($id): JsonResponse
    {
        $hoaDon = HoaDon::findOrFail($id);

        // Yêu cầu của khách: hóa đơn đã đóng tiền thì không được xóa
        if ($hoaDon->daDong()) {
            return response()->json(['thong_bao' => 'Hóa đơn đã đóng tiền, không được xóa'], 409);
        }

        $hoaDon->delete();

        return response()->json(['thong_bao' => 'Đã xóa hóa đơn']);
    }

    // Kiểm tra bộ lọc người dùng gửi lên. Sai dạng thì trả lỗi 422 như mọi form khác.
    private function kiemTraBoLoc(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'trang_thai' => ['nullable', Rule::in([HoaDon::CHUA_DONG, HoaDon::DA_DONG])],
            'tu_ngay' => 'nullable|date_format:Y-m-d',
            'den_ngay' => 'nullable|date_format:Y-m-d',
            'per_page' => 'nullable|integer|min:1|max:50',
        ], ThongBaoLoi::thongBao(), ThongBaoLoi::tenTruong());
    }

    // Câu truy vấn hóa đơn đã áp dụng quyền xem và bộ lọc. Dùng chung cho danh sách và tải CSV.
    private function locDanhSach(Request $request, array $loc): Builder
    {
        $taiKhoan = $request->user();
        $tuKhoa = trim((string) ($loc['q'] ?? ''));

        return HoaDon::with('hocVien')
            // Học viên chỉ thấy hóa đơn của mình (quyền luôn kiểm tra ở backend, không tin giao diện)
            ->when(! $taiKhoan->laKeToan(), function ($query) use ($taiKhoan) {
                $query->whereHas('hocVien', fn ($q) => $q->where('tai_khoan_id', $taiKhoan->id));
            })
            ->when($tuKhoa !== '', function ($query) use ($tuKhoa) {
                // addcslashes: ký tự % và _ người dùng gõ vào được hiểu là chữ thường, không phải ký tự đại diện
                $mau = '%'.addcslashes($tuKhoa, '%_\\').'%';
                $query->where(function ($q) use ($mau) {
                    $q->where('ma_hoa_don', 'like', $mau)
                        ->orWhereHas('hocVien', fn ($h) => $h->where('ten', 'like', $mau));
                });
            })
            ->when($loc['trang_thai'] ?? null, fn ($query, $trangThai) => $query->where('trang_thai', $trangThai))
            ->when($loc['tu_ngay'] ?? null, fn ($query, $ngay) => $query->whereDate('created_at', '>=', $ngay))
            ->when($loc['den_ngay'] ?? null, fn ($query, $ngay) => $query->whereDate('created_at', '<=', $ngay))
            ->orderBy('id', 'desc');
    }

    // Chống "CSV injection": ô bắt đầu bằng = + - @ có thể bị Excel hiểu là công thức.
    // Thêm dấu nháy đơn ở đầu để Excel coi đó là chữ thường.
    private function lamSachOCsv($giaTri)
    {
        if (is_string($giaTri) && $giaTri !== '' && str_contains("=+-@\t\r", $giaTri[0])) {
            return "'".$giaTri;
        }

        return $giaTri;
    }
}
