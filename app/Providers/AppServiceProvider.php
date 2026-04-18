<?php

namespace App\Providers;

use App\Models\SystemSetting;
use App\Models\AdminUser;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! $this->app->bound(Parser::class)) {
            $this->app->singleton(Parser::class, fn (): Parser => (new ParserFactory)->createForHostVersion());
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyRuntimeSettings();

        Event::listen(Login::class, function (Login $event): void {
            if (! ($event->user instanceof AdminUser)) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->event('login')
                ->withProperties([
                    'guard' => $event->guard,
                    'ip' => request()->ip(),
                    'user_agent' => Str::limit((string) request()->userAgent(), 255),
                ])
                ->log('login');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! ($event->user instanceof AdminUser)) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->event('logout')
                ->withProperties([
                    'guard' => $event->guard,
                    'ip' => request()->ip(),
                    'user_agent' => Str::limit((string) request()->userAgent(), 255),
                ])
                ->log('logout');
        });

        if (class_exists(Scramble::class)) {
            Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::apiKey('header', (string) config('api_auth.header', 'X-API-KEY'))
                        ->as('ApiKeyAuth')
                        ->setDescription('Header API key bắt buộc cho toàn bộ API.')
                );

                $openApi->components->addSecurityScheme(
                    'BearerAuth',
                    SecurityScheme::http('bearer', 'Bearer')
                        ->as('BearerAuth')
                        ->setDescription('Bearer access token nhận được sau khi đăng nhập.')
                );

                $apiPrefix = trim((string) config('scramble.api_path', 'api'), '/').'/';

                $protectedRoutes = collect(app('router')->getRoutes()->getRoutes())
                    ->filter(function ($route) use ($apiPrefix): bool {
                        if (! Str::startsWith($route->uri(), $apiPrefix)) {
                            return false;
                        }

                        return collect($route->gatherMiddleware())->contains(
                            fn (string $middleware): bool => str_contains($middleware, 'Authenticate:sanctum')
                                || str_contains($middleware, 'auth:sanctum')
                        );
                    })
                    ->mapWithKeys(function ($route) use ($apiPrefix): array {
                        $path = Str::after($route->uri(), $apiPrefix);
                        $methods = collect($route->methods())
                            ->reject(fn (string $method): bool => $method === 'HEAD')
                            ->map(fn (string $method): string => strtolower($method))
                            ->values()
                            ->all();

                        return [$path => $methods];
                    });

                foreach ($openApi->paths as $path) {
                    $methods = $protectedRoutes->get($path->path, []);

                    foreach ($methods as $method) {
                        if (! isset($path->operations[$method])) {
                            continue;
                        }

                        $path->operations[$method]->security = [
                            new SecurityRequirement([
                                'ApiKeyAuth' => [],
                                'BearerAuth' => [],
                            ]),
                        ];
                    }
                }
            });
        }
    }

    protected function applyRuntimeSettings(): void
    {
        $timezone = (string) config('app.timezone', 'Asia/Ho_Chi_Minh');
        $locale = (string) config('app.locale', 'vi');

        try {
            $timezone = (string) (SystemSetting::get('timezone', $timezone) ?: $timezone);
            $locale = (string) (SystemSetting::get('locale', $locale) ?: $locale);
        } catch (\Throwable) {
            // Fall back to config defaults when the settings table is unavailable.
        }

        if (in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        if ($locale !== '') {
            app()->setLocale($locale);
        }
    }
}
