<?php

namespace App\Services;

use App\Contracts\DnsResolver;
use App\Exceptions\UnsafeWebhookTargetException;
use App\ValueObjects\ValidatedWebhookTarget;

final class WebhookTargetValidator
{
    public function __construct(private readonly DnsResolver $dns) {}

    public function validate(string $url): ValidatedWebhookTarget
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (! is_array($parts)
            || mb_strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || ! is_string($parts['host'] ?? null)
            || isset($parts['user'])
            || isset($parts['pass'])) {
            throw new UnsafeWebhookTargetException('Webhook targets must be absolute HTTPS URLs without embedded credentials.');
        }

        $hostname = mb_strtolower(trim($parts['host'], '[]\.'));
        if ($hostname === ''
            || preg_match('/[^a-z0-9.:-]/', $hostname) === 1
            || $hostname === 'localhost'
            || str_ends_with($hostname, '.localhost')
            || str_ends_with($hostname, '.local')
            || str_ends_with($hostname, '.internal')) {
            throw new UnsafeWebhookTargetException('The webhook hostname is not allowed.');
        }

        if (preg_match('/^[0-9.]+$/', $hostname) === 1 && filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new UnsafeWebhookTargetException('Obfuscated IP address formats are not allowed.');
        }

        $addresses = filter_var($hostname, FILTER_VALIDATE_IP) !== false
            ? [$hostname]
            : $this->dns->resolve($hostname);
        if ($addresses === [] || collect($addresses)->contains(fn (string $address): bool => ! $this->isPublicAddress($address))) {
            throw new UnsafeWebhookTargetException('The webhook hostname must resolve exclusively to public IP addresses.');
        }

        $port = (int) ($parts['port'] ?? 443);
        if ($port < 1 || $port > 65535) {
            throw new UnsafeWebhookTargetException('The webhook port is invalid.');
        }

        return new ValidatedWebhookTarget($url, $hostname, $port, $addresses[0]);
    }

    private function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach ([
            '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24',
            '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24',
            '64:ff9b:1::/48', '100::/64', '2001:db8::/32',
        ] as $cidr) {
            if ($this->inCidr($address, $cidr)) {
                return false;
            }
        }

        return true;
    }

    private function inCidr(string $address, string $cidr): bool
    {
        [$network, $prefix] = explode('/', $cidr, 2);
        $addressBytes = @inet_pton($address);
        $networkBytes = @inet_pton($network);
        if ($addressBytes === false || $networkBytes === false || strlen($addressBytes) !== strlen($networkBytes)) {
            return false;
        }

        $bits = (int) $prefix;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        if (substr($addressBytes, 0, $bytes) !== substr($networkBytes, 0, $bytes)) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;

        return (ord($addressBytes[$bytes]) & $mask) === (ord($networkBytes[$bytes]) & $mask);
    }
}
