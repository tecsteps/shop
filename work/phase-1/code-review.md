# Phase 1: Foundation - Code Review

## Quality Metrics Defined

| # | Criterion | Threshold | Measured By |
|---|-----------|-----------|-------------|
| 1 | Code Style | 0 Pint violations in Phase 1 files | `vendor/bin/pint --test --format agent` |
| 2 | Type Safety | All methods have return type declarations; all parameters typed | Manual review of all PHP files |
| 3 | Eloquent Best Practices | Proper relationship return types, no raw DB queries, factory usage | Manual review |
| 4 | Security | No SQL injection, proper input validation, rate limiting, CSRF | Manual review |
| 5 | SOLID Principles | Single responsibility, dependency injection, interface segregation | Manual review |
| 6 | PHP 8 Features | Constructor promotion, backed enums, match expressions where appropriate | Manual review |
| 7 | Test Quality | Meaningful tests, edge cases, factory usage, proper assertions | Manual review of 14 test files |
| 8 | Laravel Conventions | Proper guards, middleware, policies, service provider patterns | Manual review |
| 9 | Code Duplication | No significant duplication (DRY) | Manual review |
| 10 | Error Handling | Proper exceptions, no silent failures | Manual review |

---

## Static Analysis Results

### Laravel Pint (Code Style)
- **Phase 1 files**: 0 violations (PASS)
- **Pre-existing files**: 10 violations in Fortify scaffolding files (not Phase 1 code, out of scope)

### Test Suite
- **235 tests, 369 assertions**: All passing
- **Duration**: 5.84s
- **No skipped or incomplete tests**

### Code Smell Check
- `env()` usage outside config files: 0 occurrences (correct)
- `DB::` facade usage in app code: 1 occurrence (`DB::prohibitDestructiveCommands` in AppServiceProvider - appropriate)
- Raw SQL queries: 0 occurrences

---

## Item-Level Checklist

### 1. Code Style - PASS

All Phase 1 files pass `vendor/bin/pint --test --format agent` with 0 violations. Consistent formatting, proper spacing, and brace style throughout.

### 2. Type Safety - PASS

All methods have explicit return type declarations:
- Model relationships all declare return types (`HasMany`, `BelongsTo`, `BelongsToMany`, `HasOne`)
- Middleware `handle()` methods typed: `(Request $request, Closure $next, ...): Response`
- Livewire component methods return `void` or `mixed`
- ChecksStoreRole trait methods have proper return types (`?StoreUserRole`, `bool`)
- Factory `definition()` methods return `array`
- Seeder `run()` methods return `void`

One minor note: Livewire `render()` methods return `mixed` rather than `\Illuminate\View\View`. This is acceptable because Livewire internally returns different types depending on layout chaining, so `mixed` is the correct pragmatic choice.

### 3. Eloquent Best Practices - PASS

**Relationships**: All properly defined with return type hints:
- `Organization::stores(): HasMany`
- `Store::organization(): BelongsTo`, `Store::domains(): HasMany`, `Store::users(): BelongsToMany`, `Store::settings(): HasOne`
- `StoreDomain::store(): BelongsTo`
- `StoreSettings::store(): BelongsTo`
- `Customer` inherits `store(): BelongsTo` from `BelongsToStore` trait
- `User::stores(): BelongsToMany`

**No raw DB queries** in application code. `DB::` facade only used for `prohibitDestructiveCommands`.

**Factories**: All models have factories with appropriate defaults and states:
- `StoreFactory` has `suspended()` state
- `StoreDomainFactory` has `primary()`, `admin()`, `api()` states
- `CustomerFactory` has `withMarketing()` state and caches password hash (performance optimization)

**Potential N+1 concern**: The `ChecksStoreRole::getStoreRole()` method queries `$user->stores()` every call. When multiple policy methods are called for the same user in a single request, this will result in repeated queries. This is not a bug, and the impact is minimal since policies typically check one or two methods per request, but is worth noting for future optimization (e.g., caching role per request).

### 4. Security - PASS

- **SQL injection**: No raw queries; all database access through Eloquent/query builder with parameter binding
- **Rate limiting**: Admin and customer login both rate-limited to 5 attempts/minute per IP
- **CSRF**: Livewire handles CSRF automatically; logout route uses POST method
- **Password hashing**: Customer model casts `password` to `hashed`; factory uses `Hash::make()`
- **Session security**: `session()->regenerate()` after login, `session()->invalidate()` and `session()->regenerateToken()` on logout
- **Generic error messages**: Login failures show "Invalid credentials" (no email enumeration)
- **Store isolation**: StoreScope ensures tenant data separation; CustomerUserProvider scopes credentials by store_id
- **Password validation**: Minimum 8 chars with confirmation required; production environment enforces stronger rules via `Password::defaults()`

### 5. SOLID Principles - PASS

- **Single Responsibility**: Each class has one clear purpose. Models handle data access, middleware handles request filtering, policies handle authorization, Livewire components handle UI actions
- **Open/Closed**: `BelongsToStore` trait and `StoreScope` are reusable across future models without modification
- **Dependency Injection**: `CustomerUserProvider` receives `$app['hash']` and model via constructor. `ResolveStore` middleware uses service container for store binding
- **Interface Segregation**: `ChecksStoreRole` trait provides focused helper methods; policies consume only what they need
- **Liskov Substitution**: `Customer extends Authenticatable`, `StoreUser extends Pivot`, `CustomerUserProvider extends EloquentUserProvider` - all respect parent contracts

### 6. PHP 8 Features - PASS

- **Backed enums**: `StoreStatus`, `StoreUserRole`, `StoreDomainType` all properly implemented as `string`-backed enums with TitleCase keys per convention
- **Null-safe operator**: Used in `User::roleForStore()` (`?->pivot`)
- **First-class callables**: Used in `StoreFactory` (`fn (array $attributes) => [...]`)
- **Named arguments**: Used in `Application::configure(basePath: dirname(__DIR__))` in `bootstrap/app.php`
- **Constructor property promotion**: Not applicable - no classes in Phase 1 use constructor injection with stored properties (the existing `AppServiceProvider` and Livewire components do not have constructors requiring promotion)

### 7. Test Quality - PASS

**Coverage**:
- 14 Phase-1-specific test files
- 235 tests with 369 assertions
- All 8 implementation steps (1.1 through 1.8) have dedicated tests

**Test patterns**:
- Config tests verify actual configuration values
- Model tests verify relationships, casts, and factory defaults
- Enum tests verify cases and backing values
- Middleware tests use test routes with proper assertions (200, 404, 503, 403)
- Auth tests use Livewire testing (`Livewire::test()`) with proper assertions
- Policy tests use datasets to test all role combinations efficiently
- Gate tests cover all 8 gates with all 4 roles (32 tests via datasets)
- Store isolation tests verify StoreScope filtering and auto-assignment

**Factory usage**: Tests consistently use factories rather than manual model creation. `Customer::withoutGlobalScopes()` is correctly used in tests where the StoreScope would interfere.

**Edge cases covered**: Unknown hostnames, suspended stores, no session store_id, no store_users record, same email across stores, marketing opt-in default.

### 8. Laravel Conventions - PASS

- **Guards**: Customer guard properly configured with custom `customer` driver
- **Middleware**: Registered in `bootstrap/app.php` using `appendToGroup()` and `alias()` - correct for Laravel 12
- **Policies**: Follow standard structure with User parameter; use container for current store
- **Gates**: Defined in `AppServiceProvider::configureGates()` - clean pattern with loop for similar gates
- **Service Provider**: Boot method organized into focused private methods (`configureDefaults`, `configureAuth`, `configureRateLimiting`, `configureGates`)
- **Migrations**: Proper `up()` / `down()` methods, foreign key constraints with cascade delete, named indexes
- **Seeders**: Organized with `DatabaseSeeder` calling `OrganizationSeeder` and `StoreSeeder`

### 9. Code Duplication - PASS

- **Login components**: The admin and customer login components share similar structure (validation, rate limiting, attempt, redirect). This is acceptable because: (a) they use different guards, (b) they redirect differently, (c) future phases will diverge them further (admin needs store selection, customer needs store-scoped auth). Extracting a shared base would be premature.
- **Policy boilerplate**: `getStoreId()` is repeated in 8 policies. This is mitigated by the `ChecksStoreRole` trait centralizing role-checking logic. The one-liner `getStoreId()` method is too trivial to extract further.
- **Gate definitions**: The 7 `ownerOrAdmin` gates are defined in a loop - good DRY practice.

### 10. Error Handling - PASS

- **Middleware**: Uses `abort()` with appropriate HTTP status codes (404, 503, 403)
- **Auth**: Failed login attempts add errors to the Livewire component error bag
- **Rate limiting**: Displays remaining seconds in the error message
- **Store resolution**: Handles null store IDs, non-existent stores, and suspended stores
- **No silent failures**: All error paths produce visible output (HTTP errors or validation messages)

---

## Issues Found

No FAIL-level issues were found. The codebase is clean and well-structured.

## Observations (Not Issues)

1. **Trait location inconsistency**: `BelongsToStore` is in `app/Models/Concerns/` while `ChecksStoreRole` is in `app/Traits/`. Both are traits. Having two trait locations is not a problem, and the distinction (model-specific vs. general-purpose) is reasonable, but the team should be consistent going forward.

2. **ResolveStore suspended check**: Line 44 compares `$store->status->value === 'suspended'` rather than using the enum directly: `$store->status === StoreStatus::Suspended`. Using the enum case comparison would be more type-safe and consistent with how enums are used elsewhere. This works correctly as-is.

3. **Rate limiter key collision**: Both admin and customer login components use `'login|'.$this->getIpAddress()` as the throttle key. If an admin and customer login from the same IP, they share the rate limit budget. In practice this is unlikely to matter, but adding a prefix like `'admin-login|'` or `'customer-login|'` would be more precise.

4. **ChecksStoreRole repeated queries**: Each call to `getStoreRole()` queries the database. If multiple policy checks or gate checks run in a single request, this produces N queries for the same user-store pair. A request-scoped cache (e.g., a static array keyed by `$user->id:$storeId`) could eliminate this. Not a bug, just a future optimization opportunity.

5. **StoreSeeder resilience**: `StoreSeeder::run()` calls `Organization::first()` and `User::first()`. If the seeder runs before `DatabaseSeeder` or `OrganizationSeeder`, these return null and the seeder will error. The call order in `DatabaseSeeder` is correct, so this works, but a null check or explicit `firstOrFail()` would make the dependency clearer.

---

## Self-Assessment

**Overall Code Quality Rating: 8/10**

**Justification**: The Phase 1 codebase is well-structured, follows Laravel conventions consistently, has comprehensive test coverage (235 tests, all passing), uses PHP 8 features appropriately, and has zero Pint violations. The architecture is sound - multi-tenant isolation via StoreScope, clean separation between admin/customer auth, and the ChecksStoreRole trait avoids policy duplication effectively.

Points were deducted for:
- Minor inconsistencies (trait location, enum comparison style) that do not cause bugs but reduce uniformity (-0.5)
- Rate limiter key collision potential (-0.5)
- Repeated database queries in ChecksStoreRole (-0.5)
- No request-scoped optimization for repeated role lookups (-0.5)

**Remaining Risks**:
- Fortify route coexistence: The existing Fortify routes at `/login`, `/register` may conflict with admin auth routes in future phases. This is documented in the dev report as known technical debt.
- Policy auto-discovery: Policies for models that do not yet exist (Order, Fulfillment, etc.) cannot be auto-discovered. They will activate automatically once the models are created in their respective phases.

**Passes but feels fragile**:
- The `StoreSeeder` dependency on execution order (Organization must exist before Store can be created). If someone runs seeders independently, it will break. Consider using `$this->callOnce()` or explicit dependency checks.
- The `customer_password_reset_tokens` table has a composite PK on (email, store_id) without a FK on email. If the customer password reset flow is implemented naively, orphaned tokens could accumulate. This is acceptable for now since the full password reset flow is deferred.
