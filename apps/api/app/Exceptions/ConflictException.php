<?php

declare(strict_types=1);

namespace App\Exceptions;

class ConflictException extends ApplicationException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::Conflict;
    }
}
