<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        Log::info('request', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $this->durationMs($request),
            'user_agent' => $request->userAgent(),
            // 'client_ip' => $request->ip(),
        ]);
    }

    private function durationMs(Request $request): ?float
    {
        $startedAt = $request->server('REQUEST_TIME_FLOAT');

        if (! is_numeric($startedAt)) {
            return null;
        }

        return round((microtime(true) - (float) $startedAt) * 1000, 2);
    }
}
