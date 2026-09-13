<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Writes structured JSON audit entries to the `audit` log channel,
 * per spec 06 section 8.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function log(
        string $event,
        ?int $userId = null,
        ?int $storeId = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $extra = [],
    ): void {
        $request = request();

        Log::channel('audit')->info($event, array_merge([
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'user_id' => $userId,
            'store_id' => $storeId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ], $extra));
    }
}
