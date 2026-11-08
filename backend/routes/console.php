<?php

use Illuminate\Support\Facades\Schedule;

// Hẹn giờ: mỗi ngày 08:00 tự gửi email nhắc nợ (yêu cầu "hệ thống tự gửi email nhắc").
// Muốn thử ngay: php artisan hoa-don:nhac-no
Schedule::command('hoa-don:nhac-no')->dailyAt('08:00');
