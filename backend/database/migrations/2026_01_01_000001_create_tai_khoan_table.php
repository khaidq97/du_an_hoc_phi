<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bảng tai_khoan: người đăng nhập hệ thống (kế toán hoặc học viên).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tai_khoan', function (Blueprint $table) {
            $table->id();
            $table->string('ten', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            // ke_toan: thu tiền, tạo, xóa hóa đơn. hoc_vien: chỉ xem hóa đơn của mình.
            $table->string('vai_tro', 20)->default('hoc_vien');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_khoan');
    }
};
