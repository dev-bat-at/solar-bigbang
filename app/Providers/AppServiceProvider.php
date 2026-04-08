<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
}
