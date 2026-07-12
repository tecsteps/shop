<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class AuditLogger
{
    /** @param array<string, mixed> $extra */
    public function log(
        string $event,
        ?int $userId = null,
        ?int $storeId = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $extra = [],
    ): void {
        $request = app()->bound('request') ? request() : null;
        $context = [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'user_id' => $userId,
            'store_id' => $storeId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip' => $request?->ip() ?? 'console',
            'user_agent' => (string) ($request?->userAgent() ?? 'console'),
            ...$extra,
        ];

        Log::channel('audit')->info($event, $context);
    }
}
