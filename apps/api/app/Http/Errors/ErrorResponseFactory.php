<?php

declare(strict_types=1);

namespace App\Http\Errors;

use App\Exceptions\ApplicationException;
use App\Exceptions\ErrorCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorResponseFactory
{
    /**
     * Http 層まで漏れた時点で実装バグとみなし、詰め替え先のステータスを使わず 500 として扱う例外。
     *
     * @var list<class-string<Throwable>>
     */
    public const INTERNAL_ERROR_EXCEPTIONS = [
        BackedEnumCaseNotFoundException::class,
        ModelNotFoundException::class,
        RecordNotFoundException::class,
        RecordsNotFoundException::class,
    ];

    public function make(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        if ($e instanceof ApplicationException) {
            return $this->json($e->errorCode(), $e->clientMessage());
        }

        if ($e instanceof ValidationException) {
            return $this->json(ErrorCode::InvalidArguments);
        }

        if ($e instanceof HttpExceptionInterface && ! $this->isInternalError($e)) {
            return $this->fromStatus($e->getStatusCode());
        }

        if (config()->boolean('app.debug')) {
            return null;
        }

        return $this->json(ErrorCode::InternalError);
    }

    // Handler::prepareException は ModelNotFoundException などを NotFoundHttpException に詰め替える。
    // 詰め替え後の型では判別できないため、previous に残る元の例外で判定する。
    private function isInternalError(Throwable $e): bool
    {
        $previous = $e->getPrevious();

        foreach (self::INTERNAL_ERROR_EXCEPTIONS as $class) {
            if ($previous instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function fromStatus(int $status): JsonResponse
    {
        $code = match (true) {
            $status === 403 => ErrorCode::PermissionDenied,
            $status === 404 => ErrorCode::NotFound,
            $status === 409 => ErrorCode::Conflict,
            $status >= 400 && $status < 500 => ErrorCode::InvalidArguments,
            default => ErrorCode::InternalError,
        };

        return $this->json($code, status: $status);
    }

    private function json(ErrorCode $code, ?string $message = null, ?int $status = null): JsonResponse
    {
        return new JsonResponse([
            'code' => $code->value,
            'message' => $message ?? $code->defaultMessage(),
        ], $status ?? $this->status($code));
    }

    private function status(ErrorCode $code): int
    {
        return match ($code) {
            ErrorCode::InvalidArguments => 400,
            ErrorCode::PermissionDenied => 403,
            ErrorCode::NotFound => 404,
            ErrorCode::Conflict => 409,
            ErrorCode::InternalError => 500,
        };
    }
}
