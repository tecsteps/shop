<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $changes
     * @param  array<string, mixed>  $extra
     */
    public function log(
        string $event,
        ?int $userId = null,
        ?int $storeId = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $changes = null,
        array $extra = [],
    ): void {
        $request = request();
        $entry = array_filter([
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'user_id' => $userId,
            'store_id' => $storeId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip' => $request->ip() ?? 'console',
            'user_agent' => (string) $request->userAgent(),
            'changes' => $changes,
            ...$extra,
        ], fn (mixed $value): bool => $value !== null);

        Log::channel('audit')->info((string) json_encode($entry, JSON_UNESCAPED_SLASHES));
    }
}
