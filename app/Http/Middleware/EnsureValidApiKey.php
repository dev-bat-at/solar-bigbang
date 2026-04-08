<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $headerName = (string) config('api_auth.header', 'X-API-KEY');
        $configuredKey = (string) config('api_auth.key', '');
        $providedKey = (string) $request->header($headerName, '');

        if ($configuredKey === '') {
            return \App\Support\Api\ApiResponse::error(
                'Hệ thống chưa có API key để xác thực yêu cầu.',
                'The system has no configured API key to authenticate requests.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return \App\Support\Api\ApiResponse::error(
                "Vui lòng truyền header {$headerName} hợp lệ.",
                "Please provide a valid {$headerName} header.",
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }
}
