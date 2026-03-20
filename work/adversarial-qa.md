# Adversarial QA Report

**Date:** 2026-03-20
**Tester:** Adversarial QA Analyst (Claude)
**Baseline:** 143 passing E2E test cases

---

## Summary

Found **5 FAIL** results and **1 security concern**, against **12 PASS** results. The most critical issue is that negative quantities can be added to cart and successfully checked out, creating orders with negative totals. One issue (FAIL-5) was fixed during this QA session.

---

## FAIL Results

### FAIL-1: Negative quantity accepted in cart (CRITICAL)

- **Extends baseline:** Cart add-to-cart tests
- **What was tried:** Added a product to cart with quantity -3 via the quantity input field
- **Expected behavior:** Validation should reject quantities <= 0
- **Actual behavior:** The negative quantity was accepted. Cart showed quantity -3, subtotal $-74.97, total $-74.97. The entire checkout flow completed successfully, creating Order #1016 with total_amount = -7497 in the database, financial_status = "paid".
- **Severity:** CRITICAL - Financial vulnerability. An attacker could place orders with negative totals, potentially gaming the payment system.
- **Root cause:** `CartService::addLine()` (app/Services/CartService.php:34) does not validate that `$quantity > 0`. The `checkAvailability` inventory check likely passes because a negative requested quantity is always "available."
- **Database proof:** `SELECT total_amount, financial_status FROM orders WHERE order_number = '#1016'` returns `{total_amount: -7497, financial_status: "paid"}`

### FAIL-2: Zero quantity accepted in cart

- **Extends baseline:** Cart add-to-cart tests
- **What was tried:** Added a product to cart with quantity 0
- **Expected behavior:** Validation should reject quantity 0
- **Actual behavior:** A cart line was created with quantity 0 and line total $0.00
- **Severity:** MEDIUM - Allows meaningless cart entries and could cause downstream issues
- **Root cause:** Same as FAIL-1 - no `quantity > 0` validation in `CartService::addLine()`

### FAIL-3: Checkout confirmation pages are publicly accessible (IDOR)

- **Extends baseline:** Checkout confirmation tests
- **What was tried:** Visited `/checkout/confirmation/1`, `/checkout/confirmation/5` without authentication
- **Expected behavior:** Should require authentication or use non-guessable tokens (UUIDs)
- **Actual behavior:** Order confirmation pages are accessible to anyone. They expose: customer email, order subtotal, shipping, tax, and total amounts. Checkout IDs are sequential integers, making enumeration trivial.
- **Severity:** MEDIUM - Information disclosure. An attacker can enumerate all checkout confirmations and see customer emails and order totals.
- **Recommended fix:** Use UUIDs instead of sequential IDs for checkout confirmation URLs, or verify that the visitor's session matches the checkout session.

### FAIL-4: Credit card number stored as public Livewire property

- **Extends baseline:** Checkout payment tests
- **What was tried:** Inspected the Livewire component source and DOM during checkout
- **Expected behavior:** Sensitive payment data should not be exposed in the HTML source
- **Actual behavior:** `cardNumber` is defined as `public string $cardNumber = ''` in `App\Livewire\Storefront\Checkout\Show` (line 43). This means:
  - The card number appears in the `wire:snapshot` JSON attribute in the page HTML source
  - It is transmitted as plain text in every Livewire POST request after the user types it
  - Any browser extension, XSS attack, or DOM inspection tool can read it
- **Severity:** HIGH - PCI compliance violation. Credit card numbers should never be stored in public DOM attributes.
- **Recommended fix:** Use JavaScript to collect card data and send it directly to the payment provider (tokenization), or at minimum make this a non-public property that's only set during the form submission action.

---

## PASS Results

### PASS-1: Admin panel requires authentication
- **What was tried:** Navigated to `/admin` without a session
- **Expected:** Redirect to admin login
- **Actual:** Redirected to `/admin/login` - correct

### PASS-2: Customer account requires authentication
- **What was tried:** Navigated to `/account` without a customer session
- **Expected:** Redirect to customer login
- **Actual:** Redirected to `/account/login` - correct

### PASS-3: Customer cannot access another customer's orders
- **What was tried:** Logged in as customer@acme.test, navigated to `/account/orders/1003` (belongs to jane@example.com)
- **Expected:** 404 or forbidden
- **Actual:** 404 returned - correct

### PASS-4: Customer cannot access admin panel
- **What was tried:** While logged in as a customer, navigated to `/admin`
- **Expected:** Redirect to admin login
- **Actual:** Redirected to `/admin/login` - correct

### PASS-5: Nonexistent resources return 404
- **What was tried:** `/products/nonexistent-handle-xyzzy`, `/collections/nonexistent-handle-xyzzy`, `/account/orders/99999`, `/admin/orders/99999`, `/admin/products/99999/edit`
- **Expected:** Clean 404 pages
- **Actual:** All returned proper 404 pages without stack traces or sensitive info - correct

### PASS-6: Path traversal blocked
- **What was tried:** `/products/../../etc/passwd`
- **Expected:** 404, not file disclosure
- **Actual:** 404 - correct

### PASS-7: XSS in search is escaped
- **What was tried:** `/search?q=<script>alert('xss')</script>`
- **Expected:** Script tag rendered as text, not executed
- **Actual:** Displayed as text, no JS alert triggered - correct

### PASS-8: XSS in admin order view is escaped
- **What was tried:** Created order with XSS payloads in name/address fields, viewed in admin
- **Expected:** Script tags rendered as text
- **Actual:** `<script>alert("xss")</script>` displayed as plain text in shipping/billing address - Blade escaping works correctly

### PASS-9: SQL injection in address fields prevented
- **What was tried:** Set last name to `O'Brien--; DROP TABLE orders;`
- **Expected:** Stored safely, no SQL execution
- **Actual:** Stored as plain text in JSON, no SQL injection - Laravel ORM parameterizes queries correctly

### PASS-10: Empty cart redirects from checkout
- **What was tried:** Navigated to `/checkout` with empty cart
- **Expected:** Redirect to cart
- **Actual:** Redirected to `/cart` showing "Your cart is empty" - correct

### PASS-11: Admin login validates empty fields
- **What was tried:** Submitted admin login form with empty email and password
- **Expected:** Validation errors
- **Actual:** "The email field is required." and "The password field is required." - correct

### PASS-12: Admin login handles XSS in email field
- **What was tried:** Entered `<script>alert('xss')</script>` as email
- **Expected:** Validation error, no XSS execution
- **Actual:** Validation error displayed, no script executed - correct

---

## Log File Inspection

- No unhandled exceptions from normal storefront browsing
- **Livewire state corruption errors observed:** When Livewire component state from one page (e.g., checkout with `cardNumber`, `email`) bleeds into another component (e.g., `storefront.products.show`) during navigation, it causes `PublicPropertyNotFoundException`. This happened twice during testing:
  - `Public property [$cardNumber] not found on component: [storefront.products.show]`
  - `Public property [$email] not found on component: [storefront.products.show]`
- These errors appear to be related to Livewire's client-side state management during SPA-like navigation. While they cause an error dialog for the user, they do not expose sensitive data or crash the server.
- No stack traces or sensitive data exposed in error responses to the browser (404 pages are clean).

---

## Self-Assessment

### Attack Vectors Explored
- Input validation (empty fields, negative numbers, zero, extreme values, XSS, SQL injection, template injection)
- Authentication and authorization (admin access, customer access, cross-customer access)
- URL manipulation (nonexistent resources, path traversal, IDOR on checkout confirmations)
- Cart edge cases (negative quantity, zero quantity, negative total checkout)
- Checkout flow (negative total completion, XSS in address fields)
- Search XSS
- Admin panel XSS rendering
- Log file inspection for unhandled exceptions
- Public Livewire property exposure (card numbers)

### How Hard Did I Try
Moderate to high. I found a critical financial vulnerability (negative quantities/totals) and confirmed it end-to-end through the entire checkout flow including database verification. I also found a PCI-relevant issue with card number exposure and an IDOR on checkout confirmations.

### What Remains Untested
- **Concurrent/state issues:** Opening cart in two tabs, modifying in one, checking the other. The Playwright MCP browser made Livewire interactions unreliable for multi-step flows, limiting concurrent testing.
- **Discount code stacking:** Could not reliably test applying multiple discount codes due to Livewire interaction issues.
- **Rate limiting:** Did not test brute-force login attempts or rapid API calls.
- **File upload attacks:** Did not test malicious file uploads in product media.
- **CSRF token manipulation:** Did not test CSRF bypass attempts.
- **Extremely long strings (1000+ chars):** Did not test buffer overflow-style inputs.
- **International characters in addresses:** Could not test due to Livewire interaction limitations.
- **Cart quantity manipulation after add:** Could not reliably test using the +/- buttons in cart to go negative.
- **Admin product creation with malicious data:** Attempted but Livewire form submission via Playwright was unreliable.
