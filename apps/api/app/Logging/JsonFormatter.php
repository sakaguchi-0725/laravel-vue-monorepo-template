<?php

declare(strict_types=1);

namespace App\Logging;

use DateTimeInterface;
use Illuminate\Support\Arr;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Throwable;

class JsonFormatter extends NormalizerFormatter
{
    /**
     * @var list<string>
     */
    private const RESERVED_KEYS = ['time', 'level', 'level_value', 'message', 'context', 'exception'];

    public function __construct()
    {
        parent::__construct(DateTimeInterface::RFC3339_EXTENDED);
    }

    public function format(LogRecord $record): string
    {
        $context = $record->context;
        $exception = $context['exception'] ?? null;
        unset($context['exception']);

        /** @var array<string, array<mixed>|bool|float|int|string|null> $extra */
        $extra = $this->normalizeValue(Arr::except($record->extra, self::RESERVED_KEYS));

        $payload = [
            'time' => $this->formatDate($record->datetime),
            'level' => $record->level->getName(),
            'level_value' => $record->level->value,
            'message' => $record->message,
            ...$extra,
        ];

        if ($context !== []) {
            $payload['context'] = $this->normalizeValue($context);
        }

        if ($exception instanceof Throwable) {
            $payload['exception'] = $this->normalizeException($exception);
        }

        return $this->toJson($payload)."\n";
    }
}
