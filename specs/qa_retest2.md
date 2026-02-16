# QA Retest 2 - Bug Fix Verification

**Date:** 2026-02-16
**Tested against:** http://shop.test (Playwright MCP)
**Branch:** claude-code-team-3

---

## Test 1: Percent discount (S8-06) - PASS

**Steps:** Navigated to /products/classic-cotton-t-shirt, selected variant, added to cart. Navigated to /cart. Entered "WELCOME10" in discount input, clicked Apply.

**Result:** Discount correctly calculated as 10% of cart subtotal. With $624.94 subtotal (multiple items in cart), the discount showed -$62.49 which is exactly 10%. Discount was also successfully removed via the Remove button.

---

## Test 2: Checkout discount display (S9-04) - FAIL

**Steps:** Added product to cart. Applied "FLAT5" discount in cart - correctly showed -$5.00. Proceeded to checkout. Filled contact/address form (test@example.com, DE, Berlin, 10115, Test User, Teststr 1). Selected Standard Shipping ($4.99). Reached payment step.

**Result:** The checkout order summary does NOT display the discount line at any step. The summary only shows line items, subtotal, and shipping. No discount row or total row is rendered. The checkout Blade template (`resources/views/livewire/storefront/checkout/show.blade.php`) contains no discount-related markup.

**Root cause:** The checkout order summary partial does not include discount information from the session.

---

## Test 3: Declined card retry (S9-12) - PASS

**Steps:** At checkout payment step, entered card number 4000000000000002, clicked Place order. Received "Your card was declined." error message. Changed card to 4000000000009995, clicked Place order again.

**Result:** Second attempt showed "Your card has insufficient funds." - a friendly user-facing error, not a 500 error page. Card retry works correctly after a declined payment.

---

## Test 4: Customer order detail (S10-08) - FAIL

**Steps:** Navigated to /account/login. Logged in as customer@acme.test / password. Viewed account dashboard with order list. Clicked order #1001 link.

**Result:** The order link on the dashboard generates URLs like `/account/orders/#1001` where `#1001` becomes a URL hash fragment instead of a route segment. Clicking the link does not navigate to the order detail page - it stays on `/account`. However, navigating directly to `/account/orders/1001` loads the order detail correctly with full order info (items, totals, addresses, payment, shipping status).

**Root cause:** In `resources/views/livewire/storefront/account/dashboard.blade.php` line 41, the link uses `url('/account/orders/' . $order->order_number)` without stripping the `#` prefix from `order_number`. The orders index page (`index.blade.php` line 20) correctly uses `ltrim($order->order_number, '#')`, but the dashboard template does not.

---

## Test 5: Stock exceeded (S11-04) - PASS

**Steps:** Navigated to /products/premium-slim-fit-jeans (stock: 8 units). Added to cart. Navigated to /cart. Clicked the + button repeatedly to increase quantity.

**Result:** Quantity incremented up to 8 (max stock) and then stopped. No 500 error page was shown. The exception is caught in the Livewire component (`Cart/Show.php` line 32-34). However, the flash message "Not enough stock available." is not displayed in the cart template because the Blade view does not render `session('error')` flash messages. The user sees no error text but also no crash - the quantity simply stays at the stock limit.

**Note:** While no 500 error occurs (the primary fix), the lack of visible feedback to the user is a minor UX issue.

---

## Summary

| Test | Bug ID | Description | Result |
|------|--------|-------------|--------|
| 1 | S8-06 | Percent discount calculation | PASS |
| 2 | S9-04 | Checkout discount display | FAIL |
| 3 | S9-12 | Declined card retry | PASS |
| 4 | S10-08 | Customer order detail link | FAIL |
| 5 | S11-04 | Stock exceeded error handling | PASS |

**Overall: 3 PASS / 2 FAIL**
