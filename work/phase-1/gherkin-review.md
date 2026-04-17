# Phase 1: Foundation - Gherkin Specification Review

> Reviewed: 2026-03-20
> Reviewer: Gherkin Reviewer Agent
> Source specs: `specs/09-IMPLEMENTATION-ROADMAP.md` (Steps 1.1-1.8), `specs/06-AUTH-AND-SECURITY.md`, `specs/01-DATABASE-SCHEMA.md`
> Reviewed file: `work/phase-1/gherkin-specs.md`

---

## Counts

**Requirements identified: 97 discrete requirements**
**Gherkin scenarios written: 143 (104 plain Scenarios + 39 Scenario Outlines)**

The Scenario Outlines expand to many more individual test cases when example rows are counted (approximately 270+ expanded cases), giving thorough role-matrix coverage.

---

## Requirements Breakdown by Step

### Step 1.1: Environment and Config (8 requirements, 8 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 1 | SQLite database connection with WAL, foreign keys, busy_timeout, synchronous | Yes |
| 2 | File-based cache | Yes |
| 3 | File-based sessions with 120-minute lifetime | Yes |
| 4 | Synchronous queue | Yes |
| 5 | Log-based mail | Yes |
| 6 | Customer auth guard, provider, and password broker | Yes |
| 7 | Structured JSON logging channel | Yes |
| 8 | Local filesystem / public disk for media | Yes |

### Step 1.2: Core Migrations (9 requirements, 9 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 9 | organizations table schema | Yes |
| 10 | stores table schema | Yes |
| 11 | store_domains table schema | Yes |
| 12 | users table modifications (status, last_login_at, two_factor_*, password_hash) | Yes |
| 13 | store_users table with composite PK | Yes |
| 14 | store_settings table with store_id as PK | Yes |
| 15 | Monetary amounts stored as INTEGER | Yes |
| 16 | Enum columns use TEXT with CHECK constraints | Yes |
| 17 | FK cascading deletes | Yes |

### Step 1.3: Core Models (19 requirements, 19 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 18 | Organization hasMany stores | Yes |
| 19 | Organization factory | Yes |
| 20 | Store belongsTo Organization | Yes |
| 21 | Store hasMany StoreDomains | Yes |
| 22 | Store belongsToMany Users through store_users | Yes |
| 23 | Store hasOne StoreSettings | Yes |
| 24 | Store factory with defaults | Yes |
| 25 | Store casts status to StoreStatus enum | Yes |
| 26 | StoreDomain belongsTo Store | Yes |
| 27 | StoreDomain factory | Yes |
| 28 | StoreDomain casts type to StoreDomainType enum | Yes |
| 29 | StoreUser is custom Pivot with role attribute | Yes |
| 30 | StoreSettings belongsTo Store | Yes |
| 31 | StoreSettings casts settings_json to array | Yes |
| 32 | StoreSettings factory | Yes |
| 33 | User belongsToMany stores through store_users | Yes |
| 34 | Seeders create sample data for all foundation models | Yes |
| 35 | All models have explicit return type declarations | Yes |
| 36 | All models have proper fillable or guarded arrays | Yes |

### Step 1.4: Enums (3 requirements, 3 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 37 | StoreStatus enum (Active, Suspended) | Yes |
| 38 | StoreUserRole enum (Owner, Admin, Staff, Support) | Yes |
| 39 | StoreDomainType enum (Storefront, Admin, Api) | Yes |

### Step 1.5: Tenant Resolution Middleware (8 requirements, 8 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 40 | Storefront hostname resolution binds store to container | Yes |
| 41 | Unknown hostname returns 404 | Yes |
| 42 | Suspended store returns 503 | Yes |
| 43 | Hostname-to-store_id cached for 5 minutes | Yes |
| 44 | Admin reads current_store_id from session | Yes |
| 45 | Admin without store_users record returns 403 | Yes |
| 46 | ResolveStore registered in storefront middleware group | Yes |
| 47 | ResolveStore registered in admin middleware group | Yes |

### Step 1.6: BelongsToStore Trait and Global Scope (4 requirements, 4 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 48 | StoreScope filters queries by current store | Yes |
| 49 | BelongsToStore auto-sets store_id on creating | Yes |
| 50 | Behavior when no current store is bound | Yes |
| 51 | Trait applied to all 16 tenant-scoped models (listed) | Yes |

### Step 1.7: Admin Authentication (12 requirements, 12 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 52 | Admin login with valid credentials succeeds, session regenerated, redirect to /admin | Yes |
| 53 | Admin login with invalid credentials fails with generic "Invalid credentials" | Yes |
| 54 | Admin login with non-existent email fails with same generic message | Yes |
| 55 | Rate limiting: 5 attempts per minute per IP | Yes |
| 56 | Remember me sets long-lived token | Yes |
| 57 | Logout invalidates session, regenerates CSRF, redirects to /admin/login | Yes |
| 58 | Password reset sends email for existing users, generic response | Yes |
| 59 | Password reset for non-existent email gives same generic response | Yes |
| 60 | Password reset with valid token succeeds | Yes |
| 61 | Password reset with expired token (>60 min) fails | Yes |
| 62 | Password reset email throttled to one per 60 seconds | Yes |
| 63 | Login rate limiter registered in AppServiceProvider | Yes |

### Step 1.7: Customer Authentication (16 requirements, 16 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 64 | Customer guard uses session driver and customers provider | Yes |
| 65 | CustomerUserProvider scopes queries by store_id | Yes |
| 66 | Customer login with valid credentials succeeds | Yes |
| 67 | Customer login redirects to intended URL | Yes |
| 68 | Customer login with invalid credentials fails with generic message | Yes |
| 69 | Customer login rate-limited to 5 per minute per IP | Yes |
| 70 | Customer registration with valid data succeeds, auto-login, redirect to /account | Yes |
| 71 | Registration requires name, email, password, password_confirmation | Yes |
| 72 | Registration enforces min password length of 8 | Yes |
| 73 | Registration enforces password confirmation match | Yes |
| 74 | Customer email unique per store (composite unique) | Yes |
| 75 | Same email can register in different stores | Yes |
| 76 | Registration supports optional marketing_opt_in | Yes |
| 77 | Registration defaults marketing_opt_in to false | Yes |
| 78 | Customer password reset: separate broker, token table with store_id | Yes |
| 79 | Customer password reset token expiry (60 min) | Yes |
| 80 | Customer password reset email throttled to one per 60 seconds | Yes |
| 81 | Customer forgot-password gives generic response | Yes |

### Step 1.8: Authorization (18 requirements, 71 scenarios) - COMPLETE

| # | Requirement | Covered? |
|---|-------------|----------|
| 82 | ChecksStoreRole trait: getStoreRole | Yes |
| 83 | ChecksStoreRole trait: hasRole | Yes |
| 84 | ChecksStoreRole trait: isOwnerOrAdmin | Yes |
| 85 | ChecksStoreRole trait: isOwnerAdminOrStaff | Yes |
| 86 | ChecksStoreRole trait: isAnyRole | Yes |
| 87 | User.roleForStore helper method | Yes |
| 88 | ProductPolicy (viewAny, view, create, update, delete, archive, restore) | Yes |
| 89 | OrderPolicy (viewAny, view, update, cancel, createFulfillment, createRefund) | Yes |
| 90 | CollectionPolicy (viewAny, view, create, update, delete) | Yes |
| 91 | DiscountPolicy (viewAny, view, create, update, delete) | Yes |
| 92 | CustomerPolicy (viewAny, view, update) | Yes |
| 93 | StorePolicy (viewSettings, updateSettings, delete) | Yes |
| 94 | ThemePolicy (viewAny, view, create, update, delete, publish) | Yes |
| 95 | PagePolicy (viewAny, view, create, update, delete) | Yes |
| 96 | FulfillmentPolicy (create, update, cancel) | Yes |
| 97 | RefundPolicy (create) | Yes |

Additional authorization items covered beyond the 97 core requirements:
- NavigationMenuPolicy (from auth spec Section 2.4, not listed in roadmap Step 1.8 but correctly included)
- 9 authorization gates (from auth spec Section 2.5)
- CheckStoreRole middleware (from auth spec Section 3.3)
- Ownership constraints (exactly one owner per store, ownership transfer is dedicated action)

---

## Gaps Found

### 1. CustomerAuthenticate Middleware - MISSING

The auth spec Section 3.1 defines three custom middleware aliases:
- `store.resolve` -> ResolveStore (covered)
- `role.check` -> CheckStoreRole (covered)
- `auth:customer` -> CustomerAuthenticate (NOT covered)

The `CustomerAuthenticate` middleware (auth spec Section 3.3) ensures customer authentication via the `customer` guard, redirecting unauthenticated customers to `/account/login` with intended URL preservation. While the *behavior* of customer authentication is tested in the Customer Authentication scenarios, the middleware itself has no dedicated Gherkin scenarios covering:
- Authenticated customer passes through
- Unauthenticated customer is redirected to `/account/login`
- Current URL is saved as intended redirect URL

**Impact:** Low-to-medium. The redirect-to-intended-URL behavior IS covered in the customer login scenarios (Scenario: "Customer login redirects to intended URL if present"), but the middleware itself as a standalone component is not tested.

**Recommendation:** Add 2-3 scenarios for CustomerAuthenticate middleware. This middleware is explicitly part of Step 1.7's customer auth requirements since it handles the redirect flow.

### 2. Middleware Alias Registration - MISSING

The auth spec Section 3.1 requires three middleware aliases to be registered in `bootstrap/app.php`: `store.resolve`, `role.check`, and `auth:customer`. The Gherkin specs cover middleware *group* registration (storefront and admin groups containing ResolveStore) but do not verify the alias registrations themselves.

**Impact:** Low. The aliases are an implementation detail, but they are explicitly specified in the auth spec.

**Recommendation:** Consider adding a scenario verifying the three middleware aliases are registered.

### 3. Store View Sharing - PARTIAL

Step 1.5 requires that after resolving the store, it should be "shared with all Blade views as `currentStore`." The storefront hostname resolution scenario includes "And the store should be shared with all Blade views as `currentStore`", which is correct. However, the admin session resolution scenario does NOT include this assertion.

**Impact:** Low. The behavior is the same for both paths (shared steps after resolution), but the admin scenario is incomplete.

**Recommendation:** Add the view sharing assertion to the admin session resolution scenario.

---

## Consistency with Phase 2 and Phase 3

### Phase 2 Consistency (Catalog)
Phase 2 depends on Phase 1 models (Store), the BelongsToStore trait (applied to Product, Collection, InventoryItem), and the StoreScope. The Gherkin specs correctly list these models in the BelongsToStore trait application scenario (line 404-422). The ProductPolicy is fully specified in Phase 1, ready for Phase 2 to implement the Product model it authorizes.

No conflicts identified. Phase 1 provides the full authorization layer that Phase 2 will use.

### Phase 3 Consistency (Themes, Pages, Navigation)
Phase 3 depends on ThemePolicy, PagePolicy, and NavigationMenuPolicy - all covered in the Phase 1 Gherkin specs. The BelongsToStore trait correctly lists Theme, Page, and NavigationMenu. The `manage-navigation` gate is covered.

No conflicts identified.

### General Sequencing
The Gherkin specs correctly define policies for models that do not yet exist (Order, Product, Fulfillment, etc.). This is the intended design - Phase 1 builds the authorization framework, and later phases implement the models. This approach is consistent with the roadmap's dependency graph.

---

## Ambiguities Identified

The Gherkin spec's self-assessment (lines 1502-1529) identifies 10 ambiguities. I concur with all of them and add the following:

**11. Admin suspended store behavior for mutations:** The auth spec Section 3.3 states that for admin routes on suspended stores, the middleware should "abort 403 for mutations instead" of 503. The Gherkin specs only test the storefront 503 behavior and do not cover admin-specific suspended store handling. The roadmap step 1.5 says "Return 503 for suspended stores on storefront routes" without explicitly defining admin behavior. This is an ambiguity that should be resolved before implementation.

**12. Middleware group composition beyond ResolveStore:** The auth spec Section 3.2 defines full middleware stacks for each route group (e.g., admin authenticated routes use `web`, `auth`, `verified`, `store.resolve`, `role.check`). The Gherkin specs only verify that ResolveStore is in the storefront/admin groups, not the complete middleware stack. Whether the full stack composition is a Phase 1 requirement or an integration concern is ambiguous.

**13. Store view sharing variable name:** The spec says the variable is `currentStore` (camelCase). The Gherkin scenario uses `currentStore`. This is consistent, but worth noting for implementation since Blade `@share` and `View::share()` handle this differently.

---

## Self-Assessment

### Confidence Level: HIGH (85-90%)

The Gherkin specifications provide thorough coverage of all 97 identified requirements across Steps 1.1-1.8. The traceability table at the end of the spec is well-structured and maps every requirement to its scenarios.

### Strengths
- Complete coverage of the permission matrix across all 10 policies using Scenario Outlines
- Thorough treatment of edge cases (no role, non-existent emails, expired tokens)
- Good use of Scenario Outlines for exhaustive role-based testing
- The self-assessment section is honest and identifies real ambiguities
- The traceability table provides a clear audit trail

### Weaknesses
- The CustomerAuthenticate middleware is missing as a standalone component test
- Middleware alias registration is not verified
- The admin session resolution scenario is missing the view sharing assertion
- Some Scenario Outlines combine multiple policy actions (e.g., "viewAny / view") which could make test mapping less precise

### Edge Cases Intentionally Excluded (and why)
- **API auth (Sanctum):** Not part of Phase 1 per the roadmap. Correctly excluded.
- **Rate limiters beyond `login`:** (api.admin, api.storefront, checkout, search, analytics, webhooks) belong to their respective phases. Correctly excluded.
- **CSRF protection:** Standard Laravel behavior, not a Phase 1 custom requirement. Correctly excluded.
- **Ownership transfer flow:** The spec defines the constraint (one owner per store) but no transfer mechanism in Phase 1. The Gherkin specs correctly cover the constraint without the transfer flow.
- **Two-factor authentication flow:** The users table has `two_factor_*` columns, but no 2FA flow is defined in Phase 1. The migration columns are covered; the flow is deferred. Correctly excluded.

### What Could Be Missing
- If the team decides that CustomerAuthenticate middleware is a Phase 1 deliverable (which it should be, since it is part of the customer auth system), 2-3 additional scenarios are needed.
- The rate limiter behavior when multiple guards share the same limiter key space could use more explicit testing.
- No scenario tests that disabled users (`status = 'disabled'`) cannot log in. The auth spec does not explicitly require this, but it is implied by the `status` column with CHECK constraint.

---

## Verdict

**APPROVED WITH MINOR GAPS.** The 3 gaps identified are low-impact and can be addressed before or during implementation. The core coverage is comprehensive, and no high-priority requirements are missing. The spec is ready for coding to begin, with the recommendation that the CustomerAuthenticate middleware scenarios be added.
