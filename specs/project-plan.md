# Project Plan

> Comprehensive build plan for the Shop e-commerce platform.
> PHP 8.4 / Laravel 12 / Livewire v4 / Flux UI Free v2 / Tailwind CSS v4 / SQLite / Pest v4

---

## Phase Dependency Graph

```
P1 (Foundation) --> P2 (Catalog)
P1 (Foundation) --> P3 (Themes & Storefront)
P2 (Catalog) + P3 (Themes) --> P4 (Cart, Checkout, Discounts)
P4 --> P5 (Payments, Orders, Fulfillment)
P5 --> P6 (Customer Accounts)
P5 --> P7 (Admin Panel)
P2 --> P8 (Search)
P5 --> P9 (Analytics)
P5 --> P10 (Apps & Webhooks)
P6 + P7 + P8 + P9 + P10 --> P11 (Polish)
P11 --> P12 (Full Test Suite)
```

---

## Phase 1: Foundation (Migrations, Models, Middleware, Auth)

**Priority:** CRITICAL -- everything depends on this.
**Specs:** 01-DATABASE-SCHEMA.md (Epic 1), 05-BUSINESS-LOGIC.md (Section 1), 06-AUTH-AND-SECURITY.md, 09-IMPLEMENTATION-ROADMAP.md (Steps 1.1-1.8)

### Technical Specification Summary

Multi-tenant foundation with organizations, stores, domains, users, and store settings. Tenant resolution middleware resolves stores from hostnames (storefront) or session (admin). BelongsToStore trait + StoreScope for automatic tenant isolation. Dual auth: admin users via `web` guard (session), customers via custom `customer` guard scoped by store. Authorization via policies with role-based permission matrix (Owner/Admin/Staff/Support). Rate limiting on login (5/min/IP). SQLite with WAL mode, foreign keys enabled.

### Development Tasks

- [ ] **P1-T1** -- Environment and config setup: `.env` (SQLite, file cache/session, sync queue, log mail), `config/database.php` (WAL mode, foreign keys, busy_timeout=5000), `config/auth.php` (customer guard, customers provider, customers password broker), `config/session.php`, `config/cache.php`, `config/queue.php`, `config/filesystems.php`, `config/logging.php` (structured JSON channel)
- [ ] **P1-T2** -- Core migrations (Batch 1-2): `create_organizations_table`, `create_stores_table` (FK: organization_id), `create_store_domains_table` (FK: store_id), modify `users` migration (add status, last_login_at, two_factor columns), `create_store_users_table` (composite PK: store_id + user_id, role column), `create_store_settings_table` (PK: store_id). All monetary amounts as INTEGER (cents), enums as TEXT with CHECK constraints.
- [ ] **P1-T3** -- Enums: `StoreStatus` (Active, Suspended), `StoreUserRole` (Owner, Admin, Staff, Support), `StoreDomainType` (Storefront, Admin, Api) in `app/Enums/`
- [ ] **P1-T4** -- Core models with factories and seeders: `Organization`, `Store`, `StoreDomain`, `StoreUser` (pivot), `StoreSettings`. Define all relationships, $fillable/$guarded, casts() method for JSON/enum columns. User model: add `roleForStore(Store): ?StoreUserRole` helper, `belongsToMany(Store)` via store_users.
- [ ] **P1-T5** -- BelongsToStore trait and StoreScope: `App\Models\Concerns\BelongsToStore` (applies StoreScope, auto-sets store_id on creating event), `App\Models\Scopes\StoreScope` (where store_id = current_store->id)
- [ ] **P1-T6** -- ResolveStore middleware: storefront resolution from hostname (cache 5min), admin resolution from session, 404 for unknown hostname, 503 for suspended stores. Register in `bootstrap/app.php` as `store.resolve` alias and in `storefront`/`admin` middleware groups.
- [ ] **P1-T7** -- Rate limiters: register in `AppServiceProvider::boot()` -- login (5/min/IP), api.admin (60/min/token), api.storefront (120/min/IP), checkout (10/min/session), search (30/min/IP), analytics (60/min/IP), webhooks (100/min/IP)
- [ ] **P1-T8** -- Admin authentication: Livewire `Admin\Auth\Login` component, `Admin\Auth\Logout` action, standard session auth via `Auth::guard('web')->attempt()`, session regeneration on login, last_login_at update. Password reset flow with `Password::broker('users')`.
- [ ] **P1-T9** -- Customer authentication: custom `CustomerUserProvider` that scopes by store_id, Livewire `Storefront\Account\Auth\Login` and `Register` components. Email unique per store (not globally). Rate limited at 5/min/IP.
- [ ] **P1-T10** -- Authorization policies: `ProductPolicy`, `OrderPolicy`, `CollectionPolicy`, `DiscountPolicy`, `CustomerPolicy`, `StorePolicy`, `PagePolicy`, `ThemePolicy`, `FulfillmentPolicy`, `RefundPolicy`. Each checks user role via store_users pivot. Permission matrix: Owner=all, Admin=most, Staff=products/orders/discounts/fulfillments/analytics/view-customers, Support=read-only orders + view customers.
- [ ] **P1-T11** -- Routes setup: `routes/web.php` (admin auth routes prefix `/admin`, storefront routes), `routes/api.php` (storefront API prefix `/api/storefront/v1`, admin API prefix `/api/admin/v1`), `routes/console.php` (scheduled jobs). Register middleware groups in `bootstrap/app.php`.

**Dependencies:** None (this is the first phase).

### Code Review Checkpoint

- [ ] **P1-CR1** -- Verify all migrations run cleanly (`php artisan migrate:fresh`)
- [ ] **P1-CR2** -- Verify all model relationships return correct types
- [ ] **P1-CR3** -- Verify tenant isolation via StoreScope works correctly
- [ ] **P1-CR4** -- Verify middleware registration in `bootstrap/app.php`
- [ ] **P1-CR5** -- Run `vendor/bin/pint --dirty` for code style

### Pest Tests

- [ ] **P1-TEST1** -- `tests/Feature/Tenancy/TenantResolutionTest.php`: resolves store from hostname (200), returns 404 for unknown hostname, returns 503 for suspended store, resolves from session for admin, denies admin without store_users record, caches hostname lookup (6 tests)
- [ ] **P1-TEST2** -- `tests/Feature/Tenancy/StoreIsolationTest.php`: scopes product queries to store, scopes order queries to store, auto-sets store_id on create, prevents cross-store access, allows access when scope removed (5 tests)
- [ ] **P1-TEST3** -- `tests/Feature/Auth/AdminAuthTest.php`: renders login page, authenticates with valid credentials, rejects invalid, no email/password reveal, rate limits (6th = 429), regenerates session, logout works, redirects unauthenticated, remember me, last_login_at (10 tests)
- [ ] **P1-TEST4** -- `tests/Feature/Auth/CustomerAuthTest.php`: renders login, authenticates, rejects invalid, scopes to store, rate limits, registers customer, rejects duplicate email, allows same email cross-store, logout, merges guest cart on login (10 tests)
- [ ] **P1-TEST5** -- `tests/Feature/Auth/SanctumTokenTest.php`: creates token with abilities, authenticates API request, rejects invalid token, enforces abilities, revokes token (5 tests)
- [ ] **P1-TEST6** -- Shared test helpers in `tests/Pest.php`: `createStoreContext()`, `actingAsAdmin()`, `actingAsCustomer()`

### Browser Verification

- [ ] **P1-BV1** -- Visit `/admin/login` at shop.test, verify the login form renders
- [ ] **P1-BV2** -- Login as admin, verify redirect to `/admin` dashboard
- [ ] **P1-BV3** -- Visit storefront root `/`, verify 404 or placeholder renders (no store domain matched yet for shop.test)

---

## Phase 2: Catalog (Products, Variants, Inventory, Collections, Media)

**Priority:** HIGH -- storefront and orders depend on this.
**Specs:** 01-DATABASE-SCHEMA.md (Epic 2), 05-BUSINESS-LOGIC.md (Sections 2-3), 09-IMPLEMENTATION-ROADMAP.md (Steps 2.1-2.5)

### Technical Specification Summary

Products with options (Size, Color), option values, and variants (cartesian product). Each variant has an InventoryItem for stock tracking. Collections group products via pivot. ProductMedia stores images with processing pipeline (thumbnail 150x150, medium 600x600, large 1200x1200). Product status state machine: Draft -> Active -> Archived (with guards). Variant matrix auto-generation. Inventory service with reserve/release/commit/restock operations. Handle (slug) generator with collision handling scoped per store.

### Development Tasks

- [ ] **P2-T1** -- Catalog migrations (Batch 3-5): `create_products_table`, `create_product_options_table`, `create_product_option_values_table`, `create_product_variants_table`, `create_variant_option_values_table`, `create_inventory_items_table`, `create_collections_table`, `create_collection_products_table`, `create_product_media_table`
- [ ] **P2-T2** -- Enums: `ProductStatus` (Draft, Active, Archived), `VariantStatus` (Active, Archived), `CollectionStatus` (Draft, Active, Archived), `MediaType` (Image, Video), `MediaStatus` (Processing, Ready, Failed), `InventoryPolicy` (Deny, Continue)
- [ ] **P2-T3** -- Models with relationships, factories, seeders: `Product` (hasMany variants/options/media, belongsToMany collections), `ProductOption` (belongsTo product, hasMany values), `ProductOptionValue`, `ProductVariant` (belongsTo product, hasOne inventoryItem, belongsToMany optionValues), `InventoryItem` (belongsTo variant), `Collection` (belongsToMany products), `ProductMedia` (belongsTo product). Apply BelongsToStore trait on Product, Collection, InventoryItem.
- [ ] **P2-T4** -- `App\Support\HandleGenerator`: generates unique slugs scoped per store with collision suffix (-1, -2, etc.), handles special characters, excludes current record ID from collision check
- [ ] **P2-T5** -- `App\Services\ProductService`: create (with nested variants/options), update, transitionStatus (state machine validation), delete (only draft with no orders). Dispatches `ProductStatusChanged` event.
- [ ] **P2-T6** -- `App\Services\VariantMatrixService`: rebuildMatrix computes cartesian product of options, creates missing variants, archives orphaned variants with order references, deletes orphaned variants without references. Auto-creates default variant for products without options.
- [ ] **P2-T7** -- `App\Services\InventoryService`: checkAvailability, reserve, release, commit, restock. All in DB transactions. Throws `InsufficientInventoryException` when policy=deny and available < quantity.
- [ ] **P2-T8** -- `App\Jobs\ProcessMediaUpload`: resizes images to 3 sizes, updates ProductMedia status. Livewire file upload via `WithFileUploads`, stored on local `public` disk.

**Dependencies:** P1 (Foundation must be complete).

### Code Review Checkpoint

- [ ] **P2-CR1** -- Verify all catalog migrations run on top of Phase 1
- [ ] **P2-CR2** -- Verify variant matrix generation for various option combinations
- [ ] **P2-CR3** -- Verify inventory operations are atomic (DB transactions)
- [ ] **P2-CR4** -- Verify handle generator uniqueness per store
- [ ] **P2-CR5** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P2-TEST1** -- `tests/Feature/Products/ProductCrudTest.php`: list products, create with default variant, generate handle, handle collision, update, status transitions (draft->active, active->archived), reject draft->active without priced variant, prevent active->draft with orders, delete draft, prevent delete with orders, filter by status, search by title (13 tests)
- [ ] **P2-TEST2** -- `tests/Feature/Products/VariantTest.php`: create from option matrix (3x2=6), preserve existing on add, archive orphaned with orders, delete orphaned without orders, auto-create default variant, validate SKU uniqueness within store, allow duplicate SKU cross-store, allow null SKUs (8 tests)
- [ ] **P2-TEST3** -- `tests/Feature/Products/InventoryTest.php`: auto-create on variant creation, check availability, reserve, throws InsufficientInventory (deny), allows overselling (continue), release, commit, restock (8 tests)
- [ ] **P2-TEST4** -- `tests/Feature/Products/CollectionTest.php`: create with handle, add products, remove products, reorder, transition draft->active, list with product count, scope to store (7 tests)
- [ ] **P2-TEST5** -- `tests/Feature/Products/MediaUploadTest.php`: upload image, process and generate variants, reject non-image, set alt text, reorder positions, delete with file removal (6 tests)
- [ ] **P2-TEST6** -- `tests/Unit/HandleGeneratorTest.php`: slug from title, suffix on collision, increment on multiple collisions, special characters, exclude current ID, scope to store (6 tests)

### Browser Verification

- [ ] **P2-BV1** -- Verify products can be created via tinker/seeder
- [ ] **P2-BV2** -- Verify variant matrix generation produces correct combinations

---

## Phase 3: Themes, Pages, Navigation, Storefront Layout

**Priority:** HIGH -- storefront rendering depends on this.
**Specs:** 01-DATABASE-SCHEMA.md (Epic 3), 04-STOREFRONT-UI.md, 09-IMPLEMENTATION-ROADMAP.md (Steps 3.1-3.5)

### Technical Specification Summary

Theme system with files and settings per store. CMS pages (draft/published/archived). Navigation menus with hierarchical items (link, page, collection, product types). Full Blade storefront layout: header with nav, announcement bar, main content, footer, cart drawer. Dark mode support via `dark:` prefix. Storefront Livewire components for home, collections, products, cart, search, pages. NavigationService builds menu trees and caches per store (5min TTL). ThemeSettings singleton for active theme config. Currency formatting: cents to display with `<x-price>` component (e.g., 2499 -> "24.99 EUR").

### Development Tasks

- [ ] **P3-T1** -- Migrations (Batch 3): `create_themes_table`, `create_theme_files_table`, `create_theme_settings_table`, `create_pages_table`, `create_navigation_menus_table`, `create_navigation_items_table`
- [ ] **P3-T2** -- Enums: `ThemeStatus` (Draft, Published), `PageStatus` (Draft, Published, Archived), `NavigationItemType` (Link, Page, Collection, Product)
- [ ] **P3-T3** -- Models with factories/seeders: `Theme`, `ThemeFile`, `ThemeSettings`, `Page`, `NavigationMenu`, `NavigationItem`. Apply BelongsToStore on Theme, Page, NavigationMenu. Relationships as specified.
- [ ] **P3-T4** -- `App\Services\NavigationService`: buildTree (hierarchical menu from flat items), resolveUrl (page/collection/product URL resolution). Cache per store with 5min TTL.
- [ ] **P3-T5** -- ThemeSettings service: singleton in `AppServiceProvider`, loads and caches active theme settings for current store.
- [ ] **P3-T6** -- Storefront Blade layout: `resources/views/storefront/layouts/app.blade.php` (header with nav, announcement bar, main content, footer, cart drawer). Dark mode via `dark:`. Mobile-first responsive design.
- [ ] **P3-T7** -- Storefront Blade components: `product-card`, `price` (cents to formatted string: "24.99 EUR"), `badge`, `quantity-selector`, `address-form`, `order-summary`, `breadcrumbs`, `pagination`
- [ ] **P3-T8** -- Storefront Livewire components: `Storefront\Home`, `Storefront\Collections\Index`, `Storefront\Collections\Show` (filters/sort/pagination), `Storefront\Products\Show` (variant selection, image gallery, add-to-cart), `Storefront\Pages\Show`
- [ ] **P3-T9** -- Error pages: styled 404 and 503 pages matching storefront theme

**Dependencies:** P1 (Foundation must be complete). Can be built in parallel with P2.

### Code Review Checkpoint

- [ ] **P3-CR1** -- Verify storefront layout renders with all structural elements
- [ ] **P3-CR2** -- Verify navigation service produces correct URL trees
- [ ] **P3-CR3** -- Verify dark mode works across all storefront views
- [ ] **P3-CR4** -- Verify price component formats correctly (0, small, large amounts)
- [ ] **P3-CR5** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P3-TEST1** -- Navigation service: builds tree, resolves URLs for each item type, caches results
- [ ] **P3-TEST2** -- Page rendering: published pages render, draft pages return 404
- [ ] **P3-TEST3** -- Theme settings: loads active theme, caches correctly

### Browser Verification

- [ ] **P3-BV1** -- Visit storefront home page, verify layout renders (header, footer, nav)
- [ ] **P3-BV2** -- Visit a collection page, verify product grid renders
- [ ] **P3-BV3** -- Visit a product page, verify variant selector and price display
- [ ] **P3-BV4** -- Verify dark mode toggle/system preference works
- [ ] **P3-BV5** -- Verify 404 page renders with storefront styling

---

## Phase 4: Cart, Checkout, Discounts, Shipping, Taxes

**Priority:** HIGH -- core shopping flow.
**Specs:** 01-DATABASE-SCHEMA.md (Epics 4-5), 02-API-ROUTES.md (Sections 2.1-2.3), 05-BUSINESS-LOGIC.md (Sections 4-8), 09-IMPLEMENTATION-ROADMAP.md (Steps 4.1-4.9)

### Technical Specification Summary

Cart with versioned optimistic concurrency (409 on mismatch). CartLines with unit_price, subtotal, discount, total. Session-based cart binding for guests, merges into customer cart on login. Checkout state machine: started -> addressed -> shipping_selected -> payment_selected -> completed (or expired). Discount service: code/automatic, percent/fixed/free_shipping, case-insensitive, usage limits, minimum purchase rules, proportional allocation across lines. Shipping calculator: zone matching by country/region, flat/weight/price rate types. Tax calculator: manual mode with basis points (1900=19%), exclusive/inclusive modes, integer math only. PricingEngine pipeline: subtotals -> discount -> shipping -> tax -> total. Value objects: PricingResult, TaxLine. Scheduled jobs: ExpireAbandonedCheckouts (every 15min), CleanupAbandonedCarts (daily).

### Development Tasks

- [ ] **P4-T1** -- Migrations (Batch 4-5): `create_carts_table`, `create_cart_lines_table`, `create_checkouts_table`, `create_shipping_zones_table`, `create_shipping_rates_table`, `create_tax_settings_table`, `create_discounts_table`
- [ ] **P4-T2** -- Enums: `CartStatus` (Active, Converted, Abandoned), `CheckoutStatus` (Started, Addressed, ShippingSelected, PaymentPending, Completed, Expired), `DiscountType` (Code, Automatic), `DiscountValueType` (Percent, Fixed, FreeShipping), `DiscountStatus` (Draft, Active, Expired, Disabled), `ShippingRateType` (Flat, Weight, Price, Carrier), `TaxMode` (Manual, Provider)
- [ ] **P4-T3** -- Models with factories/seeders: `Cart`, `CartLine`, `Checkout`, `ShippingZone`, `ShippingRate`, `TaxSettings`, `Discount`. Apply BelongsToStore on Cart, Checkout, ShippingZone, Discount. Define all relationships.
- [ ] **P4-T4** -- `App\Services\CartService`: create, addLine (validate active product/inventory), updateLineQuantity, removeLine, getOrCreateForSession, mergeOnLogin. All mutations increment cart_version. Dispatches `CartUpdated` event.
- [ ] **P4-T5** -- `App\Services\DiscountService`: validate (code lookup case-insensitive, status/date/usage/minimum checks), calculate (percent/fixed/free_shipping, proportional allocation). Throws `InvalidDiscountException` with reason codes.
- [ ] **P4-T6** -- `App\Services\ShippingCalculator`: getAvailableRates (zone matching by country/region JSON), calculate (flat/weight/price rate types). Skips inactive rates, returns zero when no items require shipping.
- [ ] **P4-T7** -- `App\Services\TaxCalculator`: calculate (manual rate or provider), extractInclusive (tax from gross), addExclusive (tax to net). All integer math, rates in basis points.
- [ ] **P4-T8** -- `App\ValueObjects\PricingResult` and `App\ValueObjects\TaxLine`: immutable value objects for pricing pipeline output.
- [ ] **P4-T9** -- `App\Services\PricingEngine`: calculate pipeline (line subtotals -> cart subtotal -> discount -> discounted subtotal -> shipping -> tax -> total). Stores result in checkouts.totals_json.
- [ ] **P4-T10** -- `App\Services\CheckoutService`: state machine transitions (setAddress, setShippingMethod, selectPaymentMethod, completeCheckout, expireCheckout). Validates state transitions, reserves inventory on payment_selected.
- [ ] **P4-T11** -- `App\Jobs\ExpireAbandonedCheckouts`: runs every 15min, finds expired checkouts, releases inventory, transitions to expired. `App\Jobs\CleanupAbandonedCarts`: runs daily, marks old active carts as abandoned. Register in `routes/console.php`.
- [ ] **P4-T12** -- Storefront Cart/Checkout Livewire components: `Storefront\CartDrawer` (slide-out panel), `Storefront\Cart\Show` (full cart page), `Storefront\Checkout\Show` (multi-step stepper), `Storefront\Checkout\Confirmation` (order confirmation)
- [ ] **P4-T13** -- Cart REST API endpoints: POST/GET /carts, POST/PUT/DELETE /carts/{id}/lines. Checkout REST API: POST /checkouts, GET/PUT address/shipping-method/payment-method, POST apply-discount, DELETE discount. Form Request classes for validation.
- [ ] **P4-T14** -- Events: `CheckoutCompleted`, `CheckoutAddressed`, `CheckoutShippingSelected`, `CartUpdated`

**Dependencies:** P2 (Catalog) and P3 (Storefront Layout) must both be complete.

### Code Review Checkpoint

- [ ] **P4-CR1** -- Verify cart versioning and 409 conflict handling
- [ ] **P4-CR2** -- Verify checkout state machine enforces valid transitions only
- [ ] **P4-CR3** -- Verify pricing engine produces correct totals for various scenarios
- [ ] **P4-CR4** -- Verify discount proportional allocation sums correctly (no off-by-one)
- [ ] **P4-CR5** -- Verify all monetary calculations use integers only (no floats)
- [ ] **P4-CR6** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P4-TEST1** -- `tests/Unit/PricingEngineTest.php`: subtotal from lines, single line, empty cart, percent discount, fixed discount, cap at subtotal, free shipping, exclusive tax, inclusive tax, zero tax, flat shipping, end-to-end totals, rounding, idempotent (14 tests)
- [ ] **P4-TEST2** -- `tests/Unit/DiscountCalculatorTest.php`: validate active code, reject expired/not-yet-active/usage-limit/unknown, case-insensitive, minimum purchase, percent/fixed/free-shipping calculation, proportional allocation, rounding remainder (13 tests)
- [ ] **P4-TEST3** -- `tests/Unit/TaxCalculatorTest.php`: manual exclusive, extract inclusive, zero rate, zero amount, non-standard rate, small amounts, high rates (7 tests)
- [ ] **P4-TEST4** -- `tests/Unit/ShippingCalculatorTest.php`: match by country, match by region, no match, flat rate, weight-based, price-based, no-shipping items, multiple zones, skip inactive (9 tests)
- [ ] **P4-TEST5** -- `tests/Unit/CartVersionTest.php`: starts at v1, increments on add/update/remove, version mismatch exception (5 tests)
- [ ] **P4-TEST6** -- `tests/Feature/Cart/CartServiceTest.php`: create cart, add line, increment existing, reject inactive product, reject insufficient inventory (deny), allow overselling (continue), update quantity, remove on qty=0, remove line, version increments, session binding, merge on login (13 tests)
- [ ] **P4-TEST7** -- `tests/Feature/Cart/CartApiTest.php`: create via API, retrieve, add line, update quantity, remove line, 404 for nonexistent, 409 on version mismatch, rate limiting (8 tests)
- [ ] **P4-TEST8** -- `tests/Feature/Checkout/CheckoutFlowTest.php`: create from cart, full happy path, reject empty cart, expire after timeout, prevent duplicate orders (5 tests)
- [ ] **P4-TEST9** -- `tests/Feature/Checkout/CheckoutStateTest.php`: started->addressed, reject missing fields, addressed->shipping_selected, reject wrong zone rate, skip shipping for digital, shipping_selected->payment_selected, payment_selected->completed, reject invalid transitions, recalculate on address change (9 tests)
- [ ] **P4-TEST10** -- `tests/Feature/Checkout/PricingIntegrationTest.php`: simple totals, discount recalculation, snapshot in totals_json, recalculate on shipping change, prices-include-tax (5 tests)
- [ ] **P4-TEST11** -- `tests/Feature/Checkout/DiscountTest.php`: apply percent, apply fixed, remove discount, reject expired, increment usage on completion, free shipping (6 tests)
- [ ] **P4-TEST12** -- `tests/Feature/Checkout/ShippingTest.php`: available rates, empty for no match, flat rate, weight-based, zero for digital (5 tests)
- [ ] **P4-TEST13** -- `tests/Feature/Checkout/TaxTest.php`: exclusive, inclusive, zero tax, tax lines in totals_json (4 tests)

### Browser Verification

- [ ] **P4-BV1** -- Add product to cart via storefront, verify cart drawer shows item
- [ ] **P4-BV2** -- Update quantity in cart, verify totals recalculate
- [ ] **P4-BV3** -- Apply discount code, verify discount appears in totals
- [ ] **P4-BV4** -- Begin checkout, fill address, select shipping, verify totals update
- [ ] **P4-BV5** -- Verify expired discount code shows error message

---

## Phase 5: Payments, Orders, Fulfillment

**Priority:** HIGH -- completes the purchase flow.
**Specs:** 01-DATABASE-SCHEMA.md (Epics 5-7), 02-API-ROUTES.md (Sections 2.3-2.4, 3.3-3.4), 05-BUSINESS-LOGIC.md (Sections 9-12), 09-IMPLEMENTATION-ROADMAP.md (Steps 5.1-5.6)

### Technical Specification Summary

Customer and CustomerAddress models (customer email unique per store). Orders with line item snapshots (title_snapshot, sku_snapshot survives product deletion). Sequential order numbers per store (#1001, #1002...). Mock PSP (no external APIs): magic card numbers (4242...=success, 4000...0002=decline, 4000...9995=insufficient funds), PayPal always succeeds, bank transfer returns pending. Payment/Refund/Fulfillment records. Fulfillment guard: blocks fulfillment when financial_status != paid/partially_refunded. Bank transfer admin confirmation flow. Auto-cancel unpaid bank transfer orders after configurable days. Auto-fulfill digital products on payment confirmation. Events: OrderCreated, OrderPaid, OrderFulfilled, OrderCancelled, OrderRefunded.

### Development Tasks

- [ ] **P5-T1** -- Migrations (Batch 5-7): `create_customers_table`, `create_customer_addresses_table`, `create_orders_table`, `create_order_lines_table`, `create_payments_table`, `create_refunds_table`, `create_fulfillments_table`, `create_fulfillment_lines_table`
- [ ] **P5-T2** -- Enums: `OrderStatus` (Pending, Paid, Fulfilled, Cancelled, Refunded), `FinancialStatus` (Pending, Authorized, Paid, PartiallyRefunded, Refunded, Voided), `FulfillmentStatus` (Unfulfilled, Partial, Fulfilled), `PaymentMethod` (CreditCard, Paypal, BankTransfer), `PaymentStatus` (Pending, Captured, Failed, Refunded), `RefundStatus` (Pending, Processed, Failed), `FulfillmentShipmentStatus` (Pending, Shipped, Delivered)
- [ ] **P5-T3** -- Models with factories/seeders: `Customer` (belongsTo Store, hasMany addresses/orders/carts), `CustomerAddress`, `Order` (hasMany lines/payments/refunds/fulfillments), `OrderLine`, `Payment`, `Refund`, `Fulfillment`, `FulfillmentLine`. Apply BelongsToStore on Customer, Order.
- [ ] **P5-T4** -- Payment contracts and mock provider: `App\Contracts\PaymentProvider` interface (charge, refund), `App\Services\Payments\MockPaymentProvider` (magic card numbers, PayPal always succeeds, bank transfer returns pending, mock reference IDs). Bind interface to implementation in `AppServiceProvider`.
- [ ] **P5-T5** -- `App\Services\OrderService`: createFromCheckout (atomic: order + lines with snapshots, commit inventory, mark cart converted, dispatch OrderCreated), generateOrderNumber (sequential per store: #1001+), cancel (only if not fulfilled, release inventory, dispatch OrderCancelled)
- [ ] **P5-T6** -- `App\Services\RefundService`: create (validates amount <= payment, calls provider refund, updates financial_status to partially_refunded or refunded, restocks if flag set, dispatches OrderRefunded)
- [ ] **P5-T7** -- `App\Services\FulfillmentService`: create (fulfillment guard checks financial_status is paid/partially_refunded, creates fulfillment with lines/quantities, throws FulfillmentGuardException), markAsShipped (tracking info, shipped_at), markAsDelivered (delivered_at, dispatches FulfillmentDelivered). Updates order.fulfillment_status (partial/fulfilled).
- [ ] **P5-T8** -- Bank transfer admin confirmation: validate bank_transfer + pending, update financial_status to paid, update payment to captured, commit reserved inventory, auto-fulfill digital items, dispatch OrderPaid. `App\Jobs\CancelUnpaidBankTransferOrders` (daily, cancels after config days).
- [ ] **P5-T9** -- Events: `OrderCreated`, `OrderPaid`, `OrderFulfilled`, `OrderCancelled`, `OrderRefunded`, `CheckoutCompleted`
- [ ] **P5-T10** -- Checkout pay endpoint: `POST /api/storefront/v1/checkouts/{id}/pay` -- processes payment via MockPaymentProvider, creates order, handles success/decline/pending responses. Form request validation for card fields.
- [ ] **P5-T11** -- Order status API: `GET /api/storefront/v1/orders/{orderNumber}` with HMAC-signed token access
- [ ] **P5-T12** -- Admin Order API: `GET /api/admin/v1/stores/{storeId}/orders`, `GET .../orders/{id}`, `POST .../orders/{id}/fulfillments`, `POST .../orders/{id}/refunds`. Sanctum token auth with ability checks.

**Dependencies:** P4 (Cart/Checkout) must be complete.

### Code Review Checkpoint

- [ ] **P5-CR1** -- Verify order creation is atomic (all-or-nothing in transaction)
- [ ] **P5-CR2** -- Verify mock PSP magic card numbers produce correct results
- [ ] **P5-CR3** -- Verify fulfillment guard blocks when financial_status is pending
- [ ] **P5-CR4** -- Verify order line snapshots survive product deletion
- [ ] **P5-CR5** -- Verify sequential order numbers per store
- [ ] **P5-CR6** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P5-TEST1** -- `tests/Feature/Payments/MockPaymentProviderTest.php`: success card, decline card, insufficient funds, PayPal success, bank transfer pending, mock reference ID (6 tests)
- [ ] **P5-TEST2** -- `tests/Feature/Payments/PaymentServiceTest.php`: credit card -> paid order, PayPal -> paid order, bank transfer -> pending order, resolves MockPaymentProvider, creates payment record (5 tests)
- [ ] **P5-TEST3** -- `tests/Feature/Payments/BankTransferConfirmationTest.php`: admin confirms, cannot confirm non-bank-transfer, cannot confirm already confirmed, auto-cancel after config days, no cancel within config days, auto-fulfill digital on confirmation (6 tests)
- [ ] **P5-TEST4** -- `tests/Feature/Orders/OrderCreationTest.php`: creates from checkout, sequential numbers, line snapshots, commits inventory, marks cart converted, dispatches OrderCreated, preserves data on product delete, links to customer, sets email (9 tests)
- [ ] **P5-TEST5** -- `tests/Feature/Orders/RefundTest.php`: full refund, partial refund, rejects exceeding amount, restocks with flag, no restock without flag, role restriction, records reason (7 tests)
- [ ] **P5-TEST6** -- `tests/Feature/Orders/FulfillmentTest.php`: create for specific lines, partial status, fulfilled status, tracking info, pending->shipped, shipped->delivered, prevents over-fulfillment, guard blocks pending, guard allows paid, guard allows partially_refunded, auto-fulfill digital, role restriction (12 tests)
- [ ] **P5-TEST7** -- `tests/Feature/Api/StorefrontCheckoutApiTest.php`: create checkout, set address, select shipping, apply discount, retrieve with totals, select payment method, complete with credit card, reject declined card, validate address fields (9 tests)
- [ ] **P5-TEST8** -- `tests/Feature/Api/AdminOrderApiTest.php`: list orders, retrieve single, filter by status, create fulfillment, create refund, require write-orders ability (6 tests)

### Browser Verification

- [ ] **P5-BV1** -- Complete full checkout with credit card (success card), verify order confirmation page
- [ ] **P5-BV2** -- Attempt checkout with decline card, verify error message
- [ ] **P5-BV3** -- Complete checkout with bank transfer, verify bank instructions shown
- [ ] **P5-BV4** -- Verify PayPal checkout flow completes successfully

---

## Phase 6: Customer Accounts

**Priority:** MEDIUM -- enhances the shopping experience.
**Specs:** 09-IMPLEMENTATION-ROADMAP.md (Steps 6.1-6.2), 04-STOREFRONT-UI.md (Section 8), 02-API-ROUTES.md (Section 1.3 Customer Account Routes)

### Technical Specification Summary

Customer auth via custom `customer` guard with `CustomerUserProvider` (scopes by store_id). Customer account pages: dashboard (recent orders), order history (paginated), order detail (timeline), address book (CRUD with default toggle). Logout invalidates session, regenerates CSRF, redirects to login. Customer email unique per store, not globally.

### Development Tasks

- [ ] **P6-T1** -- Customer auth components (if not already built in P1-T9): finalize `CustomerUserProvider`, ensure customer guard works with store scoping, test login/register/logout flows
- [ ] **P6-T2** -- Livewire components: `Storefront\Account\Dashboard` (overview with recent orders), `Storefront\Account\Orders\Index` (paginated history), `Storefront\Account\Orders\Show` (detail with timeline), `Storefront\Account\Addresses\Index` (address CRUD)
- [ ] **P6-T3** -- Customer account Blade views: `account/login.blade.php`, `account/register.blade.php`, `account/dashboard.blade.php`, `account/orders/index.blade.php`, `account/orders/show.blade.php`, `account/addresses/index.blade.php`
- [ ] **P6-T4** -- Customer profile update: allow name and marketing_opt_in changes
- [ ] **P6-T5** -- Form requests: `RegisterCustomerRequest`, `StoreCustomerAddressRequest`, `UpdateCustomerAddressRequest`

**Dependencies:** P5 (Payments/Orders) must be complete (customers need orders to view).

### Code Review Checkpoint

- [ ] **P6-CR1** -- Verify customer can only see their own orders (not other customers')
- [ ] **P6-CR2** -- Verify address default toggle resets other addresses
- [ ] **P6-CR3** -- Verify logout properly invalidates session and regenerates CSRF
- [ ] **P6-CR4** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P6-TEST1** -- `tests/Feature/Customers/CustomerAccountTest.php`: renders dashboard, lists orders, shows order detail, prevents accessing other customer's orders, redirects unauthenticated, updates profile (6 tests)
- [ ] **P6-TEST2** -- `tests/Feature/Customers/AddressManagementTest.php`: lists addresses, creates, updates, deletes, sets default, validates required fields, prevents managing other customer's addresses (7 tests)

### Browser Verification

- [ ] **P6-BV1** -- Register new customer, verify redirect to account dashboard
- [ ] **P6-BV2** -- Login as existing customer, view order history
- [ ] **P6-BV3** -- Add/edit/delete address in address book
- [ ] **P6-BV4** -- Verify customer cannot access another customer's order via URL manipulation

---

## Phase 7: Admin Panel

**Priority:** MEDIUM -- merchant management interface.
**Specs:** 03-ADMIN-UI.md (all sections), 02-API-ROUTES.md (Section 1.2), 09-IMPLEMENTATION-ROADMAP.md (Steps 7.1-7.5)

### Technical Specification Summary

Full admin panel with Livewire v4 + Flux UI Free. Layout shell: fixed sidebar (256px desktop, overlay mobile), top bar (store selector, user profile, notifications), breadcrumbs, toast notifications. Dashboard: KPI tiles (sales, orders, AOV, visitors with period comparison), orders chart (Chart.js via Alpine.js), top products table, conversion funnel. Products: list with search/filter/sort/bulk actions/pagination, shared form component for create/edit (options builder, variant matrix, media upload). Orders: list with status filters, detail with timeline/payments/fulfillment modal/refund modal. Collections, Customers, Discounts, Settings (General/Domains/Shipping/Taxes/Checkout/Notifications), Themes, Pages, Navigation, Analytics, Search Settings, Apps, Developers. Dark mode via localStorage preference.

### Development Tasks

- [ ] **P7-T1** -- Admin layout: `resources/views/livewire/admin/layout/app.blade.php`, `Admin\Layout\Sidebar` (Flux brand/icon/separator, responsive overlay on mobile), `Admin\Layout\TopBar` (store selector dropdown, profile dropdown, notification badge), breadcrumbs component. Toast notification system via Livewire events + Alpine.js.
- [ ] **P7-T2** -- Dark mode: localStorage persistence, system preference default, apply before first paint to avoid flash
- [ ] **P7-T3** -- `Admin\Dashboard`: KPI tiles (4-col grid, period comparison badges), orders chart (Chart.js + Alpine.js), top products table (5 rows), conversion funnel (horizontal bars). Date range filter (Today/7d/30d/Custom).
- [ ] **P7-T4** -- `Admin\Products\Index`: product list with search (300ms debounce), status/type filters, sortable columns (title, inventory, updated_at), bulk actions (archive, delete, set active), pagination. Checkbox selection. Delete confirmation modal. Empty state with CTA.
- [ ] **P7-T5** -- `Admin\Products\Form`: shared create/edit form. Title, description (rich text), status, vendor, type, tags, handle. Options builder (add/remove options and values). Variant matrix auto-generation (price, SKU, barcode, weight, inventory per variant). Media upload (drag-drop, reorder, alt text). Collection picker.
- [ ] **P7-T6** -- `Admin\Orders\Index`: order list with status filters, search by order number/email, date range. `Admin\Orders\Show`: order detail with timeline, line items, payment info, fulfillment modal (select lines/quantities, tracking), refund modal (amount, reason, restock checkbox). Bank transfer confirm payment button.
- [ ] **P7-T7** -- `Admin\Collections\Index` and `Admin\Collections\Form`: collection list, shared create/edit form with product picker and reorder
- [ ] **P7-T8** -- `Admin\Customers\Index` and `Admin\Customers\Show`: customer list with search, customer detail (info, orders, addresses)
- [ ] **P7-T9** -- `Admin\Discounts\Index` and `Admin\Discounts\Form`: discount list with status/type filters, shared create/edit form (code, type, value, dates, usage limits, minimum purchase rules)
- [ ] **P7-T10** -- `Admin\Settings\Index`: tabbed settings (General, Domains, Shipping, Taxes, Checkout, Notifications). `Admin\Settings\Shipping`: shipping zones CRUD with rates per zone. `Admin\Settings\Taxes`: tax mode toggle, manual rate configuration.
- [ ] **P7-T11** -- `Admin\Themes\Index`: theme cards with Publish/Duplicate/Delete actions. `Admin\Themes\Editor`: left sections, center preview, right settings panel.
- [ ] **P7-T12** -- `Admin\Pages\Index` and `Admin\Pages\Form`: page list, shared create/edit with rich text editor
- [ ] **P7-T13** -- `Admin\Navigation\Index`: menu management with drag-and-drop item ordering
- [ ] **P7-T14** -- `Admin\Inventory\Index`: inventory management list, filter items, adjust quantities
- [ ] **P7-T15** -- `Admin\Analytics\Index`: sales chart, traffic, funnel visualization, date range filter
- [ ] **P7-T16** -- `Admin\Search\Settings`: synonyms, stop words, reindex button
- [ ] **P7-T17** -- `Admin\Apps\Index` and `Admin\Apps\Show`: app directory and installed app detail
- [ ] **P7-T18** -- `Admin\Developers\Index`: API token management (create/revoke Sanctum tokens), webhook subscription management (CRUD)
- [ ] **P7-T19** -- Admin Product API: `GET/POST /api/admin/v1/stores/{storeId}/products`, `PUT/DELETE .../products/{id}`. Sanctum auth with ability checks. Eloquent API Resources.
- [ ] **P7-T20** -- Form requests: `StoreProductRequest`, `UpdateProductRequest`, `StoreCollectionRequest`, `UpdateCollectionRequest`, `StoreDiscountRequest`, `UpdateDiscountRequest`, `StorePageRequest`, `UpdatePageRequest`, `StoreShippingZoneRequest`, `StoreShippingRateRequest`, `UpdateTaxSettingsRequest`

**Dependencies:** P5 (Orders) must be complete.

### Code Review Checkpoint

- [ ] **P7-CR1** -- Verify all admin routes are protected by auth + store.resolve + role.check middleware
- [ ] **P7-CR2** -- Verify role-based access controls on all admin actions
- [ ] **P7-CR3** -- Verify toast notifications fire on successful actions
- [ ] **P7-CR4** -- Verify responsive layout (sidebar overlay on mobile)
- [ ] **P7-CR5** -- Verify form validations show proper error messages
- [ ] **P7-CR6** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P7-TEST1** -- `tests/Feature/Admin/DashboardTest.php`: renders dashboard, shows correct KPIs, restricts to authenticated, date range filtering (4 tests)
- [ ] **P7-TEST2** -- `tests/Feature/Admin/ProductManagementTest.php`: list with pagination, create via form, edit via form, bulk archive, upload media, manage variants, role restrictions (staff can create not delete) (8 tests)
- [ ] **P7-TEST3** -- `tests/Feature/Admin/OrderManagementTest.php`: list with status filter, show detail, create fulfillment, process refund, role restrictions (5 tests)
- [ ] **P7-TEST4** -- `tests/Feature/Admin/DiscountManagementTest.php`: list, create percent, create fixed, validate code uniqueness, edit, disable (6 tests)
- [ ] **P7-TEST5** -- `tests/Feature/Admin/SettingsTest.php`: renders settings, update general, configure shipping zones, configure tax, restrict to owner/admin, manage domains (6 tests)
- [ ] **P7-TEST6** -- `tests/Feature/Api/AdminProductApiTest.php`: list with auth, create, update, delete draft, require write-products, reject without token, paginate (7 tests)

### Browser Verification

- [ ] **P7-BV1** -- Login as admin, verify dashboard renders with KPI tiles and chart
- [ ] **P7-BV2** -- Navigate through all sidebar sections, verify each loads
- [ ] **P7-BV3** -- Create a product with variants and media, verify in product list
- [ ] **P7-BV4** -- View order detail, create fulfillment with tracking
- [ ] **P7-BV5** -- Create and apply a discount code
- [ ] **P7-BV6** -- Configure shipping zones and tax settings
- [ ] **P7-BV7** -- Verify mobile sidebar overlay behavior

---

## Phase 8: Search

**Priority:** LOW -- enhances product discovery.
**Specs:** 01-DATABASE-SCHEMA.md (search tables), 05-BUSINESS-LOGIC.md (Section 13), 09-IMPLEMENTATION-ROADMAP.md (Steps 8.1-8.3)

### Technical Specification Summary

SQLite FTS5 virtual table for full-text search on products (title, description, vendor, product_type, tags). SearchService with store-scoped queries, autocomplete (prefix matching), sync/remove operations. ProductObserver auto-syncs FTS5 index on product create/update/delete. Search query logging for analytics. Storefront search UI: modal with autocomplete, full results page with filters (vendor, price range, collection), sort (relevance, price, newest), pagination.

### Development Tasks

- [ ] **P8-T1** -- Migrations: `create_search_settings_table`, `create_search_queries_table`, FTS5 virtual table migration (raw SQL for `products_fts`)
- [ ] **P8-T2** -- Models: `SearchSettings`, `SearchQuery` with factories/seeders
- [ ] **P8-T3** -- `App\Services\SearchService`: search (FTS5 query with store scoping, pagination), autocomplete (prefix matching, configurable limit), syncProduct (upsert FTS5), removeProduct (delete FTS5)
- [ ] **P8-T4** -- `App\Observers\ProductObserver`: calls SearchService::syncProduct on create/update, removeProduct on delete. Register in `AppServiceProvider`.
- [ ] **P8-T5** -- Storefront components: `Storefront\Search\Modal` (autocomplete), `Storefront\Search\Index` (full results with filters/sort/pagination)
- [ ] **P8-T6** -- `Admin\Search\Settings`: synonyms, stop words, reindex button

**Dependencies:** P2 (Catalog) must be complete.

### Code Review Checkpoint

- [ ] **P8-CR1** -- Verify FTS5 virtual table creation and sync works
- [ ] **P8-CR2** -- Verify search is scoped to current store
- [ ] **P8-CR3** -- Verify autocomplete returns results quickly
- [ ] **P8-CR4** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P8-TEST1** -- `tests/Feature/Search/SearchTest.php`: returns matching products, scopes to store, empty for no matches, logs query, paginates (5 tests)
- [ ] **P8-TEST2** -- `tests/Feature/Search/AutocompleteTest.php`: returns matching prefix, limits results, handles short prefix (3 tests)

### Browser Verification

- [ ] **P8-BV1** -- Type in search modal, verify autocomplete suggestions appear
- [ ] **P8-BV2** -- Submit search, verify results page with filters and pagination
- [ ] **P8-BV3** -- Search for non-existent term, verify empty state

---

## Phase 9: Analytics

**Priority:** LOW -- reporting and insights.
**Specs:** 01-DATABASE-SCHEMA.md (analytics tables), 05-BUSINESS-LOGIC.md (Section 14), 09-IMPLEMENTATION-ROADMAP.md (Steps 9.1-9.2)

### Technical Specification Summary

Raw analytics events (page_view, product_view, add_to_cart, remove_from_cart, checkout_started, checkout_completed, search) stored in analytics_events table. Daily aggregation job rolls up into analytics_daily table (orders_count, revenue_amount, aov_amount, visits_count, add_to_cart_count, checkout_started_count). AnalyticsService tracks events and reads aggregated data. Admin analytics dashboard with charts and date range filtering.

### Development Tasks

- [ ] **P9-T1** -- Migrations: `create_analytics_events_table`, `create_analytics_daily_table` (composite PK: store_id + date)
- [ ] **P9-T2** -- Models: `AnalyticsEvent`, `AnalyticsDaily` with factories/seeders. Apply BelongsToStore.
- [ ] **P9-T3** -- `App\Services\AnalyticsService`: track (insert raw event), getDailyMetrics (read aggregated data by date range)
- [ ] **P9-T4** -- `App\Jobs\AggregateAnalytics`: runs daily via `routes/console.php`, aggregates raw events into analytics_daily, calculates counts and revenue. Idempotent (re-running does not double values).
- [ ] **P9-T5** -- Wire up event tracking: page views, product views, add-to-cart, search queries, checkout events
- [ ] **P9-T6** -- `Admin\Analytics\Index`: sales chart, traffic, funnel visualization, date range filter (if not already built in P7-T15)

**Dependencies:** P5 (Orders) must be complete (analytics depend on order data).

### Code Review Checkpoint

- [ ] **P9-CR1** -- Verify aggregation is idempotent
- [ ] **P9-CR2** -- Verify events are scoped to current store
- [ ] **P9-CR3** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P9-TEST1** -- `tests/Feature/Analytics/EventIngestionTest.php`: tracks page_view, tracks add_to_cart, scopes to store, includes session_id, includes customer_id (5 tests)
- [ ] **P9-TEST2** -- `tests/Feature/Analytics/AggregationTest.php`: aggregates daily metrics, calculates revenue/AOV, runs idempotently (3 tests)

### Browser Verification

- [ ] **P9-BV1** -- Visit admin analytics page, verify charts render
- [ ] **P9-BV2** -- Change date range, verify data updates

---

## Phase 10: Apps and Webhooks

**Priority:** LOW -- extensibility.
**Specs:** 01-DATABASE-SCHEMA.md (apps/webhooks tables), 05-BUSINESS-LOGIC.md (Section 15), 09-IMPLEMENTATION-ROADMAP.md (Steps 10.1-10.2)

### Technical Specification Summary

Apps with installations per store. OAuth clients/tokens (stubbed for now). Webhook subscriptions per store with HMAC-SHA256 signed delivery. DeliverWebhook job with exponential backoff retry (1min, 5min, 30min, 2h, 12h -- 6 total attempts). Circuit breaker: pauses subscription after 5 consecutive failures. Webhook headers: X-Platform-Signature, X-Platform-Event, X-Platform-Delivery-Id, X-Platform-Timestamp.

### Development Tasks

- [ ] **P10-T1** -- Migrations: `create_apps_table`, `create_app_installations_table`, `create_oauth_clients_table`, `create_oauth_tokens_table`, `create_webhook_subscriptions_table`, `create_webhook_deliveries_table`
- [ ] **P10-T2** -- Models with factories/seeders: `App`, `AppInstallation`, `OauthClient`, `OauthToken`, `WebhookSubscription`, `WebhookDelivery`. Apply BelongsToStore on WebhookSubscription.
- [ ] **P10-T3** -- `App\Services\WebhookService`: dispatch (find matching subscriptions, queue delivery jobs), sign (HMAC-SHA256), verify (incoming signatures)
- [ ] **P10-T4** -- `App\Jobs\DeliverWebhook`: HTTP POST with JSON payload, HMAC signature header, retry with exponential backoff [60, 300, 1800, 7200, 43200], records response in webhook_deliveries, circuit breaker (pause after 5 consecutive failures)
- [ ] **P10-T5** -- Wire up webhook dispatching: listen for OrderCreated, OrderPaid, OrderFulfilled, OrderCancelled, OrderRefunded events and dispatch webhooks
- [ ] **P10-T6** -- Admin UI: `Admin\Apps\Index`, `Admin\Apps\Show`, `Admin\Developers\Index` (webhook subscription CRUD, API token management)

**Dependencies:** P5 (Orders) must be complete (webhooks fire on order events).

### Code Review Checkpoint

- [ ] **P10-CR1** -- Verify HMAC signature generation and verification
- [ ] **P10-CR2** -- Verify retry backoff configuration
- [ ] **P10-CR3** -- Verify circuit breaker pauses subscription correctly
- [ ] **P10-CR4** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P10-TEST1** -- `tests/Feature/Webhooks/WebhookDeliveryTest.php`: delivers to subscribed URL, signs payload, retries on failure, fails after max retries, pauses after circuit breaker (5 tests)
- [ ] **P10-TEST2** -- `tests/Feature/Webhooks/WebhookSignatureTest.php`: generates valid HMAC, verifies valid signature, rejects tampered payload, rejects incorrect secret (4 tests)

### Browser Verification

- [ ] **P10-BV1** -- Visit admin developers page, verify webhook subscription management renders
- [ ] **P10-BV2** -- Create API token, verify it appears in the list

---

## Phase 11: Polish

**Priority:** LOW but important for completeness.
**Specs:** 09-IMPLEMENTATION-ROADMAP.md (Phase 11), 04-STOREFRONT-UI.md (accessibility/responsive sections), 07-SEEDERS-AND-TEST-DATA.md

### Technical Specification Summary

Final polish pass: accessibility audit (skip links, ARIA labels, focus management in modals), responsive testing at all breakpoints (sm/md/lg/xl), dark mode completeness on all views, error pages styling, structured JSON logging, comprehensive seed data for demo stores.

### Development Tasks

- [ ] **P11-T1** -- Accessibility audit: add skip links to all pages, ARIA labels on interactive elements, focus management in modals, keyboard navigation support, heading hierarchy verification
- [ ] **P11-T2** -- Responsive testing: verify all pages render correctly at sm/md/lg/xl breakpoints. Fix any layout issues found.
- [ ] **P11-T3** -- Dark mode completeness: audit all storefront and admin views for `dark:` variants. Ensure no unstyled elements in dark mode.
- [ ] **P11-T4** -- Error pages: ensure 404 and 503 pages are styled to match storefront theme (already created in P3-T9, verify consistency)
- [ ] **P11-T5** -- Structured logging: verify JSON channel in `config/logging.php` works, add structured log entries for key operations (orders, payments, auth)
- [ ] **P11-T6** -- Comprehensive seeders: finalize `DatabaseSeeder` orchestration per 07-SEEDERS-AND-TEST-DATA.md. All 16 seeders in correct dependency order. Ensure seed data supports all Playwright E2E tests.

**Dependencies:** P6 (Customer Accounts), P7 (Admin Panel), P8 (Search), P9 (Analytics), P10 (Apps/Webhooks) must all be complete.

### Code Review Checkpoint

- [ ] **P11-CR1** -- Run accessibility scan on all major pages
- [ ] **P11-CR2** -- Verify seed data matches spec 07 requirements exactly
- [ ] **P11-CR3** -- Verify `php artisan migrate:fresh --seed` completes without errors
- [ ] **P11-CR4** -- Run `vendor/bin/pint --dirty`

### Pest Tests

- [ ] **P11-TEST1** -- Verify `DatabaseSeeder` runs without errors
- [ ] **P11-TEST2** -- Verify seeded data counts match spec expectations (20 products, 5 collections, 10 customers, 15 orders, etc.)

### Browser Verification

- [ ] **P11-BV1** -- Smoke test all major storefront pages at mobile viewport (375px)
- [ ] **P11-BV2** -- Smoke test all major storefront pages at desktop viewport (1440px)
- [ ] **P11-BV3** -- Toggle dark mode, verify all pages render correctly
- [ ] **P11-BV4** -- Verify skip links work with keyboard navigation
- [ ] **P11-BV5** -- Verify 404 and 503 error pages display correctly

---

## Phase 12: Full Test Suite

**Priority:** Final phase -- runs after all implementation is complete.
**Specs:** 09-IMPLEMENTATION-ROADMAP.md (Phase 12), 08-PLAYWRIGHT-E2E-PLAN.md

### Technical Specification Summary

Full verification: run all unit tests (6 files), all feature tests (28+ files), all browser tests (18 files / 143 tests), code style check, fresh migration with seeding, and manual smoke tests for storefront and admin.

### Development Tasks

- [ ] **P12-T1** -- Browser test infrastructure: configure Pest v4 browser testing in `tests/Pest.php`, set up `.env.testing` (APP_URL=http://acme-fashion.test, DB_CONNECTION=sqlite, MAIL_MAILER=array, QUEUE_CONNECTION=sync)
- [ ] **P12-T2** -- Browser smoke tests: `tests/Browser/SmokeTest.php` (10 tests -- all major pages load without JS errors)
- [ ] **P12-T3** -- Browser admin auth tests: `tests/Browser/Admin/AuthenticationTest.php` (10 tests)
- [ ] **P12-T4** -- Browser admin product tests: `tests/Browser/Admin/ProductManagementTest.php` (7 tests)
- [ ] **P12-T5** -- Browser admin order tests: `tests/Browser/Admin/OrderManagementTest.php` (11 tests)
- [ ] **P12-T6** -- Browser admin discount tests: `tests/Browser/Admin/DiscountManagementTest.php` (6 tests)
- [ ] **P12-T7** -- Browser admin settings tests: `tests/Browser/Admin/SettingsTest.php` (7 tests)
- [ ] **P12-T8** -- Browser storefront browsing tests: `tests/Browser/Storefront/BrowsingTest.php` (15 tests)
- [ ] **P12-T9** -- Browser cart flow tests: `tests/Browser/Storefront/CartTest.php` (12 tests)
- [ ] **P12-T10** -- Browser checkout flow tests: `tests/Browser/Storefront/CheckoutTest.php` (13 tests)
- [ ] **P12-T11** -- Browser customer account tests: `tests/Browser/Storefront/CustomerAccountTest.php` (12 tests)
- [ ] **P12-T12** -- Browser inventory enforcement tests: `tests/Browser/Storefront/InventoryTest.php` (4 tests)
- [ ] **P12-T13** -- Browser tenant isolation tests: `tests/Browser/Storefront/TenantIsolationTest.php` (5 tests)
- [ ] **P12-T14** -- Browser responsive tests: `tests/Browser/Storefront/ResponsiveTest.php` (8 tests)
- [ ] **P12-T15** -- Browser accessibility tests: `tests/Browser/Storefront/AccessibilityTest.php` (11 tests)
- [ ] **P12-T16** -- Browser admin collections tests: `tests/Browser/Admin/CollectionManagementTest.php` (3 tests)
- [ ] **P12-T17** -- Browser admin customers tests: `tests/Browser/Admin/CustomerManagementTest.php` (3 tests)
- [ ] **P12-T18** -- Browser admin pages tests: `tests/Browser/Admin/PageManagementTest.php` (3 tests)
- [ ] **P12-T19** -- Browser admin analytics tests: `tests/Browser/Admin/AnalyticsTest.php` (3 tests)
- [ ] **P12-T20** -- Run full unit + feature test suite: `php artisan test` -- all tests must pass
- [ ] **P12-T21** -- Run all browser tests: verify all 143 browser tests pass
- [ ] **P12-T22** -- Run code style: `vendor/bin/pint` -- confirm conformance
- [ ] **P12-T23** -- Fresh migration with seeding: `php artisan migrate:fresh --seed` -- confirm no errors
- [ ] **P12-T24** -- Manual smoke: visit storefront, navigate products, add to cart, checkout
- [ ] **P12-T25** -- Manual smoke: visit admin login, authenticate, manage products and orders

**Dependencies:** P11 (Polish) must be complete.

### Code Review Checkpoint

- [ ] **P12-CR1** -- All unit tests pass (6 files, ~54 tests)
- [ ] **P12-CR2** -- All feature tests pass (28+ files, ~200+ tests)
- [ ] **P12-CR3** -- All browser tests pass (18 files, 143 tests)
- [ ] **P12-CR4** -- Code style passes (`vendor/bin/pint --test`)
- [ ] **P12-CR5** -- Fresh migration + seed succeeds without errors

### Browser Verification

- [ ] **P12-BV1** -- Full storefront smoke test: home -> collection -> product -> add to cart -> checkout -> confirmation
- [ ] **P12-BV2** -- Full admin smoke test: login -> dashboard -> products -> create product -> orders -> view order -> fulfill -> settings
- [ ] **P12-BV3** -- Customer account smoke test: register -> login -> view orders -> manage addresses -> logout

---

## Task Summary

| Phase | Tasks | Tests (Files) | Tests (Count) | Browser Verifications |
|-------|-------|---------------|---------------|----------------------|
| P1: Foundation | 11 | 6 | ~36 | 3 |
| P2: Catalog | 8 | 6 | ~48 | 2 |
| P3: Themes & Storefront | 9 | 3 | ~10 | 5 |
| P4: Cart/Checkout/Discounts | 14 | 13 | ~103 | 5 |
| P5: Payments/Orders/Fulfillment | 12 | 8 | ~60 | 4 |
| P6: Customer Accounts | 5 | 2 | ~13 | 4 |
| P7: Admin Panel | 20 | 6 | ~36 | 7 |
| P8: Search | 6 | 2 | ~8 | 3 |
| P9: Analytics | 6 | 2 | ~8 | 2 |
| P10: Apps/Webhooks | 6 | 2 | ~9 | 2 |
| P11: Polish | 6 | 2 | ~4 | 5 |
| P12: Full Test Suite | 25 | -- | 143 (browser) | 3 |
| **Total** | **128** | **52** | **~478** | **45** |
