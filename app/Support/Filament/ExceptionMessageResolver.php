<?php

namespace App\Support\Filament;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionMessageResolver
{
    public static function title(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof ValidationException => 'Dữ liệu chưa hợp lệ',
            $exception instanceof AuthorizationException => 'Bạn không có quyền thực hiện thao tác này',
            $exception instanceof NotFoundHttpException => 'Không tìm thấy dữ liệu',
            $exception instanceof QueryException => 'Không thể lưu dữ liệu',
            default => 'Thao tác không thành công',
        };
    }

    public static function message(Throwable $exception): string
    {
        $message = match (true) {
            $exception instanceof ValidationException => 'Vui lòng kiểm tra lại các trường bắt buộc và dữ liệu nhập vào.',
            $exception instanceof AuthorizationException => 'Tài khoản hiện tại không được phép thực hiện thao tác này.',
            $exception instanceof NotFoundHttpException => 'Bản ghi hoặc trang bạn đang thao tác không còn tồn tại.',
            $exception instanceof QueryException => self::resolveDatabaseMessage($exception),
            default => 'Hệ thống gặp lỗi trong quá trình xử lý. Vui lòng thử lại.',
        };

        if (app()->hasDebugModeEnabled() && filled($exception->getMessage())) {
            return "{$message}\n\nChi tiết kỹ thuật: {$exception->getMessage()}";
        }

        return $message;
    }

    public static function status(Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof QueryException) {
            return 500;
        }

        return 500;
    }

    protected static function resolveDatabaseMessage(QueryException $exception): string
    {
        $errorInfo = $exception->errorInfo ?? [];
        $driverCode = $errorInfo[1] ?? null;

        return match (true) {
            $driverCode === 1054 => 'Biểu mẫu và cấu trúc cơ sở dữ liệu đang chưa đồng bộ. Vui lòng kiểm tra migration hoặc cột dữ liệu bị thiếu.',
            $driverCode === 1062 => 'Dữ liệu này đã tồn tại trong hệ thống. Vui lòng đổi giá trị rồi thử lại.',
            $driverCode === 1451 => 'Không thể xóa dữ liệu này vì đang có bản ghi khác liên kết tới nó.',
            $driverCode === 1452 => 'Dữ liệu liên kết không hợp lệ hoặc chưa tồn tại.',
            default => 'Cơ sở dữ liệu từ chối thao tác vừa rồi. Vui lòng kiểm tra dữ liệu và thử lại.',
        };
    }
}
