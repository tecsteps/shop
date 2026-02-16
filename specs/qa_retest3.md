# QA Retest 3 - 2026-02-16

## Test 1: Customer order detail link from dashboard
**Result: PASS**

- Logged in as customer@acme.test / password
- Account dashboard at /account shows recent orders table with order #1001
- Navigated to /account/orders/1001 - order detail page loads successfully
- Page displays: Order #1001, status "Paid" / "Fulfilled", line items (Classic Cotton T-Shirt - S / White, qty 2, $49.98), shipping address, payment method, and totals (Subtotal $49.98, Shipping $4.99, Tax $7.98, Total $54.97)

## Test 2: Checkout discount display
**Result: PASS**

- Added Classic Cotton T-Shirt (S/White) to cart
- Applied discount code "FLAT5" on /cart - discount shown as "-$5.00", total updated to $19.99
- Proceeded to checkout, filled address (test@example.com, Test User, Teststr 1, Berlin, 10115, Germany)
- Selected Standard Shipping ($4.99)
- On payment step, order summary sidebar correctly displays:
  - Subtotal: $24.99
  - Discount (FLAT5): -$5.00
  - Shipping: $4.99
  - Total: $24.98

**Note:** The discount line is not visible on steps 1 (Contact & Address) and 2 (Shipping) of checkout - it only appears on step 3 (Payment). This may be by design or a minor display gap, but the payment step requirement is satisfied.
