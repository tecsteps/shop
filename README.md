# Mission: One-Shot Shop System Implementation

You are building a complete shop system in Laravel. You must implement ALL specifications
in `specs/` in a single uninterrupted session - do NOT stop, do NOT ask questions.

**The lead agent MUST operate in delegate mode.** After creating the team, the lead
coordinates only: creating tasks, assigning work, running quality gates, reviewing.
The lead NEVER writes application code directly.

## Prime Directives

- **Do not stop.** Complete everything in one go without interruption.
- **Use team mode.** Create a team immediately in Phase 0. Delegate ALL implementation to teammates.
- **Commit after each wave completes** with domain-prefixed messages (e.g. `feat(catalog): ...`).
- **Continuously update `specs/progress.md`** with current status after every wave.
- **If something is ambiguous, decide, document your decision in progress.md, and move on.**
- **You are writing production PHP (Laravel) code.** No shortcuts, no placeholders, no TODOs.

---

## Team Topology

Create a team immediately in Phase 0 using `TeamCreate`. The lead MUST operate in
**delegate mode** (coordination only - no code writing). For Wave 1 (foundation) and
Wave 3 (checkout), spawn teammates with `mode: "plan"` to require plan approval before
implementation. For other waves, use `mode: "bypassPermissions"` to maximize parallelism.

### Lead Agent (Orchestrator)
- Creates the team and task list
- Reads `specs/09-IMPLEMENTATION-ROADMAP.md` for the master plan
- Creates tasks with dependencies for each wave
- Assigns tasks to teammates
- Runs `php artisan migrate:fresh --seed` between waves
- Runs quality gates between waves
- Runs Playwright E2E at the end
- NEVER writes application code directly

### Teammate Roles (spawn as needed per wave)

**Wave 1 - Foundation (1 teammate: "foundation")**
- Migrations, models, enums, middleware, auth config, seeders
- Owns: `app/Models/`, `app/Enums/`, `database/`, `config/auth.php`, `bootstrap/app.php`
- Skills to activate: `developing-with-fortify`
- Read: `specs/01-DATABASE-SCHEMA.md`, `specs/06-AUTH-AND-SECURITY.md`, `specs/07-SEEDERS-AND-TEST-DATA.md`
- Test credentials to seed: `admin@acme.test` / `password`, `customer@acme.test` / `password`

**Wave 2 - Parallel (2 teammates: "catalog" + "storefront")**
- "catalog": Products, variants, inventory, collections, services
  - Owns: `app/Models/Product*.php`, `app/Services/Product*`, `app/Services/Inventory*`
  - Skills: `pest-testing`
  - Read: `specs/01-DATABASE-SCHEMA.md` (product tables), `specs/05-BUSINESS-LOGIC.md` (product/inventory sections)
- "storefront": Themes, pages, navigation, Blade layouts, storefront Livewire components
  - Owns: `app/Livewire/Storefront/`, `resources/views/storefront/`, `resources/views/layouts/`
  - Skills: `livewire-development`, `fluxui-development`, `tailwindcss-development`
  - Read: `specs/04-STOREFRONT-UI.md`

**Wave 3 - Cart/Checkout (1 teammate: "checkout")**
- Cart, checkout, discounts, shipping, taxes, pricing engine
- Owns: `app/Services/Cart*`, `app/Services/Checkout*`, `app/Services/Pricing*`, `app/Livewire/Checkout/`
- Skills: `livewire-development`, `pest-testing`
- Read: `specs/05-BUSINESS-LOGIC.md` (cart/checkout/pricing sections)

**Wave 4 - Parallel (2 teammates: "orders" + "accounts")**
- "orders": Payments (mock PSP), orders, fulfillment, refunds
  - Owns: `app/Services/Order*`, `app/Services/Payment*`, `app/Livewire/Orders/`
  - Skills: `pest-testing`
  - Read: `specs/05-BUSINESS-LOGIC.md` (order/payment sections)
- "accounts": Customer auth, account pages, address management
  - Owns: `app/Livewire/Account/`, `resources/views/account/`
  - Skills: `livewire-development`, `fluxui-development`, `developing-with-fortify`
  - Read: `specs/06-AUTH-AND-SECURITY.md`, `specs/04-STOREFRONT-UI.md` (account sections)

**Wave 5 - Parallel (2 teammates: "admin-core" + "admin-catalog")**
- "admin-core": Admin layout, dashboard, order management, customer management
  - Owns: `app/Livewire/Admin/Dashboard*`, `app/Livewire/Admin/Order*`, `app/Livewire/Admin/Customer*`
  - Skills: `livewire-development`, `fluxui-development`, `tailwindcss-development`
  - Read: `specs/03-ADMIN-UI.md` (dashboard/orders/customers sections)
- "admin-catalog": Product management, collections, discounts, settings, themes, pages
  - Owns: `app/Livewire/Admin/Product*`, `app/Livewire/Admin/Collection*`, `app/Livewire/Admin/Discount*`, `app/Livewire/Admin/Settings*`
  - Skills: `livewire-development`, `fluxui-development`
  - Read: `specs/03-ADMIN-UI.md` (products/collections/discounts/settings sections)

**Wave 6 - Parallel (up to 3 teammates: "search" + "analytics" + "webhooks")**
- Features from remaining spec sections not yet implemented

**Wave 7 - Quality + Review (1 teammate: "reviewer")**
- Fresh-eyes code review (read-only Explore agent, outputs findings list)

## Coordination Protocol

1. **File ownership**: Each teammate owns specific directories. NEVER have two
   teammates edit the same file.
2. **Migrations**: Teammates create migration FILES but do NOT run them. The lead
   runs `php artisan migrate:fresh --seed` between waves.
3. **Task dependencies**: Lead creates tasks with `addBlockedBy` relationships.
   Teammates claim unblocked tasks via `TaskUpdate`.
4. **Skill activation**: Each teammate prompt must include which skills to activate
   (see teammate roles above for specifics).
5. **Spec reading**: Each teammate reads ONLY their relevant spec sections, not all
   specs. The lead tells them exactly which spec file + section to read.
6. **Commits**: Each teammate commits with domain prefix: `feat(catalog):`,
   `feat(storefront):`, `feat(admin):`, etc.
7. **Tests**: Each teammate writes tests for their own code and runs them before
   reporting completion.
8. **Task sizing**: Break each wave into 5-6 tasks per teammate. Each task should be
   a self-contained unit (e.g., "Create Product model + migration + factory",
   "Build cart service with tests").
9. **SQLite locking**: Only the orchestrator runs `php artisan migrate`. Teammates
   create migration files but never run them.

---

## Phase 0: Orientation (DO THIS FIRST)

1. Create the team using `TeamCreate`.
2. Read `specs/09-IMPLEMENTATION-ROADMAP.md` for the master plan.
3. Read `specs/testplan.md` - this is your acceptance criteria source of truth.
4. Read `deptrac.yaml` to understand the architectural layer rules.
5. Read `phpstan.neon` to understand the analysis configuration.
6. Create `specs/progress.md` with:
    - Total number of features/requirements identified
    - Proposed wave-based implementation order
    - Risk areas (complex logic, integration points)
    - Decisions made about any ambiguities
7. Create tasks for Wave 1 and spawn the "foundation" teammate.

Commit: "Phase 0: Implementation plan"

---

## Phase 1: Core Domain & Database (Wave 1)

Delegate to "foundation" teammate. They implement domain models, migrations, seeders,
and relationships.

- All Eloquent models must have fully typed properties (no `$guarded = []` shortcuts).
- All models must have proper PHPDoc AND native return types.
- Seed test accounts: `admin@acme.test` / `password` and `customer@acme.test` / `password`.
- After teammate completes, the lead runs:
```bash
php artisan migrate:fresh --seed
vendor/bin/phpstan analyse --level=max
vendor/bin/deptrac analyse
```
- Fix any issues before proceeding to Wave 2.

---

## Phase 2: Business Logic & Storefront (Waves 2-4)

Spawn teammates per wave as described in Team Topology.

- Every service method must have explicit parameter types and return types.
- No `mixed` anywhere. No dynamic properties. No suppressed errors.
- Teammates write Pest tests for their code as they go.
- Between waves, the lead runs:
```bash
php artisan migrate:fresh --seed
vendor/bin/pest --parallel
vendor/bin/phpstan analyse --level=max
vendor/bin/deptrac analyse
```
- Fix any issues before proceeding to the next wave.

---

## Phase 3: Admin Panel (Wave 5-6)

Spawn admin teammates per wave as described in Team Topology.

- Write Pest feature/integration tests for every admin route.
- Cover: success paths, validation failures, authorization, edge cases.
- After completion, the lead runs quality gates as above.

---

## Phase 4: Quality Gates

Run all quality tools and fix every issue:
```bash
# 1. PHPStan max level - must be clean
vendor/bin/phpstan analyse --level=max

# 2. Deptrac - must be clean
vendor/bin/deptrac analyse

# 3. Full test suite with coverage
vendor/bin/pest --parallel --coverage
```

Fix anything found. Repeat until all three are clean.

Commit: "Quality gates: all passing"

---

## Phase 5: End-to-End Verification with Playwright

1. The app is served by Laravel Herd at `https://shop.test`. Do NOT run `php artisan serve`.
2. Using the **Playwright MCP** (non-scripted, browser-based), walk through EVERY
   scenario in `specs/testplan.md`:
    - Navigate as a customer: browse, search, add to cart, checkout, view orders.
    - Navigate as an admin: manage products, view orders, update statuses.
    - Test edge cases: empty cart checkout, invalid inputs, unauthorized access.
    - Login credentials: `admin@acme.test` / `password`, `customer@acme.test` / `password`
3. For EACH test plan item, record in `specs/progress.md`:
    - Pass/fail status
    - What you observed
    - Bug description if failed
4. Fix ALL bugs found. Re-run the failing Playwright scenarios to confirm fixes.
5. Re-run the full Pest suite to ensure fixes didn't break anything.
6. Repeat until every testplan item passes.

---

## Phase 6: Fresh-Eyes Review (Team Mode)

Delegate a **full code review** to a teammate-agent ("reviewer", read-only Explore agent)
with this brief:

> You are a strict senior PHP/Laravel reviewer. **Ignore all prior reasoning.**
> You have NOT seen this code before. Review the entire codebase for:
>
> 1. Architectural violations (check against deptrac.yaml)
> 2. PHPStan compliance (no mixed, no dynamic props, explicit types everywhere)
> 3. Test coverage gaps (compare specs/testplan.md against tests/)
> 4. Security issues (SQL injection, XSS, CSRF, mass assignment)
> 5. Missing edge cases or undefined behavior
> 6. Overengineering or unnecessary complexity
> 7. Laravel best practices violations
>
> **Identify weaknesses. Identify architectural drift. Suggest concrete improvements.**
> Output a numbered list of findings with severity (critical/major/minor).
> Be harsh. Do not approve unless the code is genuinely production-ready.

Then:
- Fix all critical and major findings.
- Re-run PHPStan, Deptrac, Pest.
- If critical findings were fixed, delegate to **another fresh teammate-agent** for a second review.
- Max 3 review rounds total.
- **No feature is complete without independent review.**

---

## Phase 7: SonarCloud Verification

1. Push your branch and create a PR using GitHub CLI:
```bash
gh pr create --title "Shop system implementation" --body "Full shop implementation"
```
2. Wait some moments for SonarCloud analysis.
3. Check results using Playwright MCP:
   `https://sonarcloud.io/summary/new_code?id=tecsteps_shop&pullRequest=<PR_NUMBER>`
4. If there are issues:
    - Fix them.
    - Push again.
    - Wait, recheck.
    - Max 3 iterations.
5. Target: 0 issues, A rating across all dimensions. The overall quality gate MUST NOT be "failed"

---

## Phase 8: Final Review Meeting & Showcase

Present a structured showcase by walking through the **entire `specs/testplan.md`**
using Playwright MCP. This is both the showcase AND the final verification.

### Customer Side
Walk through (via Playwright MCP) every customer-facing scenario from the testplan,
narrating what you are doing and which requirement/acceptance criterion it satisfies.
Login: `customer@acme.test` / `password`

### Admin Side
Walk through (via Playwright MCP) every admin-facing scenario from the testplan,
narrating what you are doing and which requirement/acceptance criterion it satisfies.
Login: `admin@acme.test` / `password`

### Edge Cases & Negative Paths
Walk through (via Playwright MCP) every edge case and negative path from the testplan:
invalid inputs, unauthorized access, empty states, boundary conditions.

For EACH testplan item, report pass/fail with what you observed.

Make sure all links (storefront + admin) are working and there are no 404 or 500 errors.

Before the final review, delete storage/logs/browser.log and storage/logs/laravel.log
and check both files afterwards for errors.

### QA Flow & Fix

Write test results into specs/qa_admin.md
If something is not working, fix it and do a full regression test again. Repeat until
it's 100% working.

### Quality Summary
Report final status of:
- Pest: X tests, X assertions, all passing
- PHPStan: level max, 0 errors
- Deptrac: 0 violations
- SonarCloud: rating summary
- Playwright E2E: all testplan items passing

**If ANY bug appears during the showcase, fix it, re-run ALL quality gates,
and restart the review meeting from the beginning.**
