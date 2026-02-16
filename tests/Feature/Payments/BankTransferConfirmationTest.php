<?php

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('admin can confirm bank transfer payment', function () {
    $order = Order::factory()->for($this->ctx['store'])->create([
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => 'bank_transfer',
        'total' => 5000,
    ]);
    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::BankTransfer,
        'amount' => 5000,
        'status' => \App\Enums\PaymentStatus::Pending,
        'captured_at' => null,
    ]);

    $order->update(['financial_status' => FinancialStatus::Paid]);

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});

it('cannot confirm already confirmed payment', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['payment_method' => 'bank_transfer']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);
});
