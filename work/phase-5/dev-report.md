# Phase 5: Payments, Orders, Fulfillment - Dev Report

## Summary

Phase 5 has been implemented following TDD with Pest. All 478 tests pass (including 45 new Phase 5 tests), with zero failures.

## What Was Built

### Step 5.1: Migrations (7 new tables)
- `customer_addresses` - Saved addresses for customers with JSON address data
- `orders` - Placed orders with financial and fulfillment tracking, CHECK constraint triggers
- `order_lines` - Order line items with snapshot data (survives product deletion)
- `payments` - Payment attempts/captures with encrypted raw response
- `refunds` - Refund records linked to orders and payments
- `fulfillments` - Shipment tracking with shipped_at and delivered_at
- `fulfillment_lines` - Line-level fulfillment granularity with unique constraint

### Step 5.2: Models and Enums
**Models:** CustomerAddress, Order, OrderLine, Payment, Refund, Fulfillment, FulfillmentLine
**Enums:** OrderStatus, FinancialStatus, FulfillmentStatus, PaymentStatus, RefundStatus, FulfillmentShipmentStatus
**Updated:** Customer (added addresses/orders relationships), Store (added orders relationship), OrderLine (added fulfillmentLines relationship)
**Factories:** Created for all 7 new models

### Step 5.3: Payment Service (Mock PSP)
- `App\Contracts\PaymentProvider` interface with charge() and refund()
- `App\Services\Payment\MockPaymentProvider` implementation
  - Credit card: magic numbers for decline (4000000000000002) and insufficient funds (4000000000009995)
  - PayPal: always succeeds with captured status
  - Bank transfer: returns pending status with mock bank details
- `App\Services\Payment\PaymentResult` and `RefundResult` value objects
- `App\Services\PaymentService` for bank transfer confirmation and digital auto-fulfillment
- `App\Jobs\CancelUnpaidBankTransferOrders` for auto-cancelling expired bank transfers
- Bound `PaymentProvider` to `MockPaymentProvider` in AppServiceProvider

### Step 5.4: Order Service
- `App\Services\OrderService` with:
  - `createFromCheckout()` - atomic order creation from checkout with snapshots, payment record, inventory handling, discount usage increment, cart conversion
  - `generateOrderNumber()` - sequential per store starting at #1001
  - `cancel()` - cancels unfulfilled orders, releases/restocks inventory based on financial status

### Step 5.5: Refund Service
- `App\Services\RefundService` with create() method
- Validates refund amount against remaining refundable amount
- Calls provider refund, creates refund record
- Updates financial_status to partially_refunded or refunded
- Optional inventory restock

### Step 5.6: Fulfillment Service
- `App\Services\FulfillmentService` with:
  - `create()` - fulfillment guard (paid/partially_refunded only), validates unfulfilled quantities, updates order fulfillment_status
  - `markAsShipped()` - transitions pending to shipped with tracking data
  - `markAsDelivered()` - transitions shipped to delivered

### Integration: CheckoutService Updated
- `completeCheckout()` now processes payment via MockPaymentProvider, creates order via OrderService on success, releases inventory on payment failure
- Return type changed from Checkout to Order
- Existing checkout tests updated to match new API

### Events
OrderCreated, OrderPaid, OrderCancelled, OrderRefunded, OrderFulfilled, FulfillmentCreated, FulfillmentShipped

### Exceptions
PaymentFailedException, FulfillmentGuardException

## Test Coverage

| Test File | Tests | Status |
|---|---|---|
| MockPaymentProviderTest | 6 | Pass |
| PaymentServiceTest | 5 | Pass |
| BankTransferConfirmationTest | 6 | Pass |
| OrderCreationTest | 9 | Pass |
| RefundTest | 7 | Pass |
| FulfillmentTest | 12 | Pass |
| **Total Phase 5** | **45** | **Pass** |

All 478 tests in the full suite pass (including 433 pre-existing tests from Phases 1-4).

## Files Modified (Existing)
- `app/Models/Customer.php` - Added addresses() and orders() relationships
- `app/Models/Store.php` - Added orders() relationship
- `app/Providers/AppServiceProvider.php` - Added PaymentProvider binding
- `app/Services/CheckoutService.php` - Integrated with PaymentProvider and OrderService
- `tests/Feature/Checkout/CheckoutFlowTest.php` - Updated for new completeCheckout() return type
- `tests/Feature/Products/ProductCrudTest.php` - Fixed order_lines column names
- `tests/Feature/Products/VariantTest.php` - Fixed order_lines column names

## Reviewer Gap Items Addressed
1. FulfillmentCreated and FulfillmentShipped events - implemented
2. delivered_at and shipped_at columns on fulfillments table - implemented
