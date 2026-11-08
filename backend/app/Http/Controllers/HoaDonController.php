<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoaDonRequest;
use App\Http\Resources\HoaDonResource;
use App\Models\HoaDon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoaDonController extends Controller
{
    const DEFAULT_PAGINATE = 15;

    // Danh sách hóa đơn, mới nhất ở trên.
    // Kế toán thấy tất cả; học viên chỉ thấy hóa đơn của chính mình.
    public function index(Request $request)
    {
        $taiKhoan = $request->user();

        $hoaDons = HoaDon::with('hocVien')
            ->when(! $taiKhoan->laKeToan(), function ($query) use ($taiKhoan) {
                $query->whereHas('hocVien', fn ($q) => $q->where('tai_khoan_id', $taiKhoan->id));
            })
            ->orderBy('id', 'desc')
            ->paginate(self::DEFAULT_PAGINATE);

        return HoaDonResource::collection($hoaDons);
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
}
