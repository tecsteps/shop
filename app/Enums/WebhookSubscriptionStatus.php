<?php

namespace App\Enums;

enum WebhookSubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Disabled = 'disabled';
}
