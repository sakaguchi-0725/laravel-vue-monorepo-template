<?php

declare(strict_types=1);

namespace App\Exceptions;

class PermissionDeniedException extends ApplicationException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::PermissionDenied;
    }
}
