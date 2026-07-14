<?php

namespace App\Contracts;

interface DnsResolver
{
    /** @return list<string> */
    public function resolve(string $hostname): array;
}
