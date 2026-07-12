<?php

namespace App\Services;

use App\Contracts\DnsResolver;

final class NativeDnsResolver implements DnsResolver
{
    public function resolve(string $hostname): array
    {
        $addresses = [];
        $records = @dns_get_record($hostname, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $address = $record['ip'] ?? $record['ipv6'] ?? null;
                if (is_string($address) && filter_var($address, FILTER_VALIDATE_IP)) {
                    $addresses[] = $address;
                }
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbynamel($hostname);
            if (is_array($fallback)) {
                $addresses = array_values(array_filter($fallback, fn (mixed $address): bool => is_string($address) && filter_var($address, FILTER_VALIDATE_IP) !== false));
            }
        }

        return array_values(array_unique($addresses));
    }
}
