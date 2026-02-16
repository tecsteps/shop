# E2E Storefront Test Results

**Date:** 2026-02-16
**Branch:** claude-code-team-3
**Tester:** Claude Opus 4.6 (Playwright MCP)

## Critical Bugs Found

1. **`current_store` binding missing on Livewire update requests** - `app('current_store')` in `app/Livewire/Storefront/Products/Show.php:91` fails with 500 error during Livewire AJAX calls (addToCart). The store middleware does not bind `current_store` on the Livewire update endpoint. This blocks ALL cart, checkout, and interactive Livewire functionality.

2. **Customer login redirects to admin** - `customer@acme.test` authenticates via storefront login form but redirects to `/admin` instead of `/account`. The storefront login Livewire component appears to authenticate against the admin guard rather than the customer guard.

3. **Inventory status display incorrect** - Products with 0 stock (deny policy: limited-edition-sneakers, continue policy: backorder-denim-jacket) both show "In stock" instead of "Sold out" or backorder messaging.

---

## Suite 7: Storefront Browsing

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S7-01 | Featured products on home | PASS | "Acme Fashion" store name, "Classic Cotton T-Shirt" with "24.99 EUR" displayed correctly. 8 featured products shown. |
| S7-02 | Collection with product grid | PASS | /collections/t-shirts shows "T-Shirts" heading, 4 products in grid, sort dropdown |
| S7-03 | Navigate from collection to product | PASS | Clicking product from collection navigates to product detail page |
| S7-04 | Product detail with variant options | PASS | /products/classic-cotton-t-shirt shows Size and Color dropdowns |
| S7-05 | Size and color options | PASS | Size: S, M, L, XL. Color: White, Black, Navy. All present. |
| S7-06 | Compare-at pricing | PASS | /products/premium-slim-fit-jeans shows $79.99 sale price + $99.99 strikethrough, "Sale" badge |
| S7-07 | Search "cotton" | PASS | Returns "Classic Cotton T-Shirt" plus related products (Organic Hoodie, Graphic Print Tee, etc.) |
| S7-08 | Search nonexistent product | PASS | "No results found" with "Try a different search term" message |
| S7-09 | No draft products in collections | PASS | Homepage and collections only show active products. "Unreleased Winter Jacket" (draft) not visible. |
| S7-10 | Search "draft" returns no results | PASS | No draft products appear in search results |
| S7-11 | Out-of-stock deny-policy shows "Sold out" | FAIL | /products/limited-edition-sneakers shows "In stock" despite 0 quantity with deny policy. Should show "Sold out" with disabled add-to-cart. |
| S7-12 | Continue-policy backorder messaging | FAIL | /products/backorder-denim-jacket shows "In stock" despite 0 quantity with continue policy. Description mentions backorder but no explicit backorder badge/status. |
| S7-13 | New arrivals collection | PASS | /collections/new-arrivals shows 7 products with heading and description |
| S7-14 | About page | PASS | /pages/about shows "About Us" with Our Story, Our Values, Our Team sections |
| S7-15 | Main navigation works | PASS | Clicking "T-Shirts" collection link from homepage navigates to /collections/t-shirts |

## Suite 8: Cart Flow

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S8-01 | Add product to cart | FAIL | 500 error: `Target class [current_store] does not exist` when clicking Add to cart. Livewire update endpoint missing store binding. |
| S8-02 | View cart with item | FAIL | Cart is empty due to S8-01 failure. Cart page itself renders correctly with "Your cart is empty" message. |
| S8-03 | Update quantity | FAIL | Blocked by S8-01 |
| S8-04 | Remove item | FAIL | Blocked by S8-01 |
| S8-05 | Add multiple products | FAIL | Blocked by S8-01 |
| S8-06 | Apply WELCOME10 discount | FAIL | Blocked by S8-01 |
| S8-07 | Invalid discount code | FAIL | Blocked by S8-01 |
| S8-08 | Expired discount EXPIRED20 | FAIL | Blocked by S8-01 |
| S8-09 | Maxed out discount MAXED | FAIL | Blocked by S8-01 |
| S8-10 | Apply FREESHIP discount | FAIL | Blocked by S8-01 |
| S8-11 | Apply FLAT5 discount | FAIL | Blocked by S8-01 |
| S8-12 | Cart subtotal and total | FAIL | Blocked by S8-01 |

## Suite 9: Checkout Flow

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S9-01 | Full checkout with credit card | FAIL | Blocked by cart failure (S8-01) |
| S9-02 | Shipping methods for DE address | FAIL | Blocked by S8-01 |
| S9-03 | International shipping for US | FAIL | Blocked by S8-01 |
| S9-04 | Discount in checkout | FAIL | Blocked by S8-01 |
| S9-05 | Validate required email | FAIL | Blocked by S8-01 |
| S9-06 | Validate required address | FAIL | Blocked by S8-01 |
| S9-07 | Invalid postal code | FAIL | Blocked by S8-01 |
| S9-08 | Empty cart checkout prevented | N/A | Cart page shows empty state with "Continue shopping" link - this works. But cannot test the checkout redirect behavior due to cart bug. |
| S9-09 | Checkout with PayPal | FAIL | Blocked by S8-01 |
| S9-10 | Checkout with bank transfer | FAIL | Blocked by S8-01 |
| S9-11 | Declined card | FAIL | Blocked by S8-01 |
| S9-12 | Insufficient funds | FAIL | Blocked by S8-01 |
| S9-13 | Switch payment methods | FAIL | Blocked by S8-01 |

## Suite 10: Customer Account

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S10-01 | Register new customer | PASS | Registered "New Customer E2E" (e2e-cct3@example.com). Redirects to homepage (not /account), but account is created and accessible. |
| S10-02 | Duplicate email error | N/A | Not tested due to time constraints from debugging blocking bugs |
| S10-03 | Mismatched passwords error | N/A | Not tested |
| S10-04 | Log in as customer | FAIL | customer@acme.test/password authenticates but redirects to /admin instead of /account. The storefront login uses the wrong auth guard. |
| S10-05 | Invalid credentials error | PASS | Entering wrong credentials shows "Invalid credentials." error message |
| S10-06 | Unauthenticated redirect | PASS | /account redirects to /account/login when not authenticated |
| S10-07 | Order history shows orders | FAIL | Cannot access as customer@acme.test due to login redirect to admin |
| S10-08 | Order detail for #1001 | FAIL | Blocked by S10-04 |
| S10-09 | View addresses | FAIL | Blocked by S10-04 |
| S10-10 | Add new address | FAIL | Blocked by S10-04 |
| S10-11 | Edit address | FAIL | Blocked by S10-04 |
| S10-12 | Log out | PASS | Logout button works, redirects to /account/login |

## Suite 11: Inventory Enforcement

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S11-01 | Out-of-stock deny blocks add-to-cart | FAIL | Product page shows "In stock" and enabled Add to cart button for 0-stock deny-policy product (limited-edition-sneakers). Should show "Sold out" and disable button. |
| S11-02 | Continue-policy allows add-to-cart | FAIL | Livewire 500 error blocks add-to-cart. Additionally, status incorrectly shows "In stock" instead of backorder messaging. |
| S11-03 | In-stock product shows Add to cart enabled | PASS | Classic Cotton T-Shirt (in stock) shows enabled "Add to cart" button |
| S11-04 | Cannot add more than available stock | FAIL | Blocked by S8-01 (Livewire update 500 error) |

## Suite 12: Tenant Isolation

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S12-01 | Storefront shows current store data | PASS | Homepage shows "Acme Fashion" branding. All products belong to the store. No cross-tenant data visible. |
| S12-02 | Store binding on Livewire updates | FAIL | `current_store` binding is missing during Livewire AJAX requests, causing 500 errors. |

## Suite 13: Responsive

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S13-01 | Homepage at 375x812 (mobile) | PASS | Hamburger menu ("Open navigation" button) shown. Products stack vertically. All content accessible. |
| S13-02 | Product page at 375x812 | PASS | Product details render correctly in single column |
| S13-03 | Collection at 375x812 | PASS | Products display in single-column grid |
| S13-04 | Cart at 375x812 | PASS | Empty cart message renders correctly |
| S13-05 | Homepage at 768x1024 (tablet) | PASS | Navigation hidden behind hamburger at this breakpoint. Content adapts. |
| S13-06 | Product page at 768x1024 | PASS | Layout adapts to tablet width |
| S13-07 | Search at mobile | PASS | Search page functional at mobile width |
| S13-08 | Footer at mobile | PASS | Footer stacks vertically, all links accessible |

## Suite 14: Accessibility

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S14-01 | No console errors on page load | PASS | No JavaScript errors on initial storefront page load (errors only occur on Livewire updates) |
| S14-02 | Heading hierarchy | PASS | H1 > H2 > H3 hierarchy correct on homepage (Welcome > Featured Products/Shop by Collection > Product names) |
| S14-03 | ARIA labels on navigation | PASS | `aria-label="Main navigation"` on nav, `aria-label="Breadcrumb"` on breadcrumbs |
| S14-04 | Skip to main content link | PASS | "Skip to main content" link present as first element with href="#main-content" |
| S14-05 | Form labels on search | PASS | Search input has "Search products..." placeholder and proper labeling |
| S14-06 | Form labels on login | PARTIAL | Email and Password fields have labels. Autocomplete attributes missing per browser console warning. |
| S14-07 | Form labels on register | PARTIAL | All fields labeled (First Name, Last Name, Email, Password, Confirm Password). Autocomplete attributes missing. |
| S14-08 | Keyboard navigation | PASS | All interactive elements are focusable links/buttons |
| S14-09 | aria-live on price | PASS | Price container has `aria-live="polite"` for dynamic updates |
| S14-10 | Image alt text | PARTIAL | Product images use generic placeholder SVGs without alt text (no actual product images uploaded) |
| S14-11 | Cart icon accessibility | PASS | Cart and search links have icon images with proper structure |

---

## Summary

| Suite | Total | Pass | Fail | Partial | N/A |
|-------|-------|------|------|---------|-----|
| S7: Storefront Browsing | 15 | 12 | 2 | 0 | 1 |
| S8: Cart Flow | 12 | 0 | 12 | 0 | 0 |
| S9: Checkout Flow | 13 | 0 | 12 | 0 | 1 |
| S10: Customer Account | 12 | 4 | 5 | 0 | 3 |
| S11: Inventory Enforcement | 4 | 1 | 3 | 0 | 0 |
| S12: Tenant Isolation | 2 | 1 | 1 | 0 | 0 |
| S13: Responsive | 8 | 8 | 0 | 0 | 0 |
| S14: Accessibility | 11 | 8 | 0 | 3 | 0 |
| **Total** | **77** | **34** | **35** | **3** | **5** |

## Root Causes (Priority Order)

1. **P0 - `current_store` not bound on Livewire update endpoint**: The middleware that binds `app('current_store')` does not run for the Livewire update route (`/livewire-*/update`). This causes 500 errors on ALL Livewire actions (addToCart, etc.). Blocks 24+ tests.

2. **P0 - Customer login uses wrong auth guard**: The storefront login component (`App\Livewire\Storefront\Account\Login`) authenticates against the admin/default guard. After login, customers are redirected to `/admin` instead of `/account`. Blocks 5+ tests.

3. **P1 - Inventory status not reflecting actual stock**: Products with `quantity_on_hand = 0` display "In stock" regardless of inventory policy. The product detail component does not check actual inventory levels when rendering the stock status.
