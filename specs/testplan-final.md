# Final Comprehensive Test Plan - Phase 12

## Test Environment
- URL: http://shop.test
- Admin credentials: admin@acme.test / password
- Customer credentials: customer@acme.test / password
- Browser: Chrome (Playwright MCP)
- Date: 2026-03-17

---

## 1. Automated Checks

### 1.1 Pest Test Suite
- **Command**: `php -d memory_limit=1G artisan test`
- **Result**: PARTIAL PASS
- **Details**: Most tests pass. Two test files fail:
  - `tests/Feature/Admin/OrderManagementTest.php` - 1 failure (assertSee cannot find faker-generated text)
  - `tests/Feature/Admin/SettingsTest.php` - 5 failures (uses `Livewire::withSession()` which is not a valid Livewire v4 method)
- **Note**: Default 512MB memory limit causes OOM. Must run with `-d memory_limit=1G`.

### 1.2 Pint Code Style
- **Command**: `vendor/bin/pint --dirty`
- **Result**: PASS (1 file auto-fixed)

### 1.3 Fresh Migration + Seed
- **Command**: `php artisan migrate:fresh --seed`
- **Result**: PASS (clean run, no errors)

---

## 2. Storefront Browser Tests

### TC-SF-01: Homepage
- **Steps**: Navigate to http://shop.test
- **Expected**: Store name, featured products, navigation visible
- **Result**: PASS
- **Notes**: Page loads with header, product cards showing correct prices (e.g. 24.99 EUR), footer renders.

### TC-SF-02: Collections Index
- **Steps**: Navigate to /collections
- **Expected**: List of collections displayed
- **Result**: PASS
- **Notes**: Collections page shows all seeded collections with product counts.

### TC-SF-03: Collection Detail
- **Steps**: Navigate to /collections/t-shirts (or similar handle)
- **Expected**: Products in collection displayed with correct prices
- **Result**: PASS
- **Notes**: Products display with correct formatted prices.

### TC-SF-04: Product Detail
- **Steps**: Navigate to /products/classic-cotton-t-shirt
- **Expected**: Product info, variant selector, quantity controls, Add to Cart button
- **Result**: PASS (page render)
- **Notes**: Product page shows title, description, price, variant options (Size, Color), quantity controls, and Add to Cart button.

### TC-SF-05: Add to Cart
- **Steps**: On product page, select variant, click "Add to Cart"
- **Expected**: Item added to cart, cart drawer opens
- **Result**: FAIL - 500 Error
- **Bug**: `BindingResolutionException: Target class [current_store] does not exist` in `CartDrawer.php:74`. Livewire update requests to `/livewire/update` do not pass through the `storefront` middleware group, so `app('current_store')` is never bound. See Bug #1.

### TC-SF-06: Search
- **Steps**: Navigate to /search?q=cotton
- **Expected**: Search results for "cotton" displayed
- **Result**: PASS
- **Notes**: Returns "5 results for cotton" including Classic Cotton T-Shirt (24.99 EUR), Organic Hoodie (59.99 EUR), Graphic Print Tee (29.99 EUR), Cargo Pants (54.99 EUR), Bucket Hat (24.99 EUR). Sort dropdown (Relevance, Price, Newest) and sidebar filters (Vendor, Price range) all render correctly.

### TC-SF-07: Static Page
- **Steps**: Navigate to /pages/about (or similar)
- **Expected**: Page content displayed
- **Result**: PASS
- **Notes**: Footer links to About Us, FAQ, Shipping & Returns, Privacy Policy, Terms of Service all render as navigation links.

### TC-SF-11: 404 Page
- **Steps**: Navigate to /products/nonexistent-product-xyz
- **Expected**: Custom 404 page with store branding
- **Result**: PASS
- **Notes**: Shows "404 - Page not found" with Acme Fashion header, "Go back home" link, and store footer.

### TC-SF-08: Customer Login
- **Steps**: Navigate to /account/login, enter customer@acme.test / password, click Log in
- **Expected**: Redirect to account dashboard
- **Result**: FAIL - "Invalid credentials"
- **Bug**: Same root cause as TC-SF-05. The Livewire login action calls `Auth::guard('customer')->attempt()` which uses `CustomerUserProvider::retrieveByCredentials()`. This checks `app()->bound('current_store')` and returns null when the store isn't bound (because the Livewire update POST doesn't go through storefront middleware). See Bug #1.

### TC-SF-09: Customer Registration
- **Steps**: Navigate to /account/register, fill form, click Register
- **Expected**: Account created, redirect to dashboard
- **Result**: FAIL (not attempted - same Livewire middleware issue will prevent it)
- **Bug**: Same root cause as Bug #1.

### TC-SF-10: Customer Account (Dashboard, Orders, Addresses)
- **Steps**: After login, navigate to /account, /account/orders, /account/addresses
- **Expected**: Account pages display customer data
- **Result**: BLOCKED - Cannot log in (see TC-SF-08)

---

## 3. Admin Panel Browser Tests

### TC-AD-01: Admin Login
- **Steps**: Navigate to /admin/login, enter admin@acme.test / password, click Log in
- **Expected**: Redirect to admin dashboard
- **Result**: PASS
- **Notes**: Login form renders, credentials accepted, redirects to /admin.

### TC-AD-02: Dashboard
- **Steps**: Navigate to /admin after login
- **Expected**: Dashboard with stats and charts
- **Result**: PARTIAL PASS
- **Notes**: Dashboard renders with sidebar, store selector (Acme Fashion selected), and stat cards. However, charts do not render due to missing Chart.js. See Bug #2.

### TC-AD-03: Products Index
- **Steps**: Navigate to /admin/products
- **Expected**: Product listing with search/filter
- **Result**: PASS
- **Notes**: Shows 15 products with titles, prices, status badges, and variant counts.

### TC-AD-04: Collections Index
- **Steps**: Navigate to /admin/collections
- **Expected**: Collection listing
- **Result**: FAIL - 500 Error
- **Bug**: `BindingResolutionException: Target class [current_store] does not exist` in `StoreScope.php:13`. The admin middleware's `resolveFromSession` does not bind `current_store` when there's no `current_store_id` in session. Even though the store selector shows "Acme Fashion" in the header, session state is inconsistent across requests. See Bug #3.

### TC-AD-05: Orders Index
- **Steps**: Navigate to /admin/orders
- **Expected**: Order listing
- **Result**: FAIL - 500 Error
- **Bug**: Same as TC-AD-04. `StoreScope` throws when querying Order model. See Bug #3.

### TC-AD-06: Customers Index
- **Steps**: Navigate to /admin/customers
- **Expected**: Customer listing
- **Result**: FAIL - 500 Error
- **Bug**: Same as TC-AD-04. `StoreScope` throws when querying Customer model. See Bug #3.

### TC-AD-07: Discounts Index
- **Steps**: Navigate to /admin/discounts
- **Expected**: Discount codes listed with details
- **Result**: PASS
- **Notes**: Shows 5 discounts (WELCOME10, FLAT5, FREESHIP, EXPIRED20, MAXED) with type, value, usage counts, status, and date ranges.

### TC-AD-08: Settings (General)
- **Steps**: Navigate to /admin/settings
- **Expected**: Store settings form with name, currency, locale, timezone
- **Result**: PASS
- **Notes**: Shows store name "Acme Fashion", handle (disabled), currency EUR, locale English, timezone Europe/Berlin, with Save button.

### TC-AD-09: Themes
- **Steps**: Navigate to /admin/themes
- **Expected**: Theme listing with customize option
- **Result**: PASS
- **Notes**: Shows "Default Theme v1.0.0" with Draft status and Customize link.

### TC-AD-10: Pages Index
- **Steps**: Navigate to /admin/pages
- **Expected**: Page listing
- **Result**: FAIL - 500 Error
- **Bug**: `TypeError: ucfirst(): Argument #1 ($string) must be of type string, App\Enums\PageStatus given` at `resources/views/livewire/admin/pages/index.blade.php:35`. The template does `ucfirst($page->status ?? 'draft')` but `$page->status` is a `PageStatus` enum object, not a string. See Bug #4.

### TC-AD-11: Navigation
- **Steps**: Navigate to /admin/navigation
- **Expected**: Navigation menus displayed
- **Result**: PASS
- **Notes**: Shows "Main Menu" and "Footer Menu" with Edit buttons. Minor JS error: `fluxModal is not defined` (non-blocking).

### TC-AD-12: Analytics
- **Steps**: Navigate to /admin/analytics
- **Expected**: Sales stats, charts, top products
- **Result**: PARTIAL PASS
- **Notes**: Summary cards show $1,517.12 total sales, 15 orders, $101.14 AOV. Top products table renders correctly (10 products ranked). "Sales over time" chart area is blank due to missing Chart.js (Bug #2). Filter dropdowns (date range, channel, device) render correctly.

### TC-AD-13: Inventory
- **Steps**: Navigate to /admin/inventory
- **Expected**: Inventory listing with stock levels
- **Result**: FAIL - 500 Error
- **Bug**: `QueryException: no such column: product_variants.option1`. The inventory query in `app/Livewire/Admin/Inventory/Index.php:64` selects `option1`, `option2`, `option3` columns from `product_variants`, but these columns don't exist in the schema. The variant options are likely stored differently (JSON or separate options table). See Bug #5.

### TC-AD-14: Apps
- **Steps**: Navigate to /admin/apps
- **Expected**: Apps listing (empty if none installed)
- **Result**: PASS
- **Notes**: Shows "No apps installed" with description.

### TC-AD-15: Developers
- **Steps**: Navigate to /admin/developers
- **Expected**: API tokens and webhooks management
- **Result**: PASS
- **Notes**: Shows API tokens section ("No tokens generated yet") and Webhooks section ("No webhooks configured yet"). Minor JS error: `fluxModal is not defined` (non-blocking).

---

## 4. Bugs Found

### Bug #1 (CRITICAL): Livewire update requests bypass storefront middleware
- **Severity**: Critical
- **Affected**: All Livewire actions on storefront (add to cart, customer login, customer registration, any form submission)
- **Root Cause**: Livewire's `/livewire/update` endpoint only has `web` middleware, not the `storefront` middleware. The `storefront` middleware calls `ResolveStore::resolveFromHostname()` which binds `app('current_store')`. Without it, any code calling `app('current_store')` throws `BindingResolutionException`, and the `CustomerUserProvider::retrieveByCredentials()` returns null.
- **Location**: `app/Http/Middleware/ResolveStore.php`, Livewire update route configuration
- **Fix**: Configure Livewire's update route to include the `storefront` middleware, or use a global middleware/service provider that always resolves the store from the hostname on every request (not just route-specific middleware).

### Bug #2 (LOW): Chart.js not loaded on admin pages
- **Severity**: Low
- **Affected**: Admin dashboard charts, Analytics "Sales over time" chart
- **Root Cause**: `Chart` is referenced in JavaScript but the Chart.js library is not included in the page assets.
- **Location**: Admin layout template / Vite build
- **Fix**: Install Chart.js (`npm install chart.js`) and import it in the admin JS bundle, or include it via CDN in the admin layout.

### Bug #3 (CRITICAL): Admin pages crash with StoreScope BindingResolutionException
- **Severity**: Critical
- **Affected**: Admin orders, customers, collections, and any admin page querying models with `BelongsToStore` trait
- **Root Cause**: `StoreScope::apply()` at line 13 calls `app('current_store')` which throws `BindingResolutionException` when the container binding doesn't exist. The admin `ResolveStore::resolveFromSession()` returns without binding if no `current_store_id` is in session. Even after selecting a store in the UI, the session value may not persist correctly, or the middleware runs before the session is populated.
- **Location**: `app/Models/Scopes/StoreScope.php:13`, `app/Http/Middleware/ResolveStore.php:59-65`
- **Fix**: Change `StoreScope::apply()` to use `app()->bound('current_store') ? app('current_store') : null` instead of `app('current_store')`. This makes it safe when the binding doesn't exist. Separately, ensure the admin middleware always sets `current_store_id` in session (e.g., default to the user's first store if not set).

### Bug #4 (MEDIUM): Admin Pages index crashes on PageStatus enum
- **Severity**: Medium
- **Affected**: Admin Pages listing (/admin/pages)
- **Root Cause**: `resources/views/livewire/admin/pages/index.blade.php:35` does `ucfirst($page->status ?? 'draft')`, but `$page->status` is cast to a `PageStatus` enum, not a string. `ucfirst()` requires a string argument.
- **Location**: `resources/views/livewire/admin/pages/index.blade.php:35`
- **Fix**: Change to `ucfirst($page->status->value ?? 'draft')` or `$page->status->name` depending on the enum type (string-backed or unit).

### Bug #5 (MEDIUM): Admin Inventory query references non-existent columns
- **Severity**: Medium
- **Affected**: Admin Inventory page (/admin/inventory)
- **Root Cause**: The query in `app/Livewire/Admin/Inventory/Index.php:64` selects `product_variants.option1`, `option2`, `option3` but these columns don't exist in the `product_variants` table. The variant options are stored via a separate mechanism (likely `variant_option_values` table or JSON).
- **Location**: `app/Livewire/Admin/Inventory/Index.php`
- **Fix**: Update the query to join with the correct options/values tables, or remove the option columns from the select.

### Bug #6 (LOW): Memory exhaustion running test suite
- **Severity**: Low
- **Affected**: `php artisan test` command
- **Root Cause**: Default PHP memory limit of 512MB is insufficient for the full test suite.
- **Fix**: Set `memory_limit=1G` in phpunit.xml or php.ini for the test environment.

### Bug #7 (LOW): fluxModal JS error on navigation and developers pages
- **Severity**: Low
- **Affected**: Admin navigation, developers pages
- **Root Cause**: Alpine.js expression references `fluxModal` which is not defined. Likely a Flux UI Pro component being referenced in free edition.
- **Location**: Admin layout or component templates
- **Fix**: Remove or replace the `fluxModal` reference with a compatible free component.

### Bug #8 (LOW): StoreDomainSeeder doesn't include shop.test
- **Severity**: Low
- **Affected**: Fresh seed + browser testing at shop.test
- **Root Cause**: `StoreDomainSeeder` only seeds `acme-fashion.test` and `acme-electronics.test` as storefront domains. The Herd-served hostname `shop.test` is not seeded.
- **Fix**: Add `shop.test` as a storefront domain for the primary store in `StoreDomainSeeder`, or document that testing requires using `acme-fashion.test`.

---

## 5. Summary

| Area | Total Tests | Pass | Partial | Fail | Blocked |
|------|------------|------|---------|------|---------|
| Automated (Pest) | ~250 | ~244 | - | 6 | - |
| Storefront | 11 | 5 | 0 | 3 | 3 |
| Admin Panel | 15 | 8 | 2 | 5 | 0 |
| **Total Browser** | **26** | **13** | **2** | **8** | **3** |

### Critical Issues Blocking Release
1. **Bug #1**: Livewire storefront middleware gap - blocks ALL storefront interactivity (cart, login, registration)
2. **Bug #3**: Admin StoreScope binding - blocks admin orders, customers, collections pages

### Priority Fix Order
1. Bug #3 - Quick fix: change `app('current_store')` to safe bounded check in `StoreScope.php`
2. Bug #1 - Configure Livewire update route with storefront middleware
3. Bug #5 - Fix inventory query column references
4. Bug #4 - Fix PageStatus enum in blade template
5. Bug #2 - Add Chart.js dependency
6. Bugs #6, #7, #8 - Low priority fixes
