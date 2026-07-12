<?php

namespace App\ValueObjects;

final readonly class ValidatedWebhookTarget
{
    public function __construct(
        public string $url,
        public string $hostname,
        public int $port,
        public string $ip,
    ) {}

    public function curlResolveEntry(): string
    {
        $ip = str_contains($this->ip, ':') ? "[{$this->ip}]" : $this->ip;

        return "{$this->hostname}:{$this->port}:{$ip}";
    }
}
