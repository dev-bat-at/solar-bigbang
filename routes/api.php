<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactConfigController;
use App\Http\Controllers\Api\DealerProjectController;
use App\Http\Controllers\Api\DealerNotificationController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\QuoteCalculatorController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function (): void {
    // Unauthenticated APIs
    Route::prefix('auth')->name('api.auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('register');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login');

        Route::post('/dealer/login', [AuthController::class, 'dealerLogin'])
            ->middleware('throttle:10,1')
            ->name('dealer.login');
    });

    // APIs chỉ cần X-API-KEY
    Route::get('/config/contact', ContactConfigController::class)
        ->name('api.config.contact');

    Route::get('/provinces', [ProvinceController::class, 'index'])
        ->name('api.provinces.index');

    Route::get('/provinces/{province}', [ProvinceController::class, 'show'])
        ->name('api.provinces.show');

    // APIs yêu cầu X-API-KEY và Bearer token
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])
            ->name('api.auth.me');

        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');

        Route::delete('/auth/account', [AuthController::class, 'destroyAccount'])
            ->name('api.auth.destroy');

        Route::post('/quote/calculate', QuoteCalculatorController::class)
            ->middleware('throttle:30,1')
            ->name('api.quote.calculate');

        Route::prefix('news')->name('api.news.')->group(function (): void {
            Route::get('/tags', [\App\Http\Controllers\Api\NewsController::class, 'tags'])
                ->name('tags');
                
            Route::get('/', [\App\Http\Controllers\Api\NewsController::class, 'index'])
                ->name('index');
                
            Route::get('/{idOrSlug}', [\App\Http\Controllers\Api\NewsController::class, 'show'])
                ->name('show');
        });

        Route::prefix('products')->name('api.products.')->group(function (): void {
            Route::get('/categories', [\App\Http\Controllers\Api\ProductController::class, 'categories'])
                ->name('categories');

            Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index'])
                ->name('index');

            Route::get('/{idOrSlug}', [\App\Http\Controllers\Api\ProductController::class, 'show'])
                ->name('show');
        });
        Route::prefix('systems')->name('api.systems.')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Api\SystemTypeController::class, 'index'])
                ->name('index');
        });

        Route::prefix('support-requests')->name('api.support.')->group(function (): void {
            Route::post('/', [\App\Http\Controllers\Api\SupportRequestController::class, 'store'])
                ->name('store');
        });

        Route::prefix('dealers')->name('api.dealers.')->group(function (): void {
            Route::get('/me/customers', [\App\Http\Controllers\Api\DealerController::class, 'customers'])
                ->name('customers');

            Route::post('/me/avatar', [\App\Http\Controllers\Api\DealerController::class, 'updateAvatar'])
                ->name('avatar.update');

            Route::post('/me/customers/{customer}/status', [\App\Http\Controllers\Api\DealerController::class, 'updateCustomerStatus'])
                ->name('customers.updateStatus');

            Route::get('/me/projects', [DealerProjectController::class, 'index'])
                ->name('projects.index');

            Route::post('/me/projects', [DealerProjectController::class, 'store'])
                ->name('projects.store');

            Route::get('/me/notifications', [DealerNotificationController::class, 'index'])
                ->name('notifications.index');

            Route::post('/me/notifications/{notification}/status', [DealerNotificationController::class, 'updateStatus'])
                ->name('notifications.updateStatus');

            Route::get('/', [\App\Http\Controllers\Api\DealerController::class, 'index'])
                ->name('index');

            Route::get('/{id}', [\App\Http\Controllers\Api\DealerController::class, 'show'])
                ->name('show');
                
            Route::post('/{id}/support-requests', [\App\Http\Controllers\Api\DealerController::class, 'requestSupport'])
                ->name('requestSupport');
        });
    });
});
