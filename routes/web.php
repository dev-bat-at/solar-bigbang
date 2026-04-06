<?php

use App\Support\AdminTopbarAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware('auth:admin')
    ->prefix('admin')
    ->group(function (): void {
        Route::get('/topbar-alerts/{type}', function (Request $request, string $type) {
            abort_unless(AdminTopbarAlerts::isValidType($type), 404);

            $user = $request->user('admin');

            abort_unless($user, 403);

            AdminTopbarAlerts::markAsViewed($user, $type);

            return redirect()->to(AdminTopbarAlerts::destinationUrl($type));
        })->name('admin.topbar-alerts.redirect');

        Route::get('/test-error-snackbar', function (Request $request) {
            abort(500, 'Đây là lỗi kiểm thử cho snackbar thông báo.');
        })->name('filament.admin.test-error-snackbar');
    });
