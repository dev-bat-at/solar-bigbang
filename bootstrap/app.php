<?php

use App\Support\Filament\ExceptionMessageResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\EnsureValidApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            $isLivewireRequest = $request->is('livewire/*') || $request->headers->has('X-Livewire');
            $isApiRequest = $request->is('api/*');
            $expectsStructuredJson = $request->expectsJson() || $request->wantsJson() || $isLivewireRequest || $isApiRequest;

            if (! $expectsStructuredJson) {
                return null;
            }

            if ($isApiRequest) {
                $status = ExceptionMessageResolver::status($exception);
                
                if ($exception instanceof ValidationException) {
                    $firstErrorVn = collect($exception->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ.';
                    
                    $firstErrorEn = match($firstErrorVn) {
                        'Số điện thoại này đã được đăng ký.' => 'This phone number is already registered.',
                        'Email này đã được đăng ký.' => 'This email is already registered.',
                        default => 'Invalid data provided.'
                    };
                    
                    return \App\Support\Api\ApiResponse::error(
                        $firstErrorVn,
                        $firstErrorEn,
                        $status
                        // Not passing $errors here means data will be empty (object)[]
                    );
                }

                $errorMessageVn = ExceptionMessageResolver::message($exception);
                if (str_contains($errorMessageVn, 'Chi tiết kỹ thuật:')) {
                    $errorMessageVn = explode("\n\nChi tiết kỹ thuật:", $errorMessageVn)[0];
                }

                return \App\Support\Api\ApiResponse::error(
                    $errorMessageVn,
                    'An error occurred.',
                    $status
                );
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'title' => ExceptionMessageResolver::title($exception),
                    'message' => ExceptionMessageResolver::message($exception),
                    'errors' => $exception->errors(),
                ], ExceptionMessageResolver::status($exception));
            }

            return response()->json([
                'success' => false,
                'title' => ExceptionMessageResolver::title($exception),
                'message' => ExceptionMessageResolver::message($exception),
            ], ExceptionMessageResolver::status($exception));
        });
    })->create();
