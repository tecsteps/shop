<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class PaymentPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->isAnyRole($user, $payment->order->store_id);
    }

    public function confirm(User $user, Payment $payment): bool
    {
        return $this->isOwnerOrAdmin($user, $payment->order->store_id);
    }
}
