# QA Re-test Results

**Date:** 2026-02-16
**Tester:** Claude (Playwright MCP browser tools)
**Environment:** http://shop.test (local Herd)

---

## Cart Discounts

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S8-06 | Apply WELCOME10 | PARTIAL | Discount applied and shown ("Discount applied: WELCOME10"), but amount is wrong: shows -$0.02 instead of expected -$2.50 (10% of $24.99). Likely a double-division bug in percent discount calculation. |
| S8-07 | Apply INVALIDCODE | PASS | Error message: "This discount code is not valid." |
| S8-08 | Apply EXPIRED20 | PASS | Error message: "This discount code is no longer active." |
| S8-09 | Apply MAXED | PASS | Error message: "This discount code has reached its usage limit." |
| S8-10 | Apply FREESHIP | PASS | Shows "Discount applied: FREESHIP" with "Free shipping" label. |
| S8-11 | Apply FLAT5 | PASS | Shows "Discount applied: FLAT5" with -$5.00 discount. Total correctly shows $19.99 ($24.99 - $5.00). |

---

## Checkout Country (S9-02)

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S9-02 | Country default DE, domestic shipping | PASS | Country selector defaults to "Germany". After filling address and continuing, shipping step shows "Standard Shipping $4.99" (domestic rate). |

---

## Checkout Discount (S9-04)

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S9-04 | Discount shown in checkout totals | FAIL | FLAT5 discount was applied in cart but the checkout order summary only shows "Subtotal $24.99" with no discount line. Discount is not carried through or displayed in checkout. |

---

## Payment Errors

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S9-11 | Declined card (4000000000000002) | PASS | Error message: "Your card was declined." displayed on payment step. |
| S9-12 | Insufficient funds (4000000000009995) | FAIL | After the first declined card test, attempting a second payment triggers "InvalidCheckoutTransitionException" (500 error). The checkout state machine does not allow retrying payment after a decline. No specific "insufficient funds" message is shown. |

---

## Customer Order Detail (S10-08)

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S10-08 | View order #1001 detail | PARTIAL | Order list page displays correctly with all orders. However, order detail links use anchor URLs (e.g., `/account/orders/#1001`) instead of route URLs (e.g., `/account/orders/%231001`). The `#` in the order number causes the link to be interpreted as a page fragment, so clicking does nothing. Navigating directly to `/account/orders/%231001` works and shows full order details (items, address, payment, totals). |

---

## Address Default Country (S10-10)

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S10-10 | Add address - country defaults to DE | PASS | "Add address" form shows country field pre-filled with "DE". Note: country is a text input rather than a dropdown (minor UX difference from checkout). |

---

## Analytics KPIs

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S18-02 | Revenue, Orders, Visits KPIs | PASS | All KPIs visible: Revenue ($1,743.14), Orders (19), Average Order Value ($91.74), Visits (228). |
| S18-03 | Conversion Funnel | PASS | "Conversion Funnel" section visible with Visits (228), Orders (19), Conversion rate: 8.3%. |

---

## Stock Exceeded (S11-04)

| Test ID | Scenario | Result | Notes |
|---------|----------|--------|-------|
| S11-04 | Increment cart qty beyond stock | FAIL | Adding Running Sneakers (stock: 5) to cart and clicking "+" past 5 triggers unhandled `InsufficientInventoryException` (500 error). Laravel's error dialog appears instead of a friendly user-facing message. The `updateQuantity` method in `Cart\Show` Livewire component does not catch the exception. Quantity does cap at 5. |

---

## Summary

| Status | Count |
|--------|-------|
| PASS | 9 |
| PARTIAL | 2 |
| FAIL | 4 |

### Bugs Found

1. **S8-06 - Percent discount calculation bug:** WELCOME10 (10%) applies -$0.02 on a $24.99 item instead of -$2.50. Likely dividing cents by 100 again.
2. **S9-04 - Discount not shown in checkout:** Cart discount (FLAT5) is not displayed in checkout order summary totals.
3. **S9-12 - Checkout state machine blocks payment retry:** After a declined card, attempting another payment throws `InvalidCheckoutTransitionException` (500). State machine needs to allow re-entering the payment step after a failure.
4. **S10-08 - Order detail links broken:** Order list links use `/#1001` (fragment) instead of URL-encoded `/%231001`. The `#` prefix in order numbers causes the link to act as an anchor.
5. **S11-04 - Unhandled inventory exception in cart:** `Cart\Show::updateQuantity()` does not catch `InsufficientInventoryException`, resulting in a 500 error instead of a friendly message when incrementing quantity beyond available stock.

### Additional Observations

- Playwright MCP's accessibility-tree click does not trigger Livewire `wire:click` actions. DOM `.click()` via `page.evaluate()` is required to interact with Livewire components.
- The storefront displays prices with `$` prefix even though the store currency is EUR. This may be intentional for the test environment or a formatting issue.
