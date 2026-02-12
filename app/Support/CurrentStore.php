<?php

namespace App\Support;

use App\Models\Store;

class CurrentStore
{
    private ?Store $store = null;

    public function set(?Store $store): void
    {
        $this->store = $store;
    }

    public function get(): ?Store
    {
        return $this->store;
    }

    public function id(): ?int
    {
        return $this->store?->id;
    }

    public function has(): bool
    {
        return $this->store !== null;
    }

    public function clear(): void
    {
        $this->store = null;
    }
}
