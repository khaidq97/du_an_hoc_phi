<?php

namespace App\Console\Commands;

use App\Mail\NhacNoMail;
use App\Models\HoaDon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

// Lệnh: php artisan hoa-don:nhac-no
// Gửi email nhắc cho học viên có hóa đơn chưa đóng.
// Lệnh này được hẹn giờ chạy mỗi ngày trong routes/console.php.
class NhacNoCommand extends Command
{
    protected $signature = 'hoa-don:nhac-no';

    protected $description = 'Gửi email nhắc học viên có hóa đơn chưa đóng tiền';

    public function handle(): int
    {
        $hoaDons = HoaDon::with('hocVien')
            ->where('trang_thai', HoaDon::CHUA_DONG)
            ->get();

        $soEmail = 0;

        foreach ($hoaDons as $hoaDon) {
            $email = $hoaDon->hocVien?->email;

            // Học viên chưa có email thì không gửi được
            if (! $email) {
                continue;
            }

            Mail::to($email)->send(new NhacNoMail($hoaDon));
            $soEmail++;
        }

        $this->info("Đã gửi {$soEmail} email nhắc nợ.");

        return self::SUCCESS;
    }
}
