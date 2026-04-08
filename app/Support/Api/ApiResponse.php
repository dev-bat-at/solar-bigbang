<?php

namespace App\Support\Api;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $messageVn = '',
        string $messageEn = '',
        int $status = JsonResponse::HTTP_OK,
        array $extra = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message_vn' => $messageVn,
            'message_en' => $messageEn,
            'data' => self::normalize($data) ?? (object) [],
        ];

        $payload = array_merge($payload, $extra);

        return response()->json($payload, $status);
    }

    public static function error(
        string $messageVn = '',
        string $messageEn = '',
        int $status = JsonResponse::HTTP_BAD_REQUEST,
        mixed $errors = null,
        array $extra = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message_vn' => $messageVn,
            'message_en' => $messageEn,
            'data' => $errors ? self::normalize($errors) : (object) [],
        ];

        $payload = array_merge($payload, $extra);

        return response()->json($payload, $status);
    }

    protected static function normalize(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(app(Request::class));
        }

        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof JsonSerializable) {
            return $data->jsonSerialize();
        }

        return $data;
    }
}
