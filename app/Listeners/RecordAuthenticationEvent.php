<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;

class RecordAuthenticationEvent
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Login|Failed|Logout $event): void
    {
        $name = match (true) {
            $event instanceof Login => 'auth.login',
            $event instanceof Failed => 'auth.failed',
            default => 'auth.logout',
        };
        $subject = $event->user instanceof Model ? $event->user : null;
        $context = ['guard' => $event->guard];

        if ($event instanceof Failed) {
            $context['identifier'] = $event->credentials['email'] ?? null;
        }

        $this->audit->record($name, $subject, $context);
    }
}
