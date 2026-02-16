# Code Review Round 1

## Critical Findings

1. **API controllers have zero authorization checks.** `ProductController` and `OrderController` in `app/Http/Controllers/Api/Admin/` never call `$this->authorize()`, `Gate::check()`, or reference any policy. Any authenticated user with a Sanctum token can CRUD any product or order for any store. The policies exist but are completely unused in the API layer.

2. **Admin Livewire components have zero authorization checks.** None of the Livewire components in `app/Livewire/Admin/` invoke `$this->authorize()` or check policies. Once authenticated (as any `User`, regardless of store role), a user has full access to create fulfillments, issue refunds, manage products, change settings, etc. The 10 policies are dead code.

3. **Storefront API routes lack store-scoping middleware.** The `routes/api.php` storefront routes (`/api/storefront/v1/carts`, `/api/storefront/v1/checkouts`) do not apply the `store.resolve` middleware. The `CartController::store()` fetches the store from `$request->attributes->get('store')` which will be `null` since no middleware sets it. Additionally, checkout routes use model binding on `Checkout` which bypasses the global store scope if `current_store` is not bound.

4. **Unescaped HTML output creates stored XSS vectors.** `{!! $product->description_html !!}`, `{!! $page->content !!}`, and `{!! $collection->description_html !!}` render admin-supplied HTML without sanitization. Any admin user (or attacker who compromises an admin account) can inject arbitrary JavaScript that executes for all storefront visitors.

5. **Order number generation has a race condition.** `OrderService::generateOrderNumber()` reads the last order number and increments it without a lock or unique constraint enforcement in the query. Under concurrent checkouts, two orders can receive the same order number.

## Major Findings

1. **No Form Request classes anywhere.** All validation is inline in controllers and Livewire components, violating the project's own Laravel conventions (CLAUDE.md specifies: "Always create Form Request classes for validation"). This makes validation logic untestable in isolation and scattered across 15+ files.

2. **Admin API routes have no store ownership verification.** `OrderController::show(Store $store, Order $order)` does not verify that `$order->store_id === $store->id`. A user can pass any `store` and `order` ID combination and access cross-store order data. Same issue in `ProductController::update/destroy`.

3. **Cart API allows access to any cart by ID.** `CartController::show(Cart $cart)` loads any cart regardless of session or store ownership. An attacker can enumerate cart IDs to view other customers' cart contents including variant selections and pricing.

4. **Checkout API allows manipulation of any checkout by ID.** `CheckoutController::setAddress`, `applyDiscount`, `pay` all accept a `Checkout` via route model binding with no ownership verification. An attacker could complete someone else's checkout or change their shipping address.

5. **`description_html` stored without sanitization on input.** The `Product` model has `description_html` in `$fillable` and the admin product form saves it directly from user input without HTML purification. Combined with the unescaped output, this is the full XSS chain.

6. **`withoutGlobalScopes()` used extensively to bypass tenant isolation.** 16 files call `withoutGlobalScopes()`. While some usages are legitimate (e.g., jobs processing all stores), the Checkout Livewire component (`Storefront/Checkout/Show.php`) loads checkouts with `withoutGlobalScopes()` using only an ID from a public Livewire property. A malicious client could manipulate the `checkoutId` property to access/complete another store's checkout.

7. **Refund in admin UI bypasses `RefundService`.** `Admin\Orders\Show::createRefund()` creates refunds directly via Eloquent instead of using `RefundService`, which handles payment provider refunds, restock logic, and events. The admin refund creates a database record but never actually refunds money through the payment provider.

8. **Sanctum not installed but admin API routes depend on it.** The two admin API test files are skipped because Sanctum is not installed, meaning the `auth:sanctum` middleware on admin API routes likely falls back to default behavior. This needs verification -- the admin API may be unprotected.

9. **`OrderService::createFromCheckout` does not validate payment failure before creating the order.** If the payment fails (e.g., card declined), the order is still created in the database with lines, inventory is already decremented/reserved, and only then is the payment result checked. The order persists even on payment failure with no rollback of inventory changes (the transaction does not throw).

## Minor Findings

1. **Product `defaultVariant()` returns `HasMany` instead of `HasOne`.** The relationship `public function defaultVariant(): HasMany` should be a `HasOne` since there is conceptually one default variant per product.

2. **Checkout `Show::mount()` creates a new checkout on every page load.** Each render of the checkout page calls `$checkoutService->createFromCart($cart)`, creating abandoned checkout records. There is no reuse of existing checkouts.

3. **`CartService::updateLineQuantity` returns deleted model when qty <= 0.** When quantity is 0 or negative, the line is deleted but the method still returns the (now-deleted) `CartLine` instance. Callers may attempt to use stale data.

4. **578 uncovered classes in deptrac.** While there are 0 violations, 578 uncovered classes means most of the codebase is not analyzed by the architectural rules. The layers should be expanded to cover all app classes.

5. **Hardcoded credit card number in checkout form.** `Storefront/Checkout/Show.php` has `public string $cardNumber = '4242424242424242';` as a default value. This is a test convenience that should not ship in production code.

6. **`PricingEngine::calculate()` uses `TaxSettings::find($checkout->store_id)`.** This assumes `TaxSettings` primary key equals `store_id`, which is fragile. Should use a scoped query or relationship.

7. **No CSRF protection on storefront API routes.** The API routes in `routes/api.php` do not have CSRF or any authentication for storefront endpoints, relying solely on rate limiting (120/min). This is standard for APIs but combined with the lack of ownership checks (finding #3-4), it creates an easily exploitable surface.

8. **`Product` handle uniqueness not scoped to store.** In `ProductController::store`, the validation rule `'handle' => 'required|string|max:255|unique:products,handle'` checks global uniqueness rather than per-store uniqueness. Two stores cannot have a product with the same handle.

9. **No pagination on several admin list queries.** Some Livewire admin components load all records without pagination limits, which could cause performance issues with large datasets.

10. **Logout route uses inline closure instead of controller.** The `storefront.account.logout` route in `web.php` uses an anonymous closure, which is not serializable for route caching.

## Test Coverage Assessment

The test plan (`specs/testplan.md`) defines 143 E2E browser tests across 18 suites. These are Playwright-based acceptance tests designed for live deployed shops -- they are **not** the unit/feature tests in the `tests/` directory.

**Unit/Feature test coverage (210 tests passing):**

| Domain | Test Files | Status |
|--------|-----------|--------|
| Products (CRUD, inventory, collections, variants, media) | 5 files | Covered |
| Orders (creation, fulfillment, refund) | 3 files | Covered |
| Payments (service, mock provider, bank transfer) | 3 files | Covered |
| Cart (service, API) | 2 files | Covered |
| Checkout (flow, state, discount, shipping, tax, pricing) | 6 files | Covered |
| Auth (admin, customer, sanctum) | 3 files | Covered |
| Admin UI (products, orders, discounts, dashboard, settings) | 5 files | Covered |
| Search (search, autocomplete) | 2 files | Covered |
| Tenancy (isolation, resolution) | 2 files | Covered |
| Analytics (events, aggregation) | 2 files | Covered |
| Webhooks (delivery, signature) | 2 files | Covered |
| Storefront (components, login, inventory status) | 3 files | Covered |
| Customers (account, addresses) | 2 files | Covered |
| API (storefront cart, storefront checkout, admin products, admin orders) | 4 files | 2 skipped (Sanctum) |

**Gaps identified:**

- Admin API tests are effectively non-functional (skipped due to missing Sanctum)
- No tests for authorization/policy enforcement (because policies are never called)
- No tests for concurrent order number generation
- No tests for cross-store data access attempts via API
- No tests for XSS prevention/HTML sanitization
- No tests for checkout ownership verification

## Overall Assessment

**FAIL** -- The codebase is not production-ready.

The application has well-structured models, clean service layer separation, proper enum usage, passing static analysis (PHPStan level max), and zero deptrac violations. The test suite is substantial at 210 tests with good domain coverage.

However, there are fundamental security deficiencies that make this unsuitable for production deployment:

1. **Authorization is entirely absent from the execution path.** Policies exist but are never invoked. Any authenticated user has unrestricted access to all admin functionality across all stores.

2. **API endpoints have no ownership verification.** Cart, checkout, order, and product APIs allow cross-tenant and cross-user data access through simple ID enumeration.

3. **Stored XSS is possible** through admin-controlled HTML fields rendered unescaped on the storefront.

4. **The refund flow in the admin UI is broken** -- it records refunds without processing them through the payment provider.

These are not edge cases but core security properties that must be addressed before any deployment.
