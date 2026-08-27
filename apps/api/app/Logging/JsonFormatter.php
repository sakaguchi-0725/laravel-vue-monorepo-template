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

    private const MASK = '******';

    /**
     * @var list<string>
     */
    private readonly array $maskedKeys;

    /**
     * @param  list<string>  $maskedKeys
     */
    public function __construct(array $maskedKeys = [])
    {
        parent::__construct(DateTimeInterface::RFC3339_EXTENDED);

        $this->maskedKeys = array_map(strtolower(...), $maskedKeys);
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
            ...$this->mask($extra),
        ];

        if ($context !== []) {
            /** @var array<array<mixed>|bool|float|int|string|null> $normalized */
            $normalized = $this->normalizeValue($context);

            $payload['context'] = $this->mask($normalized);
        }

        if ($exception instanceof Throwable) {
            $payload['exception'] = $this->normalizeException($exception);
        }

        return $this->toJson($payload)."\n";
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function mask(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $this->maskedKeys, true)) {
                $values[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->mask($value);
            }
        }

        return $values;
    }
}
