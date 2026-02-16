# Comprehensive Test Plan: 5-Shop Comparison

> Systematic E2E test plan to compare 5 online shops built from the same specification by different AI coding agents. Tests are executed via Playwright MCP (browser controlled by a coding agent, not scripted). All 143 acceptance tests across 18 suites are included.

---

## 1. Shop Registry

### Shop 1: Claude Code Team Mode

| Property | Value |
|----------|-------|
| Short Name | `claude-code-team` |
| Storefront URL | `https://claude-code-team.agentic-engineers.dev/` |
| Admin URL | `https://claude-code-team.agentic-engineers.dev/admin` |
| Admin Email | `admin@acme.test` |
| Admin Password | `password` |
| Customer Email | `customer@acme.test` |
| Customer Password | `password` |

### Shop 2: Claude Code Sub-Agents

| Property | Value |
|----------|-------|
| Short Name | `claude-subagents` |
| Storefront URL | `https://claude-subagents.agentic-engineers.dev/` |
| Admin URL | `https://claude-subagents.agentic-engineers.dev/admin` |
| Admin Email | `admin@acme.test` |
| Admin Password | `password` |
| Customer Email | `customer@acme.test` |
| Customer Password | `password` |

### Shop 3: Codex Sub-Agents

| Property | Value |
|----------|-------|
| Short Name | `codex-subagents` |
| Storefront URL | `https://codex-subagents.agentic-engineers.dev/` |
| Admin URL | `https://codex-subagents.agentic-engineers.dev/admin` |
| Admin Email | `admin@acme.test` |
| Admin Password | `password` |
| Customer Email | `customer@acme.test` |
| Customer Password | `password` |

### Shop 4: Claude Code Team v2

| Property | Value |
|----------|-------|
| Short Name | `claude-code-team-2` |
| Storefront URL | `https://claude-code-team-2.agentic-engineers.dev/` |
| Admin URL | `https://claude-code-team-2.agentic-engineers.dev/admin/login` |
| Admin Email | `admin@acme.test` |
| Admin Password | `password` |
| Customer Email | `customer@acme.test` |
| Customer Password | `password` |

### Shop 5: Codex Sub-Agents v2

| Property | Value |
|----------|-------|
| Short Name | `codex-subagents-2` |
| Storefront URL | `https://codex-subagents-2.agentic-engineers.dev/` |
| Admin URL | `https://codex-subagents-2.agentic-engineers.dev/admin/login` |
| Admin Email | `owner@demo-store.test` |
| Admin Password | `password` |
| Customer Email | `customer@acme.test` |
| Customer Password | `password` |

---

## 2. Testing Methodology

### Tools

All tests are executed using **Playwright MCP** tools available to the coding agent:

| Tool | Purpose |
|------|---------|
| `browser_navigate` | Navigate to a URL |
| `browser_snapshot` | Capture accessibility snapshot (preferred over screenshot for assertions) |
| `browser_click` | Click an element by ref from snapshot |
| `browser_type` | Type text into an input field |
| `browser_fill_form` | Fill multiple form fields at once |
| `browser_select_option` | Select dropdown options |
| `browser_press_key` | Press keyboard keys (Tab, Enter, etc.) |
| `browser_resize` | Set viewport dimensions (for responsive tests) |
| `browser_console_messages` | Check for JS errors and console warnings |
| `browser_take_screenshot` | Visual screenshot for documentation |
| `browser_evaluate` | Run JS on the page (for DOM checks like heading hierarchy) |

### Scoring

Each test case receives one of:

| Score | Meaning |
|-------|---------|
| **PASS** | Test steps complete successfully, all expected results met |
| **FAIL** | Test cannot complete or expected results not met |
| **PARTIAL** | Some expected results met but not all |
| **N/A** | Feature not implemented (page 404, route missing, etc.) |

### Bug Recording

Every non-PASS result gets a description:
- What was expected vs. what happened
- The step at which the test diverged
- Any error messages observed

### Live Shop Testing Notes

- These are live deployed shops with persistent state
- Mutation tests (creates, edits, checkouts) will modify data permanently
- Use unique identifiers for test data (e.g., `E2E-SHOPNAME-` prefix) to avoid collisions
- Cart state may persist between tests via cookies/sessions
- Run smoke tests first to verify routes exist before deeper testing

---

## 3. Execution Strategy

### Team Structure

**5 agents in parallel**, one per shop:

| Agent | Assigned Shop | Results File |
|-------|---------------|--------------|
| Agent 1 | Claude Code Team Mode | `specs/results-claude-code-team.md` |
| Agent 2 | Claude Code Sub-Agents | `specs/results-claude-subagents.md` |
| Agent 3 | Codex Sub-Agents | `specs/results-codex-subagents.md` |
| Agent 4 | Claude Code Team v2 | `specs/results-claude-code-team-2.md` |
| Agent 5 | Codex Sub-Agents v2 | `specs/results-codex-subagents-2.md` |

### Execution Order

Run suites in dependency order:

1. **Phase 1 - Smoke:** Suite 1 (verifies all routes load)
2. **Phase 2 - Core:** Suite 2 (Admin Auth) + Suite 7 (Storefront Browsing)
3. **Phase 3 - Features:** Suites 3, 4, 5, 6, 10, 11, 15, 16, 17, 18 (Admin CRUD + storefront features)
4. **Phase 4 - Cart:** Suite 8
5. **Phase 5 - Checkout:** Suite 9
6. **Phase 6 - Cross-cutting:** Suites 12, 13, 14

### Agent Instructions

Each agent should:

1. Read this test plan for their assigned shop's URLs and credentials
2. Execute all 18 suites in the order above
3. For each test, record: ID, Result (PASS/FAIL/PARTIAL/N/A), Notes
4. Write results to their assigned results file (see template in Section 5)
5. Adapt steps to the actual UI (button labels, field names, navigation structure may differ)
6. Use `browser_snapshot` to inspect the page before interacting
7. For mutation tests, use shop-specific prefixes (e.g., `E2E-CCT-001` for Claude Code Team)

---

## 4. Test Cases (143 Total, 18 Suites)

### Suite 1: Smoke Tests (10 tests)

Purpose: Hit every major page, assert HTTP 200 and visible content.

#### S1-01: Loads the storefront home page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/`
2. `browser_snapshot` to check page content

**Expected:** Page displays "Acme Fashion" (or shop name). No JS errors.

#### S1-02: Loads a collection page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/collections/t-shirts`
2. `browser_snapshot`

**Expected:** Page displays "T-Shirts". No JS errors.

#### S1-03: Loads a product page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/products/classic-cotton-t-shirt`
2. `browser_snapshot`

**Expected:** Page displays "Classic Cotton T-Shirt" and "24.99". No JS errors.

#### S1-04: Loads the cart page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/cart`
2. `browser_snapshot`

**Expected:** Page displays cart-related content (e.g., "Cart", "Your Cart"). No JS errors.

#### S1-05: Loads the customer login page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/account/login`
2. `browser_snapshot`

**Expected:** Page displays "Log in" or login form. No JS errors.

#### S1-06: Loads the admin login page
**Steps:**
1. `browser_navigate` to `{ADMIN_URL}` (or `{STOREFRONT_URL}/admin/login`)
2. `browser_snapshot`

**Expected:** Page displays "Sign in" or admin login form. No JS errors.

#### S1-07: Loads the about page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/pages/about`
2. `browser_snapshot`

**Expected:** Page displays "About". No JS errors.

#### S1-08: Loads the search page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/search?q=shirt`
2. `browser_snapshot`

**Expected:** Page displays search results or "shirt". No JS errors.

#### S1-09: Loads all collections listing
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/collections`
2. `browser_snapshot`

**Expected:** Page displays "Collections" or a list of collections. No JS errors.

#### S1-10: Has no errors on critical pages (batch)
**Steps:**
1. Navigate to each critical page in sequence: `/`, `/collections/new-arrivals`, `/products/classic-cotton-t-shirt`, `/cart`, `/account/login`, `/admin/login`, `/pages/about`, `/search?q=shirt`
2. On each page: `browser_snapshot` + `browser_console_messages` (level: error)

**Expected:** No JS errors on any page. All pages load without 500 errors.

---

### Suite 2: Admin Authentication (10 tests)

Purpose: Admin login, logout, invalid credentials, session access control.

**Login helper pattern:** For tests requiring admin login, perform: navigate to admin login, fill email + password, click "Sign in", verify "Dashboard" visible.

#### S2-01: Can log in as admin
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/admin/login`
2. `browser_snapshot` to find email/password fields
3. `browser_type` or `browser_fill_form`: email = `{ADMIN_EMAIL}`, password = `{ADMIN_PASSWORD}`
4. `browser_click` the "Sign in" button
5. `browser_snapshot`

**Expected:** Page displays "Dashboard". No JS errors.

#### S2-02: Shows error for invalid credentials
**Steps:**
1. Navigate to admin login
2. Fill email = `{ADMIN_EMAIL}`, password = `wrongpassword`
3. Click "Sign in"
4. `browser_snapshot`

**Expected:** Error message visible (e.g., "Invalid credentials", "These credentials do not match"). No JS errors.

#### S2-03: Shows error for empty email
**Steps:**
1. Navigate to admin login
2. Fill only password = `password` (leave email empty)
3. Click "Sign in"
4. `browser_snapshot`

**Expected:** Validation error referencing "email". No JS errors.

#### S2-04: Shows error for empty password
**Steps:**
1. Navigate to admin login
2. Fill only email = `{ADMIN_EMAIL}` (leave password empty)
3. Click "Sign in"
4. `browser_snapshot`

**Expected:** Validation error referencing "password". No JS errors.

#### S2-05: Redirects unauthenticated users to login from dashboard
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/admin` (without logging in)
2. `browser_snapshot`

**Expected:** Page displays "Sign in" (redirected to login). No JS errors.

#### S2-06: Redirects unauthenticated users to login from products
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/admin/products` (without logging in)
2. `browser_snapshot`

**Expected:** Page displays "Sign in" (redirected to login). No JS errors.

#### S2-07: Can log out
**Steps:**
1. Log in as admin (full login flow)
2. Verify "Dashboard" visible
3. `browser_snapshot` to find profile/user menu
4. Click on profile name or dropdown trigger
5. Click "Logout" or "Sign out"
6. `browser_snapshot`

**Expected:** Page displays "Sign in" (returned to login). No JS errors.

#### S2-08: Can navigate through admin sidebar sections
**Steps:**
1. Log in as admin
2. Click "Products" in sidebar -> verify "Products" heading
3. Click "Orders" in sidebar -> verify "Orders" heading
4. Click "Customers" in sidebar -> verify "Customers" heading
5. Click "Discounts" in sidebar -> verify "Discounts" heading
6. Click "Settings" in sidebar -> verify "Settings" heading

**Expected:** Each section loads with correct heading. No JS errors.

#### S2-09: Can navigate to analytics from sidebar
**Steps:**
1. Log in as admin
2. Click "Analytics" in sidebar
3. `browser_snapshot`

**Expected:** Page displays "Analytics". No JS errors.

#### S2-10: Can navigate to themes from sidebar
**Steps:**
1. Log in as admin
2. Click "Themes" in sidebar
3. `browser_snapshot`

**Expected:** Page displays "Themes". No JS errors.

---

### Suite 3: Admin Product Management (7 tests)

Purpose: Product CRUD - listing, creating, editing, archiving, filtering.

#### S3-01: Shows the product list with seeded products
**Steps:**
1. Log in as admin
2. Click "Products" in sidebar
3. `browser_snapshot`

**Expected:** List displays "Classic Cotton T-Shirt" and "Premium Slim Fit Jeans". No JS errors.

#### S3-02: Can create a new product
**Steps:**
1. Log in as admin
2. Navigate to Products
3. Click "Add product" (or "Create product")
4. Fill: title = `E2E Test Product {SHOP_PREFIX}`, price = `29.99`, SKU = `E2E-{SHOP_PREFIX}-001`, quantity = `50`
5. Click "Save"
6. `browser_snapshot` - verify success message
7. Navigate back to product list
8. `browser_snapshot` - verify new product in list

**Expected:** "Product saved" message. Product appears in list. No JS errors.

#### S3-03: Can edit an existing product title
**Steps:**
1. Log in as admin
2. Navigate to Products
3. Click "Classic Cotton T-Shirt" to open edit form
4. Clear title, enter `Classic Cotton T-Shirt Updated`
5. Click "Save"
6. `browser_snapshot` - verify success message

**Expected:** "Product saved" message. Updated title visible. No JS errors.

#### S3-04: Can archive a product
**Steps:**
1. Log in as admin
2. Navigate to Products
3. Click "Add product"
4. Fill: title = `Product To Archive {SHOP_PREFIX}`, price = `19.99`, SKU = `E2E-ARCHIVE-{SHOP_PREFIX}`
5. Click "Save"
6. Navigate back to Products, click the new product
7. Change status to "Archived"
8. Click "Save"
9. Navigate to product list

**Expected:** Archived product not visible in default (Active) list view. No JS errors.

#### S3-05: Shows draft products only in admin, not storefront
**Steps:**
1. Log in as admin
2. Navigate to Products, look for a draft product (product with "Draft" badge)
3. `browser_snapshot` - verify draft product visible in admin
4. `browser_navigate` to `{STOREFRONT_URL}/collections/t-shirts`
5. `browser_snapshot` - verify draft product NOT visible
6. `browser_navigate` to `{STOREFRONT_URL}/search?q=draft`
7. `browser_snapshot` - verify draft product NOT in results

**Expected:** Draft product visible in admin only, not on storefront. No JS errors.

#### S3-06: Can search products in admin
**Steps:**
1. Log in as admin
2. Navigate to Products
3. Find search input, type "Cotton"
4. `browser_snapshot`

**Expected:** "Classic Cotton T-Shirt" visible in filtered results. No JS errors.

#### S3-07: Can filter products by status in admin
**Steps:**
1. Log in as admin
2. Navigate to Products
3. Click "Draft" status filter/tab
4. `browser_snapshot` - verify only draft products
5. Click "Active" status filter/tab
6. `browser_snapshot` - verify "Classic Cotton T-Shirt" visible

**Expected:** Status filters work correctly. No JS errors.

---

### Suite 4: Admin Order Management (11 tests)

Purpose: Order listing, filtering, detail view, fulfillment, refund.

#### S4-01: Shows the order list with seeded orders
**Steps:**
1. Log in as admin
2. Click "Orders" in sidebar
3. `browser_snapshot`

**Expected:** List displays "#1001". No JS errors.

#### S4-02: Can filter orders by status
**Steps:**
1. Log in as admin, navigate to Orders
2. Click "Paid" filter/tab -> `browser_snapshot` -> verify "#1001"
3. Click "All" tab to reset

**Expected:** Filters work. "#1001" visible when filtering by "Paid". No JS errors.

#### S4-03: Shows order detail with line items and totals
**Steps:**
1. Log in as admin, navigate to Orders
2. Click "#1001" to open detail
3. `browser_snapshot`

**Expected:** Displays "#1001", "Paid", "Unfulfilled", line item(s), Subtotal/Shipping/Tax/Total. No JS errors.

#### S4-04: Shows order timeline events
**Steps:**
1. Log in as admin, navigate to Orders
2. Click "#1001"
3. `browser_snapshot` - look for "Timeline" section

**Expected:** Timeline section visible with at least creation event. No JS errors.

#### S4-05: Can create a fulfillment
**Steps:**
1. Log in as admin, navigate to Order #1001 detail
2. Click "Create fulfillment" (or "Fulfill")
3. Fill: tracking company = `DHL`, tracking number = `DHL123456789`
4. Click "Fulfill items" (or "Save")
5. `browser_snapshot`

**Expected:** "Fulfillment created" message. DHL + tracking number visible. No JS errors.

#### S4-06: Can process a refund
**Steps:**
1. Log in as admin, navigate to Order #1001 detail
2. Click "Refund"
3. Fill: amount = `10.00`, reason = `Customer requested partial refund`
4. Click "Process refund"
5. `browser_snapshot`

**Expected:** "Refund processed" message. "Partially refunded" status visible. No JS errors.

#### S4-07: Shows customer information in order detail
**Steps:**
1. Log in as admin, navigate to Order #1001 detail
2. `browser_snapshot`

**Expected:** "customer@acme.test" visible in customer info section. No JS errors.

#### S4-08: Can confirm bank transfer payment
**Steps:**
1. Log in as admin, navigate to Orders
2. Click "#1005" to open detail
3. `browser_snapshot` - verify "Pending" status and "Confirm payment" button
4. Click "Confirm payment"
5. `browser_snapshot`

**Expected:** "Payment confirmed" message. Status changes to "Paid". "Confirm payment" button gone. No JS errors.

#### S4-09: Shows fulfillment guard for unpaid order
**Steps:**
1. Log in as admin, navigate to Order #1005 detail (before payment confirmation)
2. `browser_snapshot`

**Expected:** Warning about payment required. "Create fulfillment" disabled or hidden. No JS errors.

#### S4-10: Can mark fulfillment as shipped
**Steps:**
1. Log in as admin, navigate to Order #1001 detail
2. Ensure fulfillment exists (may need to create one first)
3. Click "Mark as shipped"
4. `browser_snapshot`

**Expected:** Fulfillment status changes to "Shipped". No JS errors.

#### S4-11: Can mark fulfillment as delivered
**Steps:**
1. Log in as admin, navigate to Order #1001 detail
2. Click "Mark as delivered" on shipped fulfillment
3. `browser_snapshot`

**Expected:** Fulfillment status changes to "Delivered". Order fulfillment status = "Fulfilled". No JS errors.

---

### Suite 5: Admin Discount Management (6 tests)

Purpose: Discount code listing, creation (all types), editing, status display.

#### S5-01: Shows seeded discount codes
**Steps:**
1. Log in as admin
2. Click "Discounts" in sidebar
3. `browser_snapshot`

**Expected:** Displays "WELCOME10", "FLAT5", "FREESHIP". No JS errors.

#### S5-02: Can create a new percentage discount code
**Steps:**
1. Log in as admin, navigate to Discounts
2. Click "Create discount"
3. Fill: code = `E2ETEST25-{SHOP_PREFIX}`, type = Percentage, value = `25`, starts_at = `2026-01-01`, ends_at = `2026-12-31`
4. Click "Save"
5. `browser_snapshot` - verify success
6. Navigate to discount list - verify code in list

**Expected:** "Discount saved". Code appears in list. No JS errors.

#### S5-03: Can create a fixed amount discount code
**Steps:**
1. Log in as admin, navigate to Discounts
2. Click "Create discount"
3. Fill: code = `E2EFLAT10-{SHOP_PREFIX}`, type = Fixed amount, value = `10.00`, starts_at = `2026-01-01`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Discount saved". No JS errors.

#### S5-04: Can create a free shipping discount code
**Steps:**
1. Log in as admin, navigate to Discounts
2. Click "Create discount"
3. Fill: code = `E2EFREESHIP-{SHOP_PREFIX}`, type = Free shipping, starts_at = `2026-01-01`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Discount saved". No JS errors.

#### S5-05: Can edit a discount
**Steps:**
1. Log in as admin, navigate to Discounts
2. Click "WELCOME10"
3. Change value to `15`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Discount saved". No JS errors.

#### S5-06: Shows discount status indicators
**Steps:**
1. Log in as admin, navigate to Discounts
2. `browser_snapshot`

**Expected:** "Active" badge for active discounts. "Expired" badge for EXPIRED20. No JS errors.

---

### Suite 6: Admin Settings (7 tests)

Purpose: Store settings, shipping, taxes, domains.

#### S6-01: Can view store settings
**Steps:**
1. Log in as admin
2. Click "Settings" in sidebar
3. `browser_snapshot`

**Expected:** Displays "Store Settings" (or similar) and "Acme Fashion". No JS errors.

#### S6-02: Can update store name
**Steps:**
1. Log in as admin, navigate to Settings
2. Change store name to `Acme Fashion Updated`
3. Click "Save"
4. `browser_snapshot` - verify success message
5. Reload Settings page
6. `browser_snapshot` - verify updated name persisted

**Expected:** "Settings saved". Name persisted after reload. No JS errors.

#### S6-03: Can view shipping zones
**Steps:**
1. Log in as admin, navigate to Settings
2. Click "Shipping" tab (or navigate to shipping settings)
3. `browser_snapshot`

**Expected:** Displays "Domestic", "Standard Shipping", "4.99". No JS errors.

#### S6-04: Can add a new shipping rate to existing zone
**Steps:**
1. Log in as admin, navigate to Shipping settings
2. Click "Add rate" in the Domestic zone
3. Fill: name = `Overnight Shipping`, price = `14.99`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Shipping rate saved". "Overnight Shipping" and "14.99" visible. No JS errors.

#### S6-05: Can view tax settings
**Steps:**
1. Log in as admin, navigate to Settings
2. Click "Taxes" tab
3. `browser_snapshot`

**Expected:** Displays "Tax Settings" or tax configuration. No JS errors.

#### S6-06: Can update tax inclusion setting
**Steps:**
1. Log in as admin, navigate to Tax settings
2. Toggle "Prices include tax"
3. Click "Save"
4. `browser_snapshot`

**Expected:** "Tax settings saved". No JS errors.

#### S6-07: Can view domain settings
**Steps:**
1. Log in as admin, navigate to Settings
2. Click "Domains" tab
3. `browser_snapshot`

**Expected:** Displays domain name (e.g., "acme-fashion.test" or the shop's domain). No JS errors.

---

### Suite 7: Storefront Browsing (15 tests)

Purpose: Home page, collections, product detail, variants, search, pages, content visibility.

#### S7-01: Shows featured products on home page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/`
2. `browser_snapshot`

**Expected:** Displays store name and "Classic Cotton T-Shirt" with "24.99". No JS errors.

#### S7-02: Shows collection with product grid
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/collections/t-shirts`
2. `browser_snapshot`

**Expected:** Displays "T-Shirts" and "Classic Cotton T-Shirt". No JS errors.

#### S7-03: Can navigate from collection to product
**Steps:**
1. Navigate to `/collections/t-shirts`
2. `browser_snapshot`, find "Classic Cotton T-Shirt" link
3. `browser_click` on the product
4. `browser_snapshot`

**Expected:** Product page shows "Classic Cotton T-Shirt", "24.99", "Add to cart". No JS errors.

#### S7-04: Shows product detail with variant options
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/products/classic-cotton-t-shirt`
2. `browser_snapshot`

**Expected:** Displays "Classic Cotton T-Shirt", "24.99", "Size", "Color". No JS errors.

#### S7-05: Shows size and color option values
**Steps:**
1. Navigate to product page for Classic Cotton T-Shirt
2. `browser_snapshot`

**Expected:** Size options (S, M, L, XL) and color options (Black, White, Navy) visible. No JS errors.

#### S7-06: Updates price when variant changes (compare-at pricing)
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/products/premium-slim-fit-jeans`
2. `browser_snapshot`
3. Select a sale variant if options are present
4. `browser_snapshot`

**Expected:** Displays "Premium Slim Fit Jeans". Sale price displayed with compare-at (original) price shown with strikethrough. No JS errors.

#### S7-07: Shows search results for valid query
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/search?q=cotton`
2. `browser_snapshot`

**Expected:** "Classic Cotton T-Shirt" in search results. No JS errors.

#### S7-08: Shows no results message for invalid query
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/search?q=zznonexistentproductzz`
2. `browser_snapshot`

**Expected:** "No results" (or similar empty state message). No JS errors.

#### S7-09: Does not show draft products on storefront collections
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/collections`
2. `browser_snapshot`

**Expected:** No draft products visible in any collection listing. No JS errors.

#### S7-10: Does not show draft products in search results
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/search?q=draft`
2. `browser_snapshot`

**Expected:** No draft products in results. No JS errors.

#### S7-11: Shows out of stock messaging for deny-policy product
**Steps:**
1. Find the deny-policy out-of-stock product (handle may vary; try `/products/limited-edition-sneakers` or discover from collection)
2. `browser_snapshot`

**Expected:** "Sold out" visible. "Add to cart" button disabled or hidden. No JS errors.

#### S7-12: Shows backorder messaging for continue-policy product
**Steps:**
1. Find the continue-policy out-of-stock product (handle may vary; try `/products/backorder-denim-jacket` or discover from collection)
2. `browser_snapshot`

**Expected:** "Available on backorder" (or similar). "Add to cart" enabled. No JS errors.

#### S7-13: Shows new arrivals collection
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/collections/new-arrivals`
2. `browser_snapshot`

**Expected:** Displays "New Arrivals". No JS errors.

#### S7-14: Shows static about page
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/pages/about`
2. `browser_snapshot`

**Expected:** Displays "About". No JS errors.

#### S7-15: Navigates between pages using the main navigation
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/`
2. `browser_snapshot` to find navigation links
3. Click "T-Shirts" (or equivalent collection link) in navigation
4. `browser_snapshot`

**Expected:** Navigates to T-Shirts collection. No JS errors.

---

### Suite 8: Cart Flow (12 tests)

Purpose: Add to cart, update quantity, remove, discount codes, totals.

#### S8-01: Can add product to cart
**Steps:**
1. Navigate to `/products/classic-cotton-t-shirt`
2. `browser_snapshot`, select size "M" and color "Black"
3. Click "Add to cart"
4. `browser_snapshot`

**Expected:** Product added confirmation. "Classic Cotton T-Shirt" and "24.99" visible. No JS errors.

#### S8-02: Can view cart with added item
**Steps:**
1. Add Classic Cotton T-Shirt to cart (M, Black)
2. `browser_navigate` to `{STOREFRONT_URL}/cart`
3. `browser_snapshot`

**Expected:** Cart displays "Classic Cotton T-Shirt" and "24.99". No JS errors.

#### S8-03: Can update quantity in cart
**Steps:**
1. Add product to cart, navigate to cart
2. `browser_snapshot`, click "+" to increment quantity
3. `browser_snapshot`

**Expected:** Quantity = 2. Line total shows "49.98". No JS errors.

#### S8-04: Can remove item from cart
**Steps:**
1. Add product to cart, navigate to cart
2. Click "Remove" on the item
3. `browser_snapshot`

**Expected:** "Your cart is empty" (or equivalent). No JS errors.

#### S8-05: Can add multiple different products
**Steps:**
1. Add Classic Cotton T-Shirt (M, Black)
2. Navigate to `/products/premium-slim-fit-jeans`, add to cart
3. Navigate to cart
4. `browser_snapshot`

**Expected:** Both products visible in cart. No JS errors.

#### S8-06: Can apply valid discount code WELCOME10
**Steps:**
1. Add Classic Cotton T-Shirt (M, Black) to cart
2. Navigate to cart
3. Enter discount code `WELCOME10`, click "Apply"
4. `browser_snapshot`

**Expected:** "WELCOME10" label visible. Discount line in totals (~10% off 24.99). No JS errors.

#### S8-07: Shows error for invalid discount code
**Steps:**
1. Add product to cart, navigate to cart
2. Enter `INVALID`, click "Apply"
3. `browser_snapshot`

**Expected:** "Invalid discount code" error. No JS errors.

#### S8-08: Shows error for expired discount code
**Steps:**
1. Add product to cart, navigate to cart
2. Enter `EXPIRED20`, click "Apply"
3. `browser_snapshot`

**Expected:** Error containing "expired". No JS errors.

#### S8-09: Shows error for maxed out discount code
**Steps:**
1. Add product to cart, navigate to cart
2. Enter `MAXED`, click "Apply"
3. `browser_snapshot`

**Expected:** Error containing "usage limit". No JS errors.

#### S8-10: Can apply free shipping discount
**Steps:**
1. Add product to cart, navigate to cart
2. Enter `FREESHIP`, click "Apply"
3. `browser_snapshot`

**Expected:** "FREESHIP" label. Free shipping indicator. No JS errors.

#### S8-11: Can apply FLAT5 discount for fixed amount off
**Steps:**
1. Add product to cart, navigate to cart
2. Enter `FLAT5`, click "Apply"
3. `browser_snapshot`

**Expected:** "FLAT5" label. "5.00" discount in totals. No JS errors.

#### S8-12: Shows subtotal and total in cart
**Steps:**
1. Add product to cart, navigate to cart
2. `browser_snapshot`

**Expected:** "Subtotal" label and "24.99" visible. No JS errors.

---

### Suite 9: Checkout Flow (13 tests)

Purpose: Full multi-step checkout: contact, address, shipping, payment, confirmation.

**Checkout helper:** Add Classic Cotton T-Shirt (M, Black) to cart, navigate to cart, click "Checkout".

#### S9-01: Completes full checkout with credit card
**Steps:**
1. Add product to cart, proceed to checkout
2. Enter email: `test-buyer-{SHOP_PREFIX}@example.com`, click "Continue"
3. Fill address: first name = `Test`, last name = `Buyer`, address = `Teststrasse 1`, city = `Berlin`, postal code = `10115`, country = `DE`
4. Click "Continue"
5. Verify "Standard Shipping" and "4.99" visible, select it, click "Continue"
6. Verify credit card form, fill: card number = `4242 4242 4242 4242`, name = `Test Buyer`, expiry = `12/28`, CVC = `123`
7. Click "Pay now"
8. `browser_snapshot`

**Expected:** Confirmation page with "Thank you" and order number (prefixed "#"). No JS errors.

#### S9-02: Shows shipping methods based on German address
**Steps:**
1. Add product to cart, proceed to checkout
2. Enter email, continue
3. Fill DE address (Berlin, 10115), continue
4. `browser_snapshot`

**Expected:** "Standard Shipping" at "4.99" (Domestic zone). No JS errors.

#### S9-03: Shows international shipping methods for non-DE address
**Steps:**
1. Add product to cart, proceed to checkout
2. Enter email, continue
3. Fill US address (New York, 10001), continue
4. `browser_snapshot`

**Expected:** International shipping rates shown (not Domestic rates). No JS errors.

#### S9-04: Applies discount during checkout
**Steps:**
1. Add product to cart
2. Apply `FLAT5` discount in cart
3. Proceed through checkout to payment step
4. `browser_snapshot`

**Expected:** "FLAT5" in checkout totals. "5.00" discount. Total = 24.98 (24.99 - 5.00 + 4.99). No JS errors.

#### S9-05: Validates required contact email
**Steps:**
1. Add product to cart, proceed to checkout
2. Click "Continue" without entering email
3. `browser_snapshot`

**Expected:** Validation error for email. No JS errors.

#### S9-06: Validates required shipping address fields
**Steps:**
1. Proceed to address step
2. Click "Continue" without filling fields
3. `browser_snapshot`

**Expected:** Validation errors for required fields (first_name, last_name, address, city, postal_code, country). No JS errors.

#### S9-07: Validates invalid postal code format
**Steps:**
1. Proceed to address step
2. Fill address with postal_code = `INVALID`, country = `DE`
3. Click "Continue"
4. `browser_snapshot`

**Expected:** Validation error for postal code format. No JS errors.

#### S9-08: Prevents checkout with empty cart
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/cart` (without adding items)
2. `browser_snapshot`

**Expected:** "Your cart is empty". "Checkout" button disabled or not shown. No JS errors.

#### S9-09: Completes checkout with PayPal
**Steps:**
1. Add product to cart, proceed through checkout to payment step
2. Select "PayPal" as payment method
3. Click "Pay with PayPal"
4. `browser_snapshot`

**Expected:** Confirmation with "Thank you" and "PayPal" in payment section. No JS errors.

#### S9-10: Completes checkout with bank transfer
**Steps:**
1. Add product to cart, proceed through checkout to payment step
2. Select "Bank Transfer"
3. Click "Place order"
4. `browser_snapshot`

**Expected:** Confirmation with "Thank you" and bank transfer instructions (IBAN, BIC, reference). No JS errors.

#### S9-11: Shows error for declined credit card
**Steps:**
1. Proceed to payment step
2. Enter card number `4000 0000 0000 0002` (magic decline)
3. Fill other card fields, click "Pay now"
4. `browser_snapshot`

**Expected:** Error containing "declined". Remains on checkout (no confirmation). No JS errors.

#### S9-12: Shows error for insufficient funds
**Steps:**
1. Proceed to payment step
2. Enter card number `4000 0000 0000 9995` (magic insufficient funds)
3. Fill other card fields, click "Pay now"
4. `browser_snapshot`

**Expected:** Error containing "insufficient". Remains on checkout. No JS errors.

#### S9-13: Switches between payment method forms
**Steps:**
1. Proceed to payment step
2. Verify credit card form visible
3. Click "PayPal" - verify card form hidden, "Pay with PayPal" visible
4. Click "Bank Transfer" - verify "Place order" button and bank info visible

**Expected:** Payment form dynamically switches. No JS errors.

---

### Suite 10: Customer Account (12 tests)

Purpose: Registration, login, order history, addresses, logout.

#### S10-01: Can register a new customer
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/account/register`
2. Fill: name = `New Customer E2E`, email = `e2e-{SHOP_PREFIX}@example.com`, password = `password123`, confirm = `password123`
3. Click "Create account" (or "Register")
4. `browser_snapshot`

**Expected:** Displays "My Account". No JS errors.

#### S10-02: Shows validation errors for duplicate email
**Steps:**
1. Navigate to register page
2. Fill with email = `customer@acme.test` (existing), click "Create account"
3. `browser_snapshot`

**Expected:** Error "already been taken". No JS errors.

#### S10-03: Shows validation errors for mismatched passwords
**Steps:**
1. Navigate to register page
2. Fill with password = `password123`, confirm = `different456`
3. Click "Create account"
4. `browser_snapshot`

**Expected:** Validation error for password mismatch. No JS errors.

#### S10-04: Can log in as existing customer
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/account/login`
2. Fill: email = `{CUSTOMER_EMAIL}`, password = `{CUSTOMER_PASSWORD}`
3. Click "Log in"
4. `browser_snapshot`

**Expected:** Displays "My Account" and "John Doe". No JS errors.

#### S10-05: Shows error for invalid customer credentials
**Steps:**
1. Navigate to customer login
2. Fill: email = `{CUSTOMER_EMAIL}`, password = `wrongpassword`
3. Click "Log in"
4. `browser_snapshot`

**Expected:** Error "Invalid credentials" (or similar). No JS errors.

#### S10-06: Redirects unauthenticated customers to login
**Steps:**
1. `browser_navigate` to `{STOREFRONT_URL}/account` (not logged in)
2. `browser_snapshot`

**Expected:** Redirected to login page. No JS errors.

#### S10-07: Shows order history for logged-in customer
**Steps:**
1. Log in as customer
2. Navigate to orders (click "Orders" or go to `/account/orders`)
3. `browser_snapshot`

**Expected:** Displays "#1001", "#1002", "#1004". No JS errors.

#### S10-08: Shows order detail for customer order
**Steps:**
1. Log in as customer, navigate to orders
2. Click "#1001"
3. `browser_snapshot`

**Expected:** Displays "#1001", "Subtotal", "Total". No JS errors.

#### S10-09: Can view addresses
**Steps:**
1. Log in as customer
2. Navigate to addresses (click "Addresses" or go to `/account/addresses`)
3. `browser_snapshot`

**Expected:** Address list displayed. No JS errors.

#### S10-10: Can add a new address
**Steps:**
1. Log in as customer, navigate to addresses
2. Click "Add address"
3. Fill: first_name = `John`, last_name = `Doe`, address = `New Street 42`, city = `Hamburg`, postal_code = `20095`, country = `DE`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Address saved". "New Street 42" and "Hamburg" visible. No JS errors.

#### S10-11: Can edit an existing address
**Steps:**
1. Log in as customer, navigate to addresses
2. Click "Edit" on first address
3. Change city to `Frankfurt`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Address saved". "Frankfurt" visible. No JS errors.

#### S10-12: Can log out
**Steps:**
1. Log in as customer
2. Verify "My Account" displayed
3. Click "Logout"
4. `browser_snapshot`

**Expected:** Redirected to login or home page. No JS errors.

---

### Suite 11: Inventory Enforcement (4 tests)

Purpose: Verify inventory policies (deny vs. continue) are enforced.

#### S11-01: Blocks add-to-cart for out-of-stock deny-policy product
**Steps:**
1. Navigate to deny-policy product page (Product #17, handle may vary)
2. `browser_snapshot`

**Expected:** "Sold out". Add-to-cart disabled/hidden. No JS errors.

#### S11-02: Allows add-to-cart for out-of-stock continue-policy product
**Steps:**
1. Navigate to continue-policy product page (Product #18, handle may vary)
2. `browser_snapshot` - verify "Available on backorder"
3. Click "Add to cart"
4. Navigate to cart
5. `browser_snapshot`

**Expected:** "Available on backorder" on PDP. Product appears in cart. No JS errors.

#### S11-03: Shows correct stock status for in-stock product
**Steps:**
1. Navigate to `/products/classic-cotton-t-shirt`
2. `browser_snapshot`

**Expected:** "Add to cart" enabled. "Sold out" NOT displayed. No JS errors.

#### S11-04: Prevents adding more than available stock
**Steps:**
1. Add Classic Cotton T-Shirt (M, Black) to cart
2. Navigate to cart
3. Repeatedly increment quantity beyond expected stock limit
4. `browser_snapshot`

**Expected:** Quantity capped or error shown when exceeding stock. No JS errors.

---

### Suite 12: Tenant Isolation (5 tests)

Purpose: Multi-store data isolation verification.

#### S12-01: Store only shows its own products
**Steps:**
1. Navigate to `{STOREFRONT_URL}/`
2. `browser_snapshot`

**Expected:** Displays store name. Shows "Classic Cotton T-Shirt". No products from other stores. No JS errors.

#### S12-02: Store collections only contain store products
**Steps:**
1. Navigate to `{STOREFRONT_URL}/collections/t-shirts`
2. `browser_snapshot`

**Expected:** Only this store's products. No JS errors.

#### S12-03: Admin cannot access other store data
**Steps:**
1. Log in as admin
2. Check Products list - only this store's products
3. Check Orders list - only this store's orders
4. `browser_snapshot` each

**Expected:** No cross-store data. No JS errors.

#### S12-04: Search only returns current store products
**Steps:**
1. Navigate to `{STOREFRONT_URL}/search?q=product`
2. `browser_snapshot`

**Expected:** Only this store's products in results. No JS errors.

#### S12-05: Customer accounts scoped to store
**Steps:**
1. Log in as customer
2. Navigate to orders
3. `browser_snapshot`

**Expected:** Only this store's orders visible (#1001, #1002, #1004). No JS errors.

---

### Suite 13: Responsive / Mobile (8 tests)

Purpose: Mobile (375x812) and tablet (768x1024) rendering.

#### S13-01: Storefront home works on mobile viewport
**Steps:**
1. `browser_resize` to 375x812
2. Navigate to `{STOREFRONT_URL}/`
3. `browser_snapshot`

**Expected:** Store name visible. Mobile menu/hamburger visible. No horizontal scroll. No JS errors.

#### S13-02: Product page stacks layout on mobile
**Steps:**
1. `browser_resize` to 375x812
2. Navigate to product page
3. `browser_snapshot`

**Expected:** "Classic Cotton T-Shirt", "24.99", "Add to cart" visible. Stacked layout. No JS errors.

#### S13-03: Can add to cart on mobile
**Steps:**
1. `browser_resize` to 375x812
2. Navigate to product, select variant, click "Add to cart"
3. `browser_snapshot`

**Expected:** Product added successfully. No JS errors.

#### S13-04: Cart page works on mobile
**Steps:**
1. `browser_resize` to 375x812
2. Add product, navigate to cart
3. `browser_snapshot`

**Expected:** Product visible. "Checkout" button accessible. No JS errors.

#### S13-05: Checkout flow works on mobile
**Steps:**
1. `browser_resize` to 375x812
2. Complete checkout through shipping step
3. `browser_snapshot`

**Expected:** "Standard Shipping" visible. All steps accessible without horizontal scrolling. No JS errors.

#### S13-06: Admin login works on tablet viewport
**Steps:**
1. `browser_resize` to 768x1024
2. Navigate to admin login, log in
3. `browser_snapshot`

**Expected:** "Dashboard" visible. No JS errors.

#### S13-07: Admin sidebar navigation works on tablet
**Steps:**
1. `browser_resize` to 768x1024
2. Log in as admin
3. Click "Products" -> verify heading
4. Click "Orders" -> verify heading
5. `browser_snapshot`

**Expected:** Sections load correctly. No JS errors.

#### S13-08: Collection page works on mobile with filters
**Steps:**
1. `browser_resize` to 375x812
2. Navigate to `/collections/t-shirts`
3. `browser_snapshot`

**Expected:** "T-Shirts" visible. Products visible. Filters accessible. No JS errors.

---

### Suite 14: Accessibility (11 tests)

Purpose: No JS errors, heading hierarchy, form labels, ARIA, keyboard navigation.

#### S14-01: Home page has no JS errors or console warnings
**Steps:**
1. Navigate to home page
2. `browser_console_messages` (level: warning)

**Expected:** No JS errors or warnings.

#### S14-02: Home page has proper heading hierarchy
**Steps:**
1. Navigate to home page
2. `browser_evaluate` to check: exactly one h1, headings in logical order

**Expected:** One h1 with store name. Logical heading order.

#### S14-03: Product page has proper ARIA labels for variant selector
**Steps:**
1. Navigate to product page
2. `browser_snapshot`

**Expected:** "Size" and "Color" labels visible. "Add to cart" properly labeled. No JS errors.

#### S14-04: Product page images have alt text
**Steps:**
1. Navigate to product page
2. `browser_evaluate` to check all img elements have non-empty alt

**Expected:** All product images have meaningful alt text.

#### S14-05: Customer login form has accessible labels
**Steps:**
1. Navigate to `/account/login`
2. `browser_snapshot`

**Expected:** "Email" and "Password" labels visible and associated with inputs.

#### S14-06: Admin login form has accessible labels
**Steps:**
1. Navigate to `/admin/login`
2. `browser_snapshot`

**Expected:** "Email" and "Password" labels visible and associated with inputs.

#### S14-07: Checkout form has accessible labels
**Steps:**
1. Add product to cart, proceed to checkout
2. `browser_snapshot`

**Expected:** "Email" label visible. Form fields have associated labels.

#### S14-08: Checkout validation errors are accessible
**Steps:**
1. Proceed to checkout, click "Continue" without filling fields
2. `browser_snapshot`

**Expected:** Validation errors visible and linked to respective fields.

#### S14-09: Can navigate storefront with keyboard only
**Steps:**
1. Navigate to home page
2. `browser_press_key` Tab repeatedly
3. `browser_press_key` Enter on a focused link
4. `browser_snapshot`

**Expected:** Focus indicators visible. Navigation works via keyboard.

#### S14-10: Cart page has no console errors or warnings
**Steps:**
1. Navigate to `/cart`
2. `browser_console_messages` (level: warning)

**Expected:** No JS errors or warnings.

#### S14-11: Search page has proper form labels
**Steps:**
1. Navigate to `/search?q=shirt`
2. `browser_snapshot`

**Expected:** Search input has label or aria-label. No JS errors.

---

### Suite 15: Admin Collections Management (3 tests)

#### S15-01: Shows the collection list with seeded collections
**Steps:**
1. Log in as admin
2. Navigate to `/admin/collections`
3. `browser_snapshot`

**Expected:** Displays "T-Shirts" and "New Arrivals". No JS errors.

#### S15-02: Can create a new collection
**Steps:**
1. Log in as admin, navigate to collections
2. Click "Create collection"
3. Fill: title = `E2E Test Collection`, description = `Created by E2E test.`
4. Click "Save"
5. Navigate to collection list

**Expected:** "Collection saved". "E2E Test Collection" in list. No JS errors.

#### S15-03: Can edit a collection
**Steps:**
1. Log in as admin, navigate to collections
2. Click "T-Shirts"
3. Change description to `Updated description.`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Collection saved". No JS errors.

---

### Suite 16: Admin Customer Management (3 tests)

#### S16-01: Shows the customer list
**Steps:**
1. Log in as admin
2. Click "Customers" in sidebar
3. `browser_snapshot`

**Expected:** Displays "customer@acme.test" and "John Doe". No JS errors.

#### S16-02: Shows customer detail with order history
**Steps:**
1. Log in as admin, navigate to Customers
2. Click "John Doe"
3. `browser_snapshot`

**Expected:** Displays "John Doe", "customer@acme.test", "#1001". No JS errors.

#### S16-03: Shows customer addresses
**Steps:**
1. Log in as admin, navigate to Customers, click "John Doe"
2. `browser_snapshot`

**Expected:** "Addresses" section visible. No JS errors.

---

### Suite 17: Admin Pages Management (3 tests)

#### S17-01: Shows the pages list
**Steps:**
1. Log in as admin
2. Navigate to `/admin/pages`
3. `browser_snapshot`

**Expected:** Displays "About". No JS errors.

#### S17-02: Can create a new page
**Steps:**
1. Log in as admin, navigate to pages
2. Click "Create page"
3. Fill: title = `FAQ`, body = `Frequently asked questions.`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Page saved". No JS errors.

#### S17-03: Can edit an existing page
**Steps:**
1. Log in as admin, navigate to pages
2. Click "About"
3. Update body to `Updated about content.`
4. Click "Save"
5. `browser_snapshot`

**Expected:** "Page saved". No JS errors.

---

### Suite 18: Admin Analytics Dashboard (3 tests)

#### S18-01: Shows the analytics dashboard
**Steps:**
1. Log in as admin
2. Click "Analytics" in sidebar
3. `browser_snapshot`

**Expected:** Displays "Analytics". No JS errors.

#### S18-02: Shows sales data
**Steps:**
1. Log in as admin, navigate to Analytics
2. `browser_snapshot`

**Expected:** Displays "Orders" and "Revenue" KPI labels. No JS errors.

#### S18-03: Shows conversion funnel data
**Steps:**
1. Log in as admin, navigate to Analytics
2. `browser_snapshot`

**Expected:** Displays "Visits" label (part of funnel). No JS errors.

---

## 5. Results Template

Each agent writes results to `specs/results-{shop-name}.md` using this format:

### Per-Shop Results Header

```markdown
# Test Results: {Shop Name}

- **Shop:** {Shop Name}
- **URL:** {Storefront URL}
- **Tested by:** Agent {N}
- **Date:** 2026-02-14

## Summary

| Metric | Value |
|--------|-------|
| Total Tests | 143 |
| PASS | ? |
| FAIL | ? |
| PARTIAL | ? |
| N/A | ? |
| Pass Rate | ?% |
```

### Per-Suite Results Table

```markdown
## Suite {N}: {Suite Name}

| ID | Test | Result | Notes |
|----|------|--------|-------|
| S{N}-01 | {Test name} | PASS/FAIL/PARTIAL/N/A | {Description of any issues} |
| S{N}-02 | ... | ... | ... |
```

### Final Comparison Matrix

After all 5 agents complete, compile into `specs/comparison.md`:

```markdown
# Comparison Matrix

## Suite Pass Rates

| Suite | CCT | CSA | COD | CCT2 | COD2 |
|-------|-----|-----|-----|------|------|
| S1: Smoke (10) | ?/10 | ?/10 | ?/10 | ?/10 | ?/10 |
| S2: Admin Auth (10) | ?/10 | ?/10 | ?/10 | ?/10 | ?/10 |
| S3: Products (7) | ?/7 | ?/7 | ?/7 | ?/7 | ?/7 |
| S4: Orders (11) | ?/11 | ?/11 | ?/11 | ?/11 | ?/11 |
| S5: Discounts (6) | ?/6 | ?/6 | ?/6 | ?/6 | ?/6 |
| S6: Settings (7) | ?/7 | ?/7 | ?/7 | ?/7 | ?/7 |
| S7: Browsing (15) | ?/15 | ?/15 | ?/15 | ?/15 | ?/15 |
| S8: Cart (12) | ?/12 | ?/12 | ?/12 | ?/12 | ?/12 |
| S9: Checkout (13) | ?/13 | ?/13 | ?/13 | ?/13 | ?/13 |
| S10: Customer (12) | ?/12 | ?/12 | ?/12 | ?/12 | ?/12 |
| S11: Inventory (4) | ?/4 | ?/4 | ?/4 | ?/4 | ?/4 |
| S12: Tenant (5) | ?/5 | ?/5 | ?/5 | ?/5 | ?/5 |
| S13: Responsive (8) | ?/8 | ?/8 | ?/8 | ?/8 | ?/8 |
| S14: Accessibility (11) | ?/11 | ?/11 | ?/11 | ?/11 | ?/11 |
| S15: Collections (3) | ?/3 | ?/3 | ?/3 | ?/3 | ?/3 |
| S16: Customers (3) | ?/3 | ?/3 | ?/3 | ?/3 | ?/3 |
| S17: Pages (3) | ?/3 | ?/3 | ?/3 | ?/3 | ?/3 |
| S18: Analytics (3) | ?/3 | ?/3 | ?/3 | ?/3 | ?/3 |
| **Total (143)** | **?** | **?** | **?** | **?** | **?** |

Legend: CCT = Claude Code Team, CSA = Claude Sub-Agents, COD = Codex Sub-Agents, CCT2 = Claude Code Team v2, COD2 = Codex Sub-Agents v2
```

### Bug Summary

```markdown
## Bug Summary

| Shop | Critical Bugs | Major Bugs | Minor Bugs | Total |
|------|--------------|------------|------------|-------|
| CCT | ? | ? | ? | ? |
| CSA | ? | ? | ? | ? |
| COD | ? | ? | ? | ? |
| CCT2 | ? | ? | ? | ? |
| COD2 | ? | ? | ? | ? |
```

---

## Test Count Verification

| Suite | Tests |
|-------|:-----:|
| S1: Smoke Tests | 10 |
| S2: Admin Authentication | 10 |
| S3: Admin Product Management | 7 |
| S4: Admin Order Management | 11 |
| S5: Admin Discount Management | 6 |
| S6: Admin Settings | 7 |
| S7: Storefront Browsing | 15 |
| S8: Cart Flow | 12 |
| S9: Checkout Flow | 13 |
| S10: Customer Account | 12 |
| S11: Inventory Enforcement | 4 |
| S12: Tenant Isolation | 5 |
| S13: Responsive / Mobile | 8 |
| S14: Accessibility | 11 |
| S15: Admin Collections | 3 |
| S16: Admin Customers | 3 |
| S17: Admin Pages | 3 |
| S18: Admin Analytics | 3 |
| **Total** | **143** |
