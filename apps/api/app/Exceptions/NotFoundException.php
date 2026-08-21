<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends ApplicationException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::NotFound;
    }
}
