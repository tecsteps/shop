<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;

final class TenantDatabaseTokenRepository extends DatabaseTokenRepository
{
    /** @return array<string, mixed> */
    protected function getPayload($email, #[\SensitiveParameter] $token): array
    {
        return ['store_id' => $this->storeId(), ...parent::getPayload($email, $token)];
    }

    protected function getTable()
    {
        return parent::getTable()->where('store_id', $this->storeId());
    }

    private function storeId(): int
    {
        if (! app()->bound('current_store')) {
            throw new \LogicException('Customer password reset requires a current store.');
        }

        return (int) app('current_store')->id;
    }
}
