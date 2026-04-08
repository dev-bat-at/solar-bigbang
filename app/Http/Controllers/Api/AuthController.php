<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Carbon\CarbonInterface;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[Group('Xác thực người dùng', 'Đăng ký và đăng nhập tài khoản người dùng bằng email hoặc số điện thoại.', 30)]
class AuthController extends Controller
{
    #[Endpoint(
        operationId: 'registerUser',
        title: 'Đăng ký người dùng',
        description: 'Tạo mới tài khoản người dùng với số điện thoại, email, mật khẩu và tỉnh/thành.'
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['phone'],
            'phone' => $validated['phone'],
            'email' => strtolower($validated['email']),
            'province_id' => $validated['province_id'],
            'password' => $validated['password'],
        ]);

        $user->load('province');

        return ApiResponse::success(
            new UserResource($user),
            'Đăng ký người dùng thành công.',
            'User registered successfully.',
            JsonResponse::HTTP_CREATED
        );
    }

    #[Endpoint(
        operationId: 'loginUser',
        title: 'Đăng nhập người dùng',
        description: 'Đăng nhập bằng trường login, có thể truyền email hoặc số điện thoại cùng mật khẩu.'
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $login = trim($validated['login']);
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()
            ->where($loginField, $loginField === 'email' ? strtolower($login) : $login)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error(
                'Thông tin đăng nhập không chính xác.',
                'Invalid login credentials.',
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $expiresAt = $this->resolveExpiration();
        $token = $user->createToken(
            (string) config('api_auth.token_name', 'mobile-app'),
            ['*'],
            $expiresAt,
        );

        $user->load('province');

        return ApiResponse::success(
            [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt?->toIso8601String(),
                'expires_in_minutes' => config('sanctum.expiration'),
                'user' => (new UserResource($user))->resolve($request),
            ],
            'Đăng nhập thành công.',
            'Logged in successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getAuthenticatedUser',
        title: 'Lấy thông tin người dùng hiện tại',
        description: 'Dùng Bearer token vừa đăng nhập để lấy lại hồ sơ người dùng.'
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()?->load('province');

        return ApiResponse::success(
            new UserResource($user),
            'Lấy thông tin người dùng thành công.',
            'User retrieved successfully.'
        );
    }

    protected function resolveExpiration(): ?CarbonInterface
    {
        $expiration = config('sanctum.expiration');

        if (!is_numeric($expiration)) {
            return null;
        }

        return now()->addMinutes((int) $expiration);
    }
}
