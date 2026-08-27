<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

abstract class ApplicationException extends RuntimeException
{
    // $message はログの message、$previous は exception.previous として出力される。
    // 両方省くと例外クラス名と発生箇所しかログに残らないため、どちらかは必ず渡す。
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
