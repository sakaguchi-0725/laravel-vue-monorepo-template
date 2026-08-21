<?php

declare(strict_types=1);

namespace App\Http\Errors;

use App\Exceptions\ApplicationException;
use App\Exceptions\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorResponseFactory
{
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

        // Handler::prepareException が ModelNotFoundException などを NotFoundHttpException に
        // 詰め替えてから renderable コールバックを呼ぶため、型だけではルート未定義と区別できない。
        // 詰め替えられたものは previous を持つので、previous が無いものだけを 404 として扱う。
        if ($e instanceof NotFoundHttpException && $e->getPrevious() === null) {
            return $this->json(ErrorCode::NotFound);
        }

        if (config()->boolean('app.debug')) {
            return null;
        }

        return $this->json(ErrorCode::InternalError);
    }

    private function json(ErrorCode $code, ?string $message = null): JsonResponse
    {
        return new JsonResponse([
            'code' => $code->value,
            'message' => $message ?? $code->defaultMessage(),
        ], $this->status($code));
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
