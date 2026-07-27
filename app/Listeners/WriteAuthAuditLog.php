<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Writes admin panel logins to the audit log channel (spec 06 §4.6).
 * Customer storefront logins use the "customer" guard and are ignored.
 */
class WriteAuthAuditLog
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        Log::channel('audit')->info('auth.login', [
            'guard' => $event->guard,
            'user_id' => $event->user->getAuthIdentifier(),
            'email' => $event->user->email ?? null,
            'remember' => $event->remember,
            'ip' => request()->ip(),
        ]);
    }
}
