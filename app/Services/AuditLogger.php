<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function record(string $event, ?Model $subject = null, array $context = []): void
    {
        $user = auth()->user();

        Log::channel('audit')->info($event, array_merge([
            'user_id' => $user?->getAuthIdentifier(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ], $context));
    }
}
