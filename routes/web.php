<?php

use App\Http\Controllers\ApiDocsExportController;
use App\Http\Controllers\OpenApiSpecController;
use App\Http\Controllers\SwaggerDocsController;
use App\Support\AdminTopbarAlerts;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::middleware(config('scramble.middleware', ['web', RestrictedDocsAccess::class]))
    ->get('/docs/api/export.json', ApiDocsExportController::class)
    ->name('scramble.docs.export');

Route::middleware(config('scramble.middleware', ['web', RestrictedDocsAccess::class]))
    ->get('/docs/api/openapi.json', OpenApiSpecController::class)
    ->name('scramble.docs.openapi');

Route::middleware(config('scramble.middleware', ['web', RestrictedDocsAccess::class]))
    ->get('/docs/swagger', SwaggerDocsController::class)
    ->name('scramble.docs.swagger');

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
