<?php

use App\Http\Middleware\KiemTraVaiTro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Đặt tên ngắn cho middleware kiểm tra vai trò để dùng trong routes/api.php
        $middleware->alias([
            'vai_tro' => KiemTraVaiTro::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Địa chỉ /api luôn trả lỗi dạng JSON để frontend đọc được
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // findOrFail không thấy dữ liệu: báo "Không tìm thấy" thay vì lỗi tiếng Anh
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['thong_bao' => 'Không tìm thấy dữ liệu'], 404);
            }
        });
    })->create();
