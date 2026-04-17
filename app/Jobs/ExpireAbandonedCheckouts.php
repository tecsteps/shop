<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

final class ExpireAbandonedCheckouts
{
    public function handle(CheckoutService $checkoutService): void
    {
        Checkout::query()
            ->select(['id'])
            ->whereIn('status', [
                CheckoutStatus::Started->value,
                CheckoutStatus::Addressed->value,
                CheckoutStatus::ShippingSelected->value,
                CheckoutStatus::PaymentSelected->value,
            ])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->chunkById(100, function (EloquentCollection $checkouts) use ($checkoutService): void {
                /** @var Checkout $checkout */
                foreach ($checkouts as $checkout) {
                    $this->expireCheckout($checkoutService, (int) $checkout->id);
                }
            });
    }

    private function expireCheckout(CheckoutService $checkoutService, int $checkoutId): void
    {
        DB::transaction(function () use ($checkoutService, $checkoutId): void {
            /** @var Checkout|null $checkout */
            $checkout = Checkout::query()
                ->whereKey($checkoutId)
                ->lockForUpdate()
                ->first();

            if (! $checkout instanceof Checkout) {
                return;
            }

            $status = $checkout->status;

            if (! $status instanceof CheckoutStatus) {
                $status = CheckoutStatus::tryFrom((string) $status);
            }

            if (! $status instanceof CheckoutStatus) {
                return;
            }

            if (in_array($status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
                return;
            }

            if ($checkout->expires_at === null || ! $checkout->expires_at->isPast()) {
                return;
            }

            $checkout->status = CheckoutStatus::Expired;
            $checkout->save();

            if ($status === CheckoutStatus::PaymentSelected) {
                $checkoutService->releaseReservedInventoryForCheckout($checkout);
            }
        });
    }
}
