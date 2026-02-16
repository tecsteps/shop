# Phase 8 QA Results - Storefront Side

## Suite 7: Storefront Browsing
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S7-01 | Featured products on home page | PASS | Store name "Acme Fashion", "Classic Cotton T-Shirt", "24.99 EUR" all present |
| S7-02 | Collection with product grid | PASS | /collections/t-shirts shows 4 products in grid with sort dropdown |
| S7-03 | Navigate collection to product | PASS | Clicking product link navigates to product detail page |
| S7-04 | Product detail with variant options | PASS | Size and Color dropdowns present on product page |
| S7-05 | Size and color option values | PASS | S/M/L/XL for Size, White/Black/Navy for Color on Classic Cotton T-Shirt |
| S7-06 | Price updates with variant | PASS | Premium Slim Fit Jeans shows $79.99 with $99.99 compare-at price |
| S7-07 | Search results for "cotton" | PASS | Returns Classic Cotton T-Shirt and other cotton products |
| S7-08 | No results for nonexistent | PASS | "No results found" with "Try a different search term" message |
| S7-09 | No draft products in collections | PASS | "Unreleased Winter Jacket" (draft) not shown in any collection |
| S7-10 | No draft products in search | PASS | Search for "unreleased" returns no results |
| S7-11 | Out of stock deny-policy shows Sold out | PASS | Limited Edition Sneakers shows "Sold out" with disabled button |
| S7-12 | Backorder continue-policy shows Available | PASS | Backorder Denim Jacket shows "Available on backorder" with active Add to cart |
| S7-13 | New arrivals collection | PASS | /collections/new-arrivals shows 7 products |
| S7-14 | Static about page | PASS | /pages/about shows "Our Story", "Our Values", "Our Team" sections |
| S7-15 | Main navigation works | PASS | Collections, Products, Search, Cart, Account links all present and functional |

## Suite 8: Cart Flow
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S8-01 | Add product to cart | PASS | "Added to cart!" flash message appears after clicking Add to cart |
| S8-02 | View cart with item | PASS | Cart shows Classic Cotton T-Shirt, S/White variant, $24.99 |
| S8-03 | Update quantity (+) | PASS | Quantity updates to 2, line total updates to $49.98 |
| S8-04 | Remove item | PASS | Cart shows "Your cart is empty" after removal |
| S8-05 | Add multiple products | PASS | Both Classic Cotton T-Shirt and Organic Hoodie appear in cart |
| S8-06 | Apply WELCOME10 discount | FAIL | No discount code input field exists in cart or checkout UI |
| S8-07 | Invalid discount code error | FAIL | No discount code UI implemented |
| S8-08 | Expired discount code error | FAIL | No discount code UI implemented |
| S8-09 | Maxed out discount code error | FAIL | No discount code UI implemented |
| S8-10 | Apply FREESHIP discount | FAIL | No discount code UI implemented |
| S8-11 | Apply FLAT5 discount | FAIL | No discount code UI implemented |
| S8-12 | Subtotal and total visible | PASS | Subtotal shown on cart page with Proceed to checkout link |

## Suite 9: Checkout Flow
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S9-01 | Full checkout with credit card | PASS | Order created successfully with confirmation page showing order number |
| S9-02 | Shipping methods for DE address | FAIL | No country selector in checkout form; defaults to US, cannot select DE |
| S9-03 | International shipping for US address | PASS | US address shows "International $14.99" shipping option |
| S9-04 | Discount applied during checkout | FAIL | No discount code field in checkout flow |
| S9-05 | Required email validation | PASS | "The email field is required." error shown |
| S9-06 | Required address fields validation | PASS | Required errors for email, firstName, lastName, address1, city, zip |
| S9-07 | Invalid postal code validation | N/A | No postal code format validation implemented, only required check |
| S9-08 | Empty cart prevents checkout | PASS | Redirects to /cart when cart is empty |
| S9-09 | PayPal checkout | PASS | Order #1017 created via PayPal, confirmation shown |
| S9-10 | Bank transfer checkout | PASS | Order #1018 created via bank transfer, confirmation shown |
| S9-11 | Declined card | FAIL | Card 4000000000000002 accepted; cardNumber not passed to mock PSP |
| S9-12 | Insufficient funds card | FAIL | Card 4000000000009995 would also be accepted; same root cause |
| S9-13 | Switch between payment methods | PASS | Credit Card, PayPal, Bank Transfer radio buttons all functional |

## Suite 10: Customer Account
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S10-01 | Register new customer | PASS | e2e-cct3@example.com registered, redirected to home |
| S10-02 | Duplicate email error | PASS | "An account with this email already exists." shown |
| S10-03 | Password mismatch error | PASS | "The password field confirmation does not match." shown |
| S10-04 | Login as customer | PASS | Logged in as John Doe / customer@acme.test, account page shown |
| S10-05 | Invalid customer credentials | PASS | "Invalid credentials." error shown |
| S10-06 | Unauthenticated redirect | PASS | /account redirects to /account/login |
| S10-07 | Order history | PASS | Orders #1001, #1002, #1004, #1010, #1015 visible |
| S10-08 | Order detail | FAIL | Order links use anchor fragments (#1001) instead of route URLs; detail page returns 404 |
| S10-09 | View addresses | PASS | Two addresses shown with Edit/Delete/Set default actions |
| S10-10 | Add new address | PARTIAL | Add address form appears but country field has no default, causing validation error |
| S10-11 | Edit address | PASS | Edit form opens with address fields |
| S10-12 | Logout | PASS | Redirects to /account/login after logout |

## Suite 11: Inventory Enforcement
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S11-01 | Deny-policy blocks add-to-cart | PASS | "Sold out" button disabled for Limited Edition Sneakers |
| S11-02 | Continue-policy allows add-to-cart | PASS | "Available on backorder" with active Add to cart for Backorder Denim Jacket |
| S11-03 | In-stock shows Add to cart | PASS | "In stock" indicator and active Add to cart button |
| S11-04 | Prevents adding more than stock | PARTIAL | Server returns 500 error instead of friendly message when exceeding stock |

## Suite 12: Tenant Isolation
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S12-01 | Store shows own products only | PASS | Only Acme Fashion products shown, no Acme Electronics products |
| S12-02 | Collections contain store products only | PASS | T-Shirts collection has only fashion products |
| S12-03 | Admin only sees store data | PASS | Admin dashboard scoped to Acme Fashion store |
| S12-04 | Search returns store products only | PASS | Search for "wireless" (electronics product) returns no results |
| S12-05 | Customer orders scoped to store | PASS | Customer sees only their orders from Acme Fashion store |

## Suite 13: Responsive / Mobile
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S13-01 | Home on mobile (375x812) | PASS | Hamburger menu button, products in single column, all content visible |
| S13-02 | Product page stacked on mobile | PASS | Product details, variant selectors, Add to cart all accessible |
| S13-03 | Add to cart on mobile | PASS | "Added to cart!" confirmation shown on mobile |
| S13-04 | Cart on mobile | PASS | Cart items, quantity controls, subtotal all functional |
| S13-05 | Checkout on mobile | PASS | Checkout form renders and is usable on mobile viewport |
| S13-06 | Admin login on tablet (768x1024) | PASS | Admin login form renders correctly on tablet |
| S13-07 | Admin sidebar on tablet | PASS | Sidebar with Dashboard, Products, Collections, etc. visible |
| S13-08 | Collection page on mobile | PASS | Collection grid adapts to mobile layout |

## Suite 14: Accessibility
| ID | Test | Result | Notes |
|----|------|--------|-------|
| S14-01 | No JS errors on home | PASS | Zero console errors on home page |
| S14-02 | Heading hierarchy (one h1) | PASS | Single h1 "Welcome to Acme Fashion" |
| S14-03 | ARIA labels on variant selector | PARTIAL | Visual "Size"/"Color" labels present but select elements lack name/aria-label attributes |
| S14-04 | Product images have alt text | PASS | All product images have alt attributes |
| S14-05 | Customer login form labels | PASS | Email and password inputs have proper labels |
| S14-06 | Admin login form labels | PASS | Flux UI uses aria-labelledby for proper labeling |
| S14-07 | Checkout form labels | PASS | All 9 checkout form inputs have proper labels via aria-labelledby |
| S14-08 | Checkout validation errors accessible | PASS | Validation errors appear inline next to each field |
| S14-09 | Keyboard navigation | PASS | "Skip to main content" link present |
| S14-10 | No console errors on cart | PASS | Zero console errors on cart page |
| S14-11 | Search page form labels | PASS | Search input has placeholder "Search products..." |

## Summary
| Metric | Value |
|--------|-------|
| Total | 80 |
| PASS | 62 |
| FAIL | 13 |
| PARTIAL | 4 |
| N/A | 1 |

## Key Issues Found

1. **No discount code UI (S8-06 to S8-11, S9-04)**: The API endpoint for applying discounts exists but the Livewire checkout/cart UI has no discount code input field.

2. **No country selector in checkout (S9-02)**: The checkout form defaults to US with no way to change the country, so domestic shipping rates for DE/EU cannot be tested.

3. **Card number not passed to mock PSP (S9-11, S9-12)**: The Livewire checkout `submitPayment()` method does not forward the `cardNumber` property to the OrderService/PaymentProvider, so declined/insufficient funds test cards are not detected.

4. **Order detail links broken (S10-08)**: The order listing uses anchor fragments (`#1001`) instead of proper route URLs (`/account/orders/1001`). The route exists but order_number includes `#` prefix causing 404.

5. **Address form missing country default (S10-10)**: The add address form has a country field but no default value, causing immediate validation failure.

6. **Stock exceeded returns 500 error (S11-04)**: Adding more items than available stock triggers a server error instead of a user-friendly error message.

7. **Variant selectors lack ARIA labels (S14-03)**: The Size/Color dropdown selects on product pages have no `name` or `aria-label` attributes.
