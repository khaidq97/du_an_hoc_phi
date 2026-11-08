<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bảng hoa_don. ma_hoa_don là duy nhất: database cũng chặn trùng, không chỉ code.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hoa_don', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hoa_don', 20)->unique();
            $table->foreignId('hoc_vien_id')->constrained('hoc_vien');
            $table->unsignedBigInteger('so_tien');
            // chua_dong hoặc da_dong
            $table->string('trang_thai', 20)->default('chua_dong');
            $table->dateTime('ngay_thu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hoa_don');
    }
};
