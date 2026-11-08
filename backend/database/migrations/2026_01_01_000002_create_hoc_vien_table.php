<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bảng hoc_vien: hồ sơ học viên. Một học viên có thể chưa có tài khoản đăng nhập
// (kế toán tạo sẵn hồ sơ), khi đó tai_khoan_id để trống.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hoc_vien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tai_khoan_id')->nullable()->unique()
                ->constrained('tai_khoan')->nullOnDelete();
            $table->string('ten', 100);
            $table->string('email', 150)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hoc_vien');
    }
};
