<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');

});

Route::middleware('auth:admin')
    ->prefix('admin')
    ->group(function (): void {
        Route::get('/test-error-snackbar', function (Request $request) {
            abort(500, 'Đây là lỗi kiểm thử cho snackbar thông báo.');
        })->name('filament.admin.test-error-snackbar');
    });
