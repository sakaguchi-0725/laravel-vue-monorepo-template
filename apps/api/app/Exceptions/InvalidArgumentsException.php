<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidArgumentsException extends ApplicationException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::InvalidArguments;
    }
}
