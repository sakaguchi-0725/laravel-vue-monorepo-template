<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

abstract class ApplicationException extends RuntimeException
{
    public function __construct(
        string $message = '',
        private ?string $clientMessage = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    abstract public function errorCode(): ErrorCode;

    public function clientMessage(): string
    {
        return $this->clientMessage ?? $this->errorCode()->defaultMessage();
    }
}
