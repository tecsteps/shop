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

## Phase 3: Storefront

### Home Page

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.1 | Home page renders at / | Page loads with storefront layout, header, footer, store name visible | 04-UI 2, 04-UI 3 | pass |
| 3.2 | Hero section displays theme settings content | Hero heading, subheading, and CTA button from theme_settings | 04-UI 3.1 | pass |
| 3.3 | Featured collections grid on home page | Collections configured in theme settings shown with images and titles | 04-UI 3.2 | pass |
| 3.4 | Featured products grid on home page | Active products shown with product cards (image, title, price) | 04-UI 3.3 | pass |
| 3.5 | Announcement bar renders from theme settings | Bar visible above header with configured text, dismissible via X button | 04-UI 2.3 | pending |

### Navigation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.6 | Desktop header navigation renders main-menu items | Nav items from main-menu NavigationMenu visible in header | 04-UI 2.4 | pending |
| 3.7 | Desktop dropdown submenus on hover | Child nav items appear in dropdown on hover | 04-UI 2.4 | pending |
| 3.8 | Mobile hamburger menu opens navigation drawer | Clicking hamburger shows slide-out drawer with nav items | 04-UI 2.4 | pass |
| 3.9 | Footer renders footer-menu navigation | Footer columns with nav items from footer-menu | 04-UI 2.6 | pending |
| 3.10 | Search, cart, and account icons in header | All three icons visible and linked correctly | 04-UI 2.4 | pass |

### Collections

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.11 | Collections index page at /collections | Lists all active collections with images and product counts | 04-UI 4 | pass |
| 3.12 | Collection detail page at /collections/{handle} | Shows collection title, description, breadcrumbs, product grid | 04-UI 4.1, 4.4 | pass |
| 3.13 | Collection filter by vendor | Vendor dropdown filters products to selected vendor only | 04-UI 4.3 | pass |
| 3.14 | Collection filter by price range | Min/max price inputs filter products within range | 04-UI 4.3 | pass |
| 3.15 | Collection sort by newest | Products ordered by creation date descending | 04-UI 4.2 | pending |
| 3.16 | Collection sort by price ascending | Products ordered by price low to high | 04-UI 4.2 | pending |
| 3.17 | Collection pagination (12 per page) | Only 12 products per page with pagination controls | 04-UI 4.6 | pending |
| 3.18 | Collection empty state when no products match filters | "No products found" message with clear filters button | 04-UI 4.7 | pending |

### Product Detail

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.19 | Product detail page at /products/{handle} | Shows title, price, description, images, variant selector | 04-UI 5 | pass |
| 3.20 | Price formatted as "24.99 EUR" | Cents converted to decimal with currency code after amount | 04-UI Currency | pass |
| 3.21 | Compare-at price shows strikethrough | Higher compare_at_price displayed with line-through styling | 04-UI 5.3 | pass |
| 3.22 | Variant selector with radio pills | Options with <=6 values shown as pill-shaped radio buttons | 04-UI 5.3 | pass |
| 3.23 | Variant selection updates price display | Selecting different variant updates shown price | 04-UI 5.3 | pending |
| 3.24 | In-stock messaging ("In stock" green text) | Products with available inventory show green check | 04-UI 5.3 | pass |
| 3.25 | Sold-out product shows "Out of stock" and disabled button | Deny policy with 0 inventory: red text, button says "Sold out" | 04-UI 5.3 | pass |
| 3.26 | Backorder product shows "Available on backorder" | Continue policy with 0 inventory: blue info text | 04-UI 5.3 | pass |
| 3.27 | Product image gallery with thumbnails | Main image + clickable thumbnail strip below | 04-UI 5.2 | pending |
| 3.28 | Breadcrumbs on product page (Home > Collection > Product) | Breadcrumb trail with links to home and collection | 04-UI 5.3 | pass |
| 3.29 | Draft product returns 404 | /products/{draft-handle} shows 404 error page | 04-UI 5, 05-BL | pass |

### Product Card Component

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.30 | Product card shows image, title, vendor, price | All elements rendered in card component | 04-UI 4.5 | pass |
| 3.31 | Product card Sale badge when compare_at_price set | "Sale" badge on products with higher compare_at price | 04-UI 4.5 | pass |
| 3.32 | Product card Sold Out badge for out-of-stock deny | "Sold out" badge when all variants have 0 inventory with deny policy | 04-UI 4.5 | pass |

### Static Pages

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.33 | Published page renders at /pages/{handle} | Page title and content_html displayed | 04-UI | pass |
| 3.34 | Draft page returns 404 | /pages/{draft-handle} shows 404 error page | 04-UI | pass |
| 3.35 | Archived page returns 404 | /pages/{archived-handle} shows 404 error page | 04-UI | pending |

### Error Pages

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.36 | 404 error page renders for unknown routes | Standalone page with "Page not found" and back to home link | 04-UI Errors | pass |
| 3.37 | 503 error page renders for suspended stores | Standalone "Store is currently under maintenance" page | 04-UI Errors | pending |

### Dark Mode and Responsive

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.38 | Dark mode styling applied | Background, text, borders change with dark: prefix classes | 04-UI 2.8 | pass |
| 3.39 | Mobile responsive layout (375px width) | Hamburger menu replaces nav, content stacks vertically | 04-UI 2.4 | pass |
| 3.40 | Skip link visible on keyboard focus | "Skip to main content" link appears on Tab focus | 04-UI 2.2 | pass |

### Search Placeholder

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 3.41 | Search page renders at /search | Placeholder page with search input and "coming soon" message | 04-UI | pending |

## Phase 4: Cart & Checkout

### Add to Cart

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.1 | Product page renders with Add to Cart button | Button visible, not disabled for in-stock product | 04-UI 5.4 | pass |
| 4.2 | Select variant and click Add to Cart | CartService::addLine() called, item added to session cart | 04-UI 5.4, 05-BL 3.1 | pass |
| 4.3 | Cart drawer opens after adding item | Slide-out drawer shows with added product details | 04-UI 6.1 | pass |
| 4.4 | Cart count badge updates in header | Badge shows "1" after adding first item | 04-UI 6.1 | pass |
| 4.5 | Adding same variant again increments quantity | Quantity increases instead of creating duplicate line | 05-BL 3.1 | pending |

### Cart Drawer

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.6 | Cart drawer shows line items with image, title, variant, price | All product details rendered correctly | 04-UI 6.1 | pass |
| 4.7 | Cart drawer quantity increase button works | Quantity increments, line total updates | 04-UI 6.1 | pass |
| 4.8 | Cart drawer quantity decrease button works | Quantity decrements, removes item at 0 | 04-UI 6.1 | pass |
| 4.9 | Cart drawer remove item button works | Item removed from cart, empty state shown if last item | 04-UI 6.1 | pass |
| 4.10 | Cart drawer shows subtotal | Correct sum of line totals displayed | 04-UI 6.1 | pass |
| 4.11 | Cart drawer View Cart link navigates to /cart | Link href points to cart page | 04-UI 6.1 | pass |
| 4.12 | Cart drawer close button and overlay click close drawer | Drawer closes on X click or overlay click | 04-UI 6.1 | pass |

### Full Cart Page

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.13 | Cart page renders at /cart with items | Full page cart with line items, order summary sidebar | 04-UI 6.2 | pass |
| 4.14 | Cart page quantity controls work | Increase/decrease quantity updates totals in real-time | 04-UI 6.2 | pass |
| 4.15 | Cart page remove item works | Item removed, empty state shown when cart empty | 04-UI 6.2 | pass |
| 4.16 | Cart page empty state with "Continue Shopping" link | Shopping bag icon, empty message, link to home | 04-UI 6.2 | pass |
| 4.17 | Cart page shipping estimate country selector | Selecting a country shows available shipping rates with prices | 04-UI 6.2 | pass |
| 4.18 | Valid discount code "WELCOME10" accepted | Success message "Discount code applied." displayed | 04-UI 6.2, 05-BL 3.3 | pass |
| 4.19 | Expired discount code "EXPIRED20" rejected | Error message "This discount has expired." displayed | 04-UI 6.2, 05-BL 3.3 | pass |
| 4.20 | Invalid/nonexistent discount code rejected | Error message "Invalid discount code." displayed | 05-BL 3.3 | pending |

### Checkout Flow

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.21 | Proceed to Checkout creates Checkout and redirects | Checkout model created with started status, redirects to /checkout/{id} | 04-UI 7, 05-BL 3.4 | pass |
| 4.22 | Checkout Step 1 renders contact and address form | Email, shipping address fields with country selector | 04-UI 7.1 | pass |
| 4.23 | Checkout Step 1 validation rejects empty required fields | Error messages shown for email, name, address, city, postal code | 04-UI 7.1 | pending |
| 4.24 | Checkout Step 1 submit transitions to Step 2 | CheckoutService::setAddress() called, stepper updates | 04-UI 7.1, 05-BL 3.4 | pass |
| 4.25 | Checkout Step 2 shows shipping rates for address country | Available rates from ShippingCalculator displayed with radio buttons | 04-UI 7.2 | pass |
| 4.26 | Checkout Step 2 shows address summary with change link | Address displayed in summary box, "Change address" goes back to Step 1 | 04-UI 7.2 | pass |
| 4.27 | Checkout Step 2 submit transitions to Step 3 | CheckoutService::setShippingMethod() called, stepper updates | 04-UI 7.2, 05-BL 3.4 | pass |
| 4.28 | Checkout Step 3 shows payment method options | Credit Card, PayPal, Bank Transfer radio buttons | 04-UI 7.3 | pass |
| 4.29 | Credit Card mock form shows card number, expiry, CVV | Mock form fields rendered with "testing" disclaimer | 04-UI 7.3 | pass |
| 4.30 | PayPal option shows redirect message | "You will be redirected to PayPal" message | 04-UI 7.3 | pass |
| 4.31 | Bank Transfer shows bank instructions | IBAN, BIC, and reference instructions displayed | 04-UI 7.3 | pass |
| 4.32 | Order summary sidebar visible on all checkout steps | Line items, subtotal, shipping, tax, total shown | 04-UI 7.4 | pass |
| 4.33 | Place Order completes checkout and redirects to confirmation | CheckoutService methods called, redirects to /checkout/{id}/confirmation | 04-UI 7.3, 05-BL 3.4 | pass |

### Order Confirmation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.34 | Confirmation page shows thank you message | "Thank you for your order!" heading displayed | 04-UI 7.5 | pass |
| 4.35 | Confirmation shows email address | Customer email shown in confirmation text | 04-UI 7.5 | pass |
| 4.36 | Confirmation shows order summary with all totals | Subtotal, shipping, tax, total with correct amounts | 04-UI 7.5 | pass |
| 4.37 | Confirmation "Continue Shopping" link navigates to home | Link back to storefront home page | 04-UI 7.5 | pass |

### Stepper Navigation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.38 | Stepper shows completed steps with checkmarks | Steps before current show green checkmark icon | 04-UI 7.1 | pass |
| 4.39 | Stepper allows navigating back to completed steps | Clicking completed step number goes back to that step | 04-UI 7.1 | pass |
| 4.40 | Stepper disables future steps | Cannot click on steps ahead of current | 04-UI 7.1 | pass |

### Console and Error Checks

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 4.41 | No JavaScript errors on product page | Console error count is 0 | General | pass |
| 4.42 | No JavaScript errors on cart page | Console error count is 0 | General | pass |
| 4.43 | No JavaScript errors on checkout pages | Console error count is 0 | General | pass |
| 4.44 | No JavaScript errors on confirmation page | Console error count is 0 | General | pass |

## Phase 5: Payments & Orders

### Credit Card Payments

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.01 | Credit card payment succeeds with test card 4242... | MockPaymentProvider returns captured status | 05-BL 4.1 | pass |
| 5.02 | Decline card 4000000000000002 returns declined error | MockPaymentProvider rejects known decline card | 05-BL 4.1 | pass |
| 5.03 | Insufficient funds card 4000000000009995 returns error | MockPaymentProvider rejects insufficient funds card | 05-BL 4.1 | pass |
| 5.04 | Mock payment ID starts with "mock_" prefix | Provider generates identifiable transaction IDs | 05-BL 4.1 | pass |

### PayPal Payments

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.05 | PayPal payment succeeds immediately | MockPaymentProvider returns captured for PayPal | 05-BL 4.1 | pass |

### Bank Transfer Payments

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.06 | Bank transfer creates pending payment | MockPaymentProvider returns pending status | 05-BL 4.1 | pass |
| 5.07 | Admin confirms bank transfer payment | Payment transitions to captured, order to paid | 05-BL 4.2 | pass |
| 5.08 | Cannot confirm non-bank-transfer payment | Rejects confirmation for credit card payments | 05-BL 4.2 | pass |
| 5.09 | Cannot confirm already-captured payment | Rejects double-confirmation | 05-BL 4.2 | pass |
| 5.10 | Auto-cancel job cancels old unpaid bank transfers | Orders older than threshold are cancelled | 05-BL 4.3 | pass |
| 5.11 | Auto-cancel job skips recent bank transfers | Recent pending orders are preserved | 05-BL 4.3 | pass |

### Order Creation

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.12 | Order is created from completed checkout | All fields populated from checkout data | 05-BL 3.1 | pass |
| 5.13 | Sequential order numbers starting at 1001 | First order gets #1001, second gets #1002 | 05-BL 3.1 | pass |
| 5.14 | Order lines contain product/variant snapshots | Snapshot titles preserved even if product changes | 05-BL 3.1 | pass |
| 5.15 | Inventory is committed on order creation | Stock levels decrease by ordered quantity | 05-BL 3.1 | pass |
| 5.16 | Cart is converted to order status after checkout | Cart marked as converted | 05-BL 3.1 | pass |
| 5.17 | OrderCreated event fires on order creation | Event dispatched with order instance | 05-BL 3.1 | pass |
| 5.18 | Archived product snapshots are preserved in order | Product title/price captured at time of order | 05-BL 3.1 | pass |
| 5.19 | Customer is linked to order | customer_id set from checkout | 05-BL 3.1 | pass |
| 5.20 | Email is stored from checkout when no customer | Guest email captured on order | 05-BL 3.1 | pass |

### Refunds

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.21 | Full refund updates financial status to refunded | Order financial_status transitions correctly | 05-BL 5.1 | pass |
| 5.22 | Partial refund updates financial status to partially_refunded | Intermediate refund state tracked | 05-BL 5.1 | pass |
| 5.23 | Refund exceeding paid amount is rejected | Cannot refund more than was paid | 05-BL 5.1 | pass |
| 5.24 | Refund with restock restores inventory | Stock levels increase by refunded quantity | 05-BL 5.1 | pass |
| 5.25 | Refund without restock leaves inventory unchanged | Stock levels remain the same | 05-BL 5.1 | pass |
| 5.26 | OrderRefunded event fires on refund | Event dispatched with order and refund instances | 05-BL 5.1 | pass |
| 5.27 | Refund reason is recorded | Reason text stored on refund record | 05-BL 5.1 | pass |

### Fulfillment

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.28 | Fulfillment is created for order lines | Fulfillment record with correct line quantities | 05-BL 6.1 | pass |
| 5.29 | Partial fulfillment sets status to partial | Order fulfillment_status reflects partial state | 05-BL 6.1 | pass |
| 5.30 | Full fulfillment sets status to fulfilled | All lines fulfilled transitions order status | 05-BL 6.1 | pass |
| 5.31 | Fulfillment includes tracking info | Tracking number and carrier stored | 05-BL 6.1 | pass |
| 5.32 | Mark fulfillment as shipped updates timestamps | shipped_at set, status transitions to shipped | 05-BL 6.2 | pass |
| 5.33 | Mark fulfillment as delivered updates timestamps | delivered_at set, status transitions to delivered | 05-BL 6.2 | pass |
| 5.34 | Over-fulfillment is prevented | Cannot fulfill more than ordered quantity | 05-BL 6.1 | pass |
| 5.35 | Fulfillment guard blocks pending orders | Cannot fulfill order with pending financial status | 05-BL 6.1 | pass |
| 5.36 | Fulfillment guard allows paid orders | Paid orders can be fulfilled | 05-BL 6.1 | pass |
| 5.37 | Fulfillment guard allows partially refunded orders | Partially refunded orders can still be fulfilled | 05-BL 6.1 | pass |
| 5.38 | OrderFulfilled event fires on full fulfillment | Event dispatched when all lines fulfilled | 05-BL 6.1 | pass |

### Digital Product Auto-Fulfill

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.39 | Digital products auto-fulfill on payment | requires_shipping=false items fulfilled automatically | 05-BL 6.3 | pass |
| 5.40 | Digital auto-fulfill on bank transfer confirmation | Bank transfer confirmation triggers auto-fulfill | 05-BL 6.3 | pass |

### Browser Verification

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 5.41 | Full purchase flow with credit card | Add to cart, checkout, pay, see confirmation | 04-UI 7 | pass |
| 5.42 | Decline card shows error message | Payment declined message displayed to user | 04-UI 7.4 | pass |
| 5.43 | No JavaScript errors during checkout flow | Console error count is 0 | General | pass |

## Phase 6: Customer Accounts

### Customer Dashboard

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 6.01 | Dashboard renders with customer name and email | Component displays customer info | 04-UI 8.1 | pass |
| 6.02 | Dashboard shows recent orders (last 5) | Orders listed with number, date, status, total | 04-UI 8.1 | pass |
| 6.03 | Dashboard links to orders and addresses pages | Quick navigation to sub-pages | 04-UI 8.1 | pass |
| 6.04 | Sign out button logs customer out | Session destroyed, redirect to login | 06-Auth 2.3 | pending |

### Order History

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 6.05 | Order history lists all customer orders | Paginated table with order details | 04-UI 8.2 | pass |
| 6.06 | Empty state shown for customer with no orders | "You have no orders yet" message | 04-UI 8.2 | pass |
| 6.07 | Order detail shows line items and totals | Product, variant, qty, price, subtotal, shipping, tax, total | 04-UI 8.3 | pass |
| 6.08 | Order detail shows shipping address | Address from order snapshot | 04-UI 8.3 | pass |
| 6.09 | Order detail shows fulfillment timeline | Tracking info, shipped/delivered dates | 04-UI 8.3 | pass |
| 6.10 | Cannot view another customer's order | Returns 404 for unauthorized order | 06-Auth 3.1 | pass |

### Address Management

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 6.11 | Lists saved addresses | All customer addresses displayed | 04-UI 8.4 | pass |
| 6.12 | Creates a new address | Address form saves correctly | 04-UI 8.4 | pass |
| 6.13 | Updates an existing address | Edit form pre-fills and saves changes | 04-UI 8.4 | pass |
| 6.14 | Deletes an address | Address removed from database | 04-UI 8.4 | pass |
| 6.15 | Sets default address | Default flag toggled, other addresses unset | 04-UI 8.4 | pass |
| 6.16 | Validates required fields on address form | first_name, last_name, address1, city, postal_code required | 04-UI 8.4 | pass |
| 6.17 | Cannot manage another customer's addresses | Returns error for unauthorized address | 06-Auth 3.1 | pass |

### Auth & Access

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 6.18 | Unauthenticated user redirected to login | auth:customer middleware enforced | 06-Auth 2.1 | pass |

### Browser Verification

| # | Test Case | What It Verifies | Spec Section | Status |
|---|-----------|-----------------|--------------|--------|
| 6.19 | Login, view dashboard, navigate to orders and addresses | Full account flow works in browser | 04-UI 8 | pass |
| 6.20 | Add and delete address in browser | Address CRUD works end-to-end | 04-UI 8.4 | pass |
| 6.21 | No JavaScript errors on account pages | Console error count is 0 | General | pass |
