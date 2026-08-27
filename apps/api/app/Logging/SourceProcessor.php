<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

class SourceProcessor implements ProcessorInterface
{
    /**
     * @var list<string>
     */
    private const SKIP_CLASSES = [
        self::class,
        'Monolog\\',
        'Illuminate\\Log\\',
        'Illuminate\\Support\\Facades\\',
    ];

    /**
     * @var list<string>
     */
    private const SKIP_FUNCTIONS = [
        'call_user_func',
        'call_user_func_array',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        $record->extra = [
            ...$record->extra,
            ...($exception instanceof Throwable
                ? $this->fromException($exception)
                : $this->fromBacktrace()),
        ];

        return $record;
    }

    /**
     * @return array{file: string, line: int, class: string|null, function: string|null}
     */
    private function fromException(Throwable $exception): array
    {
        $frame = $exception->getTrace()[0] ?? [];

        return [
            'file' => $this->relativePath($exception->getFile()),
            'line' => $exception->getLine(),
            'class' => $frame['class'] ?? null,
            'function' => $frame['function'] ?? null,
        ];
    }

    /**
     * @return array{file: string|null, line: int|null, class: string|null, function: string|null}
     */
    private function fromBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $index = 0;

        while (isset($trace[$index]) && $this->isSkipped($trace[$index])) {
            $index++;
        }

        $file = $trace[$index - 1]['file'] ?? null;

        return [
            'file' => $file === null ? null : $this->relativePath($file),
            'line' => $trace[$index - 1]['line'] ?? null,
            'class' => $trace[$index]['class'] ?? null,
            'function' => $trace[$index]['function'] ?? null,
        ];
    }

    /**
     * @param  array{function: string, class?: class-string}  $frame
     */
    private function isSkipped(array $frame): bool
    {
        if (! isset($frame['class'])) {
            return in_array($frame['function'], self::SKIP_FUNCTIONS, true);
        }

        foreach (self::SKIP_CLASSES as $skipped) {
            if (str_starts_with($frame['class'], $skipped)) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base)
            ? substr($path, strlen($base))
            : $path;
    }
}
