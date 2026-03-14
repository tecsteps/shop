# Manual Test Plan

## Phase 1: Foundation

### Admin Authentication

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.1 | Admin login page renders at /admin/login | Login form displays with email, password, and remember me checkbox | 06-AUTH 1.1 | pass |
| 1.2 | Admin login with valid credentials (admin@acme.test / password) | Redirects to /admin dashboard, session is authenticated | 06-AUTH 1.1 | pass |
| 1.3 | Admin login with invalid password | Shows generic "Invalid credentials" error, does not reveal which field is wrong | 06-AUTH 1.1 | pass |
| 1.4 | Admin login with non-existent email | Shows same generic "Invalid credentials" error | 06-AUTH 1.1 | pass |
| 1.5 | Admin login rate limiting (6th attempt in 1 min) | Shows "Too many attempts. Try again in X seconds." message | 06-AUTH 1.1 | pending |
| 1.6 | Admin logout via POST /admin/logout | Session invalidated, CSRF token regenerated, redirected to /admin/login | 06-AUTH 1.1 | pass |
| 1.7 | Unauthenticated admin access to /admin | Redirected to /admin/login | 06-AUTH 3.2 | pass |
| 1.8 | Remember me checkbox sets long-lived cookie | Sets remember_web_{hash} cookie on login | 06-AUTH 1.1 | pending |
| 1.9 | Session regeneration on login | Session ID changes after successful login to prevent fixation | 06-AUTH 1.1 | pending |

### Admin Password Reset

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.10 | Forgot password page renders at /admin/forgot-password | Form with email field displays | 06-AUTH 1.1, 02-API 1.1 | pending |
| 1.11 | Submit forgot password with existing email | Generic message "If that email exists, we sent a reset link." shown | 06-AUTH 1.1 | pending |
| 1.12 | Submit forgot password with non-existent email | Same generic message shown (no email enumeration) | 06-AUTH 1.1 | pending |
| 1.13 | Reset password form renders at /admin/reset-password/{token} | Form with email, password, password_confirmation fields | 06-AUTH 1.1, 02-API 1.1 | pending |
| 1.14 | Reset password with valid token | Password updated, redirected to /admin/login with success flash | 06-AUTH 1.1 | pending |
| 1.15 | Reset password with expired token (>60 min) | Error message shown | 06-AUTH 1.1 | pending |
| 1.16 | Reset password throttle (2nd request within 60 sec) | Throttled, one reset email per 60 seconds per email | 06-AUTH 1.1 | pending |

### Customer Authentication

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.17 | Customer login page renders at /account/login | Login form displays with email and password fields | 06-AUTH 1.2, 02-API 1.3 | pass |
| 1.18 | Customer login with valid credentials | Redirects to /account, session authenticated via customer guard | 06-AUTH 1.2 | pass |
| 1.19 | Customer login with invalid credentials | Shows generic "Invalid credentials" error | 06-AUTH 1.2 | pass |
| 1.20 | Customer login is store-scoped (credentials from different store rejected) | Login fails for customer belonging to a different store | 06-AUTH 1.2 | pending |
| 1.21 | Customer registration page renders at /account/register | Form with name, email, password, password_confirmation, marketing opt-in | 06-AUTH 1.2, 02-API 1.3 | pass |
| 1.22 | Customer registration with valid data | Account created, auto-logged in, redirected to /account | 06-AUTH 1.2 | pass |
| 1.23 | Customer registration with duplicate email in same store | Validation error on email field | 06-AUTH 1.2 | pass |
| 1.24 | Customer registration with same email in different store | Registration succeeds (multi-tenant isolation) | 06-AUTH 1.2 | pending |
| 1.25 | Customer registration password validation (min 8, confirmed) | Validation errors for short or unconfirmed passwords | 06-AUTH 1.2 | pending |
| 1.26 | Customer logout | Session invalidated, redirected to /account/login | 06-AUTH 1.2 | pending |
| 1.27 | Customer login rate limiting (6th attempt in 1 min) | Shows "Too many attempts" message | 06-AUTH 1.2 | pending |
| 1.28 | Unauthenticated customer access to /account | Redirected to /account/login with intended URL stored | 06-AUTH 3.3 | pending |
| 1.29 | Customer login redirect to intended URL after auth | After login, redirected to originally requested page | 06-AUTH 3.3 | pending |

### Customer Password Reset

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.30 | Customer forgot password page renders at /forgot-password | Form with email field displays | 06-AUTH 1.2 | pending |
| 1.31 | Submit customer forgot password with existing email | Generic response shown, reset email sent | 06-AUTH 1.2 | pending |
| 1.32 | Submit customer forgot password with non-existent email | Same generic response (no enumeration) | 06-AUTH 1.2 | pending |
| 1.33 | Customer reset password with valid token | Password updated, can log in with new password | 06-AUTH 1.2 | pending |
| 1.34 | Customer password reset tokens are store-scoped | Token from one store cannot reset password in another store | 06-AUTH 1.2 | pending |

### Store Resolution

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.35 | Storefront resolves from hostname (acme-fashion.test) | Store bound as current_store singleton, storefront pages render | 05-BL 1.1 | pass |
| 1.36 | Secondary domain resolves to same store (shop.test) | Both seeded domains resolve to Acme Fashion store | 05-BL 1.1 | pass |
| 1.37 | Unknown hostname returns 404 | 404 page shown for unregistered hostnames | 05-BL 1.1, 06-AUTH 3.3 | pending |
| 1.38 | Suspended store returns 503 maintenance page | Storefront shows "This store is currently unavailable." | 05-BL 1.1 | pending |
| 1.39 | Admin resolves store from session (current_store_id) | After login, admin store context set correctly | 05-BL 1.1 | pending |
| 1.40 | Admin denied when user has no store_users record | 403 error with "You do not have access to this store." | 05-BL 1.1, 06-AUTH 3.3 | pending |
| 1.41 | Store domain resolution is cached (5-min TTL) | Subsequent requests use cached hostname-to-store mapping | 05-BL 1.1 | pending |
| 1.42 | currentStore variable shared with all Blade views | Views can access $currentStore after store resolution | 05-BL 1.1 | pending |

### Authorization (Policies and Gates)

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.43 | Owner can view store settings | Settings page accessible at /admin/settings | 06-AUTH 2.2 | pending |
| 1.44 | Owner can update store settings | Settings can be saved | 06-AUTH 2.2 | pending |
| 1.45 | Admin can view and update store settings | Same access as Owner for settings | 06-AUTH 2.2 | pending |
| 1.46 | Staff cannot access store settings | 403 response when navigating to /admin/settings | 06-AUTH 2.2 | pending |
| 1.47 | Support cannot access store settings | 403 response | 06-AUTH 2.2 | pending |
| 1.48 | Only Owner can delete store | Owner succeeds, Admin/Staff/Support get 403 | 06-AUTH 2.4 (StorePolicy) | pending |
| 1.49 | Support can view orders (read-only) | Orders list page accessible | 06-AUTH 2.2, 2.4 | pending |
| 1.50 | Support cannot update orders | 403 response on update action | 06-AUTH 2.2, 2.4 | pending |
| 1.51 | Support cannot cancel orders | 403 response on cancel action | 06-AUTH 2.2, 2.4 | pending |
| 1.52 | Staff can create/update products | Product creation and editing accessible | 06-AUTH 2.2, 2.4 | pending |
| 1.53 | Staff cannot delete/archive products | 403 response on delete/archive action | 06-AUTH 2.2, 2.4 | pending |
| 1.54 | Support cannot create products | 403 response on product creation | 06-AUTH 2.2, 2.4 | pending |
| 1.55 | Staff cannot access themes | 403 response for /admin/themes | 06-AUTH 2.2, 2.4 | pending |
| 1.56 | Staff cannot manage navigation | 403 response on navigation management | 06-AUTH 2.2, 2.5 | pending |
| 1.57 | Staff can view analytics | Analytics dashboard accessible | 06-AUTH 2.5 | pending |
| 1.58 | Support cannot view analytics | 403 response for analytics | 06-AUTH 2.5 | pending |

### Middleware (CheckStoreRole)

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.59 | CheckStoreRole middleware with matching role passes | Request proceeds to controller/component | 06-AUTH 3.3 | pending |
| 1.60 | CheckStoreRole middleware with non-matching role returns 403 | "Insufficient permissions" message | 06-AUTH 3.3 | pending |
| 1.61 | CheckStoreRole middleware with no store_users record returns 403 | "You do not have access to this store." message | 06-AUTH 3.3 | pending |
| 1.62 | CheckStoreRole attaches store_user to request attributes | Downstream code can read request->attributes->get('store_user') | 06-AUTH 3.3 | pending |

### API Authentication (Sanctum)

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.63 | API request with valid Bearer token authenticates | Request resolves the associated user | 06-AUTH 1.3 | pending |
| 1.64 | API request with invalid/revoked token returns 401 | Unauthenticated response | 06-AUTH 1.3 | pending |
| 1.65 | API request with expired token returns 401 | Token past expiration date rejected | 06-AUTH 1.3 | pending |
| 1.66 | Token with correct ability can access endpoint | 200 response for authorized scope | 06-AUTH 1.3 | pending |
| 1.67 | Token without required ability returns 403 | Forbidden response for missing scope | 06-AUTH 1.3 | pending |

### Rate Limiting

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.68 | Login rate limiter: 5 per minute per IP | 6th attempt within 1 min returns 429 | 06-AUTH 4.2 | pending |
| 1.69 | Admin API rate limiter: 60 per minute | 61st request returns 429 with Retry-After header | 06-AUTH 4.2 | pending |
| 1.70 | Storefront API rate limiter: 120 per minute | 121st request returns 429 | 06-AUTH 4.2 | pending |
| 1.71 | Rate limit response includes X-RateLimit-Limit and X-RateLimit-Remaining headers | Headers present on API responses | 06-AUTH 4.2 | pending |

### Security Controls

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.72 | CSRF token required on all web form submissions | 419 response without @csrf token | 06-AUTH 4.1 | pending |
| 1.73 | Livewire actions handle CSRF automatically | No manual CSRF needed for Livewire requests | 06-AUTH 4.1 | pending |
| 1.74 | API routes exempt from CSRF (token auth) | API requests without CSRF token succeed with Bearer token | 06-AUTH 4.1 | pending |
| 1.75 | Encrypted fields store ciphertext in database | payment raw_json, webhook signing_secret are opaque in DB | 06-AUTH 4.3 | pending |

### Database and Seeder Verification

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.76 | Fresh migration runs without errors | php artisan migrate:fresh succeeds | 01-DB | pending |
| 1.77 | All seeders run without errors | php artisan db:seed populates expected data | 07-SEEDERS | pending |
| 1.78 | Organization "Acme Corp" exists with correct billing_email | billing@acme.test | 07-SEEDERS | pending |
| 1.79 | Store "Acme Fashion" exists with handle acme-fashion, status active, currency EUR | Correct attributes seeded | 07-SEEDERS | pending |
| 1.80 | StoreDomain acme-fashion.test is primary, shop.test is secondary | Both domains linked to Acme Fashion store | 07-SEEDERS | pending |
| 1.81 | Admin user exists (admin@acme.test) with status active | User seeded correctly | 07-SEEDERS | pending |
| 1.82 | Admin user linked to Acme Fashion store with Owner role | store_users pivot populated | 07-SEEDERS | pending |
| 1.83 | StoreSettings exist for Acme Fashion with empty JSON | Default settings seeded | 07-SEEDERS | pending |
| 1.84 | Foreign key constraints enforced | Cannot insert store with non-existent organization_id | 01-DB | pending |

### Store Scoping (BelongsToStore Trait)

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.85 | StoreScope filters queries by current_store | Only records for bound store returned | 05-BL 1.2 | pending |
| 1.86 | StoreScope inactive when no current_store bound | All records returned (no filter applied) | 05-BL 1.2 | pending |
| 1.87 | BelongsToStore auto-sets store_id on creating | New model gets current_store id automatically | 05-BL 1.2 | pending |
| 1.88 | BelongsToStore does not override explicit store_id | Manually set store_id preserved on create | 05-BL 1.2 | pending |

### Configuration Verification

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 1.89 | SQLite configured with WAL mode and foreign keys | database.connections.sqlite has journal_mode=wal, foreign_key_constraints=true | 09-ROADMAP 1.1 | pending |
| 1.90 | Customer guard configured in config/auth.php | customer guard with session driver and customers provider exists | 06-AUTH 1.4 | pending |
| 1.91 | Customer password broker configured | customers broker pointing to customer_password_reset_tokens table | 06-AUTH 1.4 | pending |
| 1.92 | Session driver set to file | config('session.driver') returns 'file' | 09-ROADMAP 1.1 | pending |
| 1.93 | Cache driver set to file | config('cache.default') returns 'file' | 09-ROADMAP 1.1 | pending |
| 1.94 | Queue connection set to sync | config('queue.default') returns 'sync' | 09-ROADMAP 1.1 | pending |

## Phase 2: Catalog

### Product CRUD

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.1 | Create a product via ProductService with title only | Product created with auto-generated handle, draft status, default variant, and inventory item | 05-BL 2.1 | pending |
| 2.2 | Create a product with body_html, vendor, product_type, tags | All fields persisted correctly, body_html maps to description_html column | 05-BL 2.1, 01-DB | pending |
| 2.3 | Update a product title regenerates handle | Handle re-slugified when title changes, uniqueness preserved | 05-BL 2.1 | pending |
| 2.4 | Update a product without changing title keeps handle | Handle unchanged when only other fields modified | 05-BL 2.1 | pending |
| 2.5 | Delete a draft product with no order references | Product, variants, options, media, inventory all cascade-deleted | 05-BL 2.1 | pending |
| 2.6 | Delete a non-draft product is rejected | InvalidArgumentException thrown, product unchanged | 05-BL 2.1 | pending |
| 2.7 | Delete a product with order references is rejected | InvalidArgumentException thrown even if status is draft | 05-BL 2.1 | pending |
| 2.8 | List products filtered by status | Only products matching status filter returned | 05-BL 2.1 | pending |
| 2.9 | Search products by title | Products with matching title substring returned | 05-BL 2.1 | pending |

### Handle Generation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.10 | Handle generated from title via Str::slug | "Classic Cotton T-Shirt" becomes "classic-cotton-t-shirt" | 05-BL 2.1 | pending |
| 2.11 | Handle collision appends numeric suffix | Second product with same title gets handle-1, third gets handle-2 | 05-BL 2.1 | pending |
| 2.12 | Handle scoped to store | Same title in different stores generates same handle without suffix | 05-BL 2.1 | pending |
| 2.13 | Handle excludes current record on update | Updating own title back does not cause self-collision | 05-BL 2.1 | pending |
| 2.14 | Handle generation with special characters | Titles with accents, symbols produce clean slugs | 05-BL 2.1 | pending |

### Product Status Transitions

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.15 | Draft to Active with priced variant | Status set to active, published_at set to now | 05-BL 2.1 | pending |
| 2.16 | Draft to Active without priced variant rejected | InvalidArgumentException: needs at least one variant with price > 0 | 05-BL 2.1 | pending |
| 2.17 | Active to Archived | Status set to archived | 05-BL 2.1 | pending |
| 2.18 | Active to Draft (no order references) | Status reverted to draft | 05-BL 2.1 | pending |
| 2.19 | Active to Draft with order references rejected | InvalidArgumentException thrown | 05-BL 2.1 | pending |
| 2.20 | Archived to Draft | Status set to draft | 05-BL 2.1 | pending |
| 2.21 | Archived to Active with priced variant | Status set to active, published_at updated | 05-BL 2.1 | pending |
| 2.22 | Draft to Archived rejected (invalid transition) | InvalidArgumentException for disallowed transition | 05-BL 2.1 | pending |
| 2.23 | Same status transition is no-op | No error, no update when transitioning to current status | 05-BL 2.1 | pending |

### Variant Matrix Generation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.24 | Rebuild matrix with no options creates default variant | Single default variant with is_default=true | 05-BL 2.2 | pending |
| 2.25 | Rebuild matrix with one option (3 values) creates 3 variants | S, M, L each get a variant with inventory item | 05-BL 2.2 | pending |
| 2.26 | Rebuild matrix with two options creates cartesian product | Size(S/M/L) x Color(R/B) = 6 variants | 05-BL 2.2 | pending |
| 2.27 | Rebuild preserves existing variants with same option values | Price, SKU, inventory unchanged for matching variants | 05-BL 2.2 | pending |
| 2.28 | Rebuild creates new variants for added option values | Adding XL size creates new variants while preserving S/M/L | 05-BL 2.2 | pending |
| 2.29 | Rebuild removes orphaned variants (no order refs) | Variants for removed option values are deleted | 05-BL 2.2 | pending |
| 2.30 | Rebuild archives orphaned variants with order refs | Variants with order lines set to archived instead of deleted | 05-BL 2.2 | pending |
| 2.31 | Variant title auto-generated from option values | Title is "S / Red" for size S, color Red | 05-BL 2.2 | pending |
| 2.32 | Variant option values linked via pivot table | variant_option_values records created correctly | 01-DB | pending |

### Inventory Management

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.33 | Check availability with sufficient stock (deny policy) | Returns true when on_hand - reserved >= requested | 05-BL 2.3 | pending |
| 2.34 | Check availability with insufficient stock (deny policy) | Returns false when available < requested | 05-BL 2.3 | pending |
| 2.35 | Check availability always true with continue policy | Returns true regardless of stock level | 05-BL 2.3 | pending |
| 2.36 | Reserve inventory with sufficient stock | quantity_reserved incremented by requested amount | 05-BL 2.3 | pending |
| 2.37 | Reserve inventory with insufficient stock (deny) throws | InsufficientInventoryException with variant_id, requested, available | 05-BL 2.3 | pending |
| 2.38 | Reserve inventory with continue policy always succeeds | Reserved even when on_hand is 0 | 05-BL 2.3 | pending |
| 2.39 | Release reserved inventory | quantity_reserved decremented, capped at 0 | 05-BL 2.3 | pending |
| 2.40 | Commit inventory after payment | Both on_hand and reserved decremented | 05-BL 2.3 | pending |
| 2.41 | Restock inventory | quantity_on_hand incremented | 05-BL 2.3 | pending |
| 2.42 | quantityAvailable() returns on_hand minus reserved | Computed property correct after reserve/release cycles | 05-BL 2.3, 01-DB | pending |

### Collection Management

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.43 | Create collection with title, handle, status | Collection persisted with store_id auto-set | 05-BL 2.4 | pending |
| 2.44 | Collection handle unique per store | Duplicate handle in same store rejected, different store allowed | 01-DB | pending |
| 2.45 | Attach products to collection with position | collection_products pivot records created with position | 05-BL 2.4 | pending |
| 2.46 | Detach products from collection | Pivot records removed, products unchanged | 05-BL 2.4 | pending |
| 2.47 | Reorder products in collection | Position values updated in pivot | 05-BL 2.4 | pending |
| 2.48 | Collection status transitions (draft/active/archived) | Status changes apply correctly | 05-BL 2.4 | pending |
| 2.49 | Product belongs to multiple collections | Same product in T-Shirts and New Arrivals collections | 01-DB | pending |

### Media Upload and Processing

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.50 | Create product media record | Media record with type, url, alt_text, status=processing | 01-DB | pending |
| 2.51 | ProcessMediaUpload job sets status to ready on success | Media status transitions from processing to ready | 05-BL 2.5 | pending |
| 2.52 | ProcessMediaUpload job sets status to failed on error | Media status set to failed, error logged | 05-BL 2.5 | pending |
| 2.53 | ProcessMediaUpload job retries up to 3 times | $tries = 3 configured on job class | 05-BL 2.5 | pending |
| 2.54 | ProcessMediaUpload handles missing media record gracefully | Warning logged, no exception thrown | 05-BL 2.5 | pending |
| 2.55 | Media position ordering | Multiple media per product ordered by position | 01-DB | pending |

### Store Scoping for Catalog

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.56 | Products scoped to current store via BelongsToStore | Only products for bound store returned in queries | 05-BL 1.2 | pending |
| 2.57 | Collections scoped to current store | Only collections for bound store returned | 05-BL 1.2 | pending |
| 2.58 | Inventory items scoped to current store | Only inventory for bound store returned | 05-BL 1.2 | pending |
| 2.59 | Product auto-assigned to current store on create | store_id set from current_store singleton | 05-BL 1.2 | pending |
| 2.60 | Cross-store product isolation | Store A products invisible to Store B queries | 05-BL 1.2 | pending |

### Seeder Verification

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 2.61 | 20 products seeded for Acme Fashion | Product count matches expected | 07-SEEDERS | pending |
| 2.62 | Product #1 "Classic Cotton T-Shirt" has correct attributes | Handle, price 2499, active, Size(S/M/L/XL) x Color(Black/White/Navy) = 12 variants | 07-SEEDERS | pending |
| 2.63 | Product #2 "Premium Slim Fit Jeans" has compare_at_price | Price 7999, compare_at_price_amount 9999 | 07-SEEDERS | pending |
| 2.64 | Product #15 has draft status | Should not appear in storefront queries | 07-SEEDERS | pending |
| 2.65 | Product #17 sold out (inventory 0, policy deny) | All variant inventory items have quantity_on_hand=0, policy=deny | 07-SEEDERS | pending |
| 2.66 | Product #18 backorder (inventory 0, policy continue) | All variant inventory items have quantity_on_hand=0, policy=continue | 07-SEEDERS | pending |
| 2.67 | 3 collections seeded (T-Shirts, New Arrivals, Sale) | Collections exist with active status | 07-SEEDERS | pending |
| 2.68 | T-Shirts collection contains t-shirt products | Products #1, #3, #7, #14, #16, #17 attached | 07-SEEDERS | pending |
| 2.69 | Sale collection contains products with compare_at_price | Products #2, #20 attached | 07-SEEDERS | pending |
| 2.70 | Each product has at least one media record | ProductMedia exists for all 20 products | 07-SEEDERS | pending |
