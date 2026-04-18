<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\DealerAuthResource;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\Dealer;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Carbon\CarbonInterface;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('registered')
            ->withProperties([
                'guard' => 'sanctum-user',
                'channel' => 'api',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
                'phone' => $user->phone,
                'email' => $user->email,
                'province_id' => $user->province_id,
            ])
            ->log('registered');

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

        activity('auth')
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'guard' => 'sanctum-user',
                'channel' => 'api',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
            ])
            ->log('login');

        $user->load('province');

        return ApiResponse::success(
            $this->buildLoginPayload($request, $user, $token->plainTextToken, $expiresAt),
            'Đăng nhập thành công.',
            'Logged in successfully.'
        );
    }

    #[Endpoint(
        operationId: 'loginDealer',
        title: 'Đăng nhập đại lý',
        description: 'Đăng nhập tài khoản đại lý bằng email hoặc số điện thoại cùng mật khẩu.'
    )]
    public function dealerLogin(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $login = trim($validated['login']);
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $dealer = Dealer::query()
            ->whereIn('status', ['approved', 'active'])
            ->where($loginField, $loginField === 'email' ? strtolower($login) : $login)
            ->first();

        if (! $dealer || ! Hash::check($validated['password'], (string) $dealer->password)) {
            return ApiResponse::error(
                'Thông tin đăng nhập đại lý không chính xác.',
                'Invalid dealer login credentials.',
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $expiresAt = $this->resolveExpiration();
        $token = $dealer->createToken(
            (string) config('api_auth.token_name', 'mobile-app'),
            ['*'],
            $expiresAt,
        );

        activity('auth')
            ->causedBy($dealer)
            ->event('login')
            ->withProperties([
                'guard' => 'sanctum-dealer',
                'channel' => 'api',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
            ])
            ->log('login');

        return ApiResponse::success(
            $this->buildLoginPayload($request, $dealer, $token->plainTextToken, $expiresAt),
            'Đăng nhập đại lý thành công.',
            'Dealer logged in successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getAuthenticatedUser',
        title: 'Lấy thông tin người dùng hiện tại',
        description: 'Dùng Bearer token vừa đăng nhập để lấy lại hồ sơ người dùng.'
    )]
    public function me(Request $request): JsonResponse
    {
        $authenticatable = $request->user();

        if ($authenticatable instanceof User) {
            $authenticatable->load('province');
        }

        return ApiResponse::success(
            $this->buildAuthenticatedProfile($request, $authenticatable),
            'Lấy thông tin tài khoản thành công.',
            'Authenticated account retrieved successfully.'
        );
    }

    #[Endpoint(
        operationId: 'logoutUser',
        title: 'Đăng xuất người dùng',
        description: 'Thu hồi Bearer token hiện tại của người dùng đang đăng nhập.'
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->event('logout')
                ->withProperties([
                    'guard' => $user instanceof Dealer ? 'sanctum-dealer' : 'sanctum-user',
                    'channel' => 'api',
                    'ip' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 255),
                ])
                ->log('logout');
        }

        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(
            null,
            'Đăng xuất thành công.',
            'Logged out successfully.'
        );
    }

    #[Endpoint(
        operationId: 'deleteAuthenticatedUserAccount',
        title: 'Xóa mềm tài khoản người dùng',
        description: 'Người dùng đăng nhập bằng Bearer token có thể tự xóa mềm tài khoản của chính mình. Toàn bộ access token sẽ bị thu hồi.'
    )]
    public function destroyAccount(Request $request): JsonResponse
    {
        $authenticatable = $request->user();

        if (! $authenticatable instanceof User) {
            return ApiResponse::error(
                'Chỉ tài khoản người dùng mới có thể xóa tài khoản tại endpoint này.',
                'Only user accounts can be deleted through this endpoint.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        activity('auth')
            ->causedBy($authenticatable)
            ->performedOn($authenticatable)
            ->event('account_deleted')
            ->withProperties([
                'guard' => 'sanctum-user',
                'channel' => 'api',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
            ])
            ->log('account_deleted');

        $authenticatable->tokens()->delete();
        $authenticatable->delete();

        return ApiResponse::success(
            null,
            'Xóa tài khoản thành công.',
            'Account deleted successfully.'
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

    protected function buildLoginPayload(
        Request $request,
        User|Dealer $authenticatable,
        string $accessToken,
        ?CarbonInterface $expiresAt,
    ): array {
        $role = $authenticatable instanceof Dealer ? 'dealer' : 'user';

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'expires_in_minutes' => config('sanctum.expiration'),
            'role' => $role,
            'account_type' => $role,
            $role => $this->buildAuthenticatedProfile($request, $authenticatable),
        ];
    }

    protected function buildAuthenticatedProfile(Request $request, ?Authenticatable $authenticatable): array
    {
        if ($authenticatable instanceof Dealer) {
            return array_merge(
                (new DealerAuthResource($authenticatable))->resolve($request),
                [
                    'role' => 'dealer',
                    'account_type' => 'dealer',
                ],
            );
        }

        if ($authenticatable instanceof User) {
            return array_merge(
                (new UserResource($authenticatable))->resolve($request),
                [
                    'role' => 'user',
                    'account_type' => 'user',
                ],
            );
        }

        return [];
    }
}
