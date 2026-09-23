<?php

namespace App\Services;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;

class AuditLogger
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
        $request = app()->bound('request') ? app('request') : null;
        $context = array_merge($this->redactSensitiveValues($extra), [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'user_id' => $userId,
            'store_id' => $storeId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip' => $request instanceof HttpRequest ? ($request->ip() ?? '') : '',
            'user_agent' => $request instanceof HttpRequest ? ($request->userAgent() ?? '') : '',
        ]);

        Log::channel('audit')->info($event, $context);
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function redactSensitiveValues(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            if (preg_match('/password|secret|token/i', (string) $key) === 1) {
                $redacted[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveValues($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
