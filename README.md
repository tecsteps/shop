You are building a complete shop system in Laravel. You must implement ALL specifications
in `specs/` in a single uninterrupted session - do NOT stop, do NOT ask questions.

IMPORTANT: Use Claude Code **Team Mode**. Ensure the orchestrating main agent is delegating EVERYTHING to teammates and keeps its own context as free as possible to it can focus on the big picture and flow control.

## Prime Directives
- **Do not stop.** Complete everything in one go.
- **Commit after every meaningful iteration** with a descriptive message.
- **Continuously update `specs/progress.md`** with current status after every step.
- **If something is ambiguous, decide, document your decision in progress.md, and move on.**
  Then in the Phase 6 review section, restore the stronger language:
  markdown## Phase 6: Fresh-Eyes Review (Team Mode)

Delegate a **full code review** to a sub-agent with this brief:

> You are a strict senior PHP/Laravel reviewer. **Ignore all prior reasoning.**
> You have NOT seen this code before. Review the entire codebase for:
> 1. Architectural violations (check against deptrac.yaml)
> 2. PHPStan compliance (no mixed, no dynamic props, explicit types everywhere)
> 3. Test coverage gaps (compare specs/testplan.md against tests/)
> 4. Security issues (SQL injection, XSS, CSRF, mass assignment)
> 5. Missing edge cases or undefined behavior
> 6. Overengineering or unnecessary complexity
> 7. Laravel best practices violations
>
> **Identify weaknesses and suggest concrete improvements.**
> Output a numbered list of findings with severity (critical/major/minor).
> Be harsh. Do not approve unless the code is genuinely production-ready.

Then:
- Fix all critical and major findings.
- Re-run PHPStan, Deptrac, Pest.
- If critical findings were fixed, delegate to **another fresh sub-agent** for a second review.
- Max 3 review rounds total. No feature is complete without independent review.
  And the showcase section should keep the explicit restart:
  markdownIf ANY bug appears during the showcase, fix it, re-run ALL quality gates,
  and **restart the review meeting from the beginning**.
  Want me to compile the full corrected version, or are there other items you noticed that were lost or weakened?2 / 2Write the full prompt11:05Here's the complete optimized prompt:
  markdown# Mission: One-Shot Shop System Implementation

You are building a complete shop system in Laravel. You must implement ALL specifications
in `specs/` in a single uninterrupted session - do NOT stop, do NOT ask questions.
Use **team mode** for parallelizable work.

## Prime Directives

- **Do not stop.** Complete everything in one go without interruption.
- **Use team mode.** Delegate parallelizable work to sub-agents.
- **Commit after every meaningful iteration** with a descriptive commit message.
- **Continuously update `specs/progress.md`** with current status after every step.
- **If something is ambiguous, decide, document your decision in progress.md, and move on.**
- **You are writing production PHP (Laravel) code.** No shortcuts, no placeholders, no TODOs.

---

## Phase 0: Orientation (DO THIS FIRST)

1. Read every file in `specs/` thoroughly. Internalize all requirements.
2. Read `specs/testplan.md` -- this is your acceptance criteria source of truth.
3. Read `deptrac.yaml` to understand the architectural layer rules.
4. Read `phpstan.neon` to understand the analysis configuration.
5. Create `specs/progress.md` with:
    - Total number of features/requirements identified
    - Proposed implementation order (dependencies first)
    - Risk areas (complex logic, integration points)
    - Decisions made about any ambiguities

Commit: "Phase 0: Implementation plan"

---

## Phase 1: Core Domain & Database

Implement domain models, migrations, seeders, and relationships.

- All Eloquent models must have fully typed properties (no `$guarded = []` shortcuts).
- All models must have proper PHPDoc AND native return types.
- Run after completion:
```bash
  php artisan migrate:fresh --seed
  vendor/bin/phpstan analyse --level=max
  vendor/bin/deptrac analyse
```
- Fix any issues before proceeding.

Commit progress after every meaningful iteration.

---

## Phase 2: Business Logic & Services

Implement services, actions, policies, form requests, events.

- Every service method must have explicit parameter types and return types.
- No `mixed` anywhere. No dynamic properties. No suppressed errors.
- Write Pest **unit tests** for every service/action as you go.
- Run after completion:
```bash
  vendor/bin/pest --parallel
  vendor/bin/phpstan analyse --level=max
  vendor/bin/deptrac analyse
```
- Fix any issues before proceeding.

Commit progress after every meaningful iteration.

---

## Phase 3: Controllers, Routes & Views

Implement all customer-facing and admin-facing routes, controllers, views.

- Write Pest **feature/integration tests** for every route.
- Cover: success paths, validation failures, authorization, edge cases.
- Run after completion:
```bash
  vendor/bin/pest --parallel
  vendor/bin/phpstan analyse --level=max
  vendor/bin/deptrac analyse
```
- Fix any issues before proceeding.

Commit progress after every meaningful iteration.

---

## Phase 4: End-to-End Verification with Playwright

1. Start the app: `php artisan serve --port=8000 &`
2. Using the **Playwright MCP** (non-scripted, browser-based), walk through EVERY
   scenario in `specs/testplan.md`:
    - Navigate as a customer: browse, search, add to cart, checkout, view orders.
    - Navigate as an admin: manage products, view orders, update statuses.
    - Test edge cases: empty cart checkout, invalid inputs, unauthorized access.
3. For EACH test plan item, record in `specs/progress.md`:
    - ✅ or ❌ status
    - What you observed
    - Bug description if failed
4. Fix ALL bugs found. Re-run the failing Playwright scenarios to confirm fixes.
5. Re-run the full Pest suite to ensure fixes didn't break anything:
```bash
   vendor/bin/pest --parallel
   vendor/bin/phpstan analyse --level=max
```
6. Repeat until every testplan item is ✅.

Commit progress after every meaningful iteration.

---

## Phase 5: Quality Gates

Run all quality tools and fix every issue:
```bash
# 1. PHPStan max level -- must be clean
vendor/bin/phpstan analyse --level=max

# 2. Deptrac -- must be clean
vendor/bin/deptrac analyse

# 3. PHPMetrics report -- review for issues
vendor/bin/phpmetrics --report-html=report src
# Open report/index.html via Playwright MCP, check for red flags
# (high complexity, low maintainability, coupling issues)

# 4. Full test suite with coverage
vendor/bin/pest --parallel --coverage
```

Fix anything found. Repeat until all four are clean.

Commit progress after every meaningful iteration.

---

## Phase 6: Fresh-Eyes Review (Team Mode)

Delegate a **full code review** to a sub-agent with this brief:

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
- If critical findings were fixed, delegate to **another fresh sub-agent** for a second review.
- Max 3 review rounds total.
- **No feature is complete without independent review.**

Commit progress after every meaningful iteration.

---

## Phase 7: SonarCloud Verification

1. Push your branch and create a PR using GitHub CLI:
```bash
   gh pr create --title "Shop system implementation" --body "Full shop implementation"
```
2. Wait 60 seconds for SonarCloud analysis.
3. Check results using Playwright MCP:
   `https://sonarcloud.io/summary/new_code?id=tecsteps_shop&pullRequest=<PR_NUMBER>`
4. If there are issues:
    - Fix them.
    - Push again.
    - Wait 60 seconds, recheck.
    - Max 3 iterations.
5. Target: 0 issues, A rating across all dimensions.

Commit and push after every fix.

---

## Phase 8: Final Review Meeting & Showcase

Present a structured showcase to me:

### Customer Side
Walk through (via Playwright MCP) every customer-facing feature, narrating what you are doing
and which requirement/acceptance criterion it satisfies.

### Admin Side
Walk through (via Playwright MCP) every admin-facing feature, narrating what you are doing
and which requirement/acceptance criterion it satisfies.

### QA Self-Verification
Before finalizing, explicitly:
- List each acceptance criterion from specs/testplan.md.
- Confirm how it is implemented.
- Confirm which test covers it.
- Confirm Playwright E2E verification status.
- Validate edge cases and negative paths.
- Ensure no undefined behavior exists.

### Quality Summary
Report final status of:
- Pest: X tests, X assertions, all passing
- PHPStan: level max, 0 errors
- Deptrac: 0 violations
- PHPMetrics: summary of key metrics
- SonarCloud: rating summary
- Playwright E2E: all testplan items ✅

**If ANY bug appears during the showcase, fix it, re-run ALL quality gates,
and restart the review meeting from the beginning.**

---

## Strict Rules (Apply at ALL Times)

### Code Quality
- **No `mixed` types.** Ever. Anywhere.
- **No `@phpstan-ignore` or error suppression.**
- **No `$guarded = []`.** Use explicit `$fillable`.
- **Explicit return types on every method.**
- **Fully typed properties** with constructor promotion where appropriate.
- **No dynamic properties.**
- **No relying on docblocks to hide real type problems.**

### Architecture
- Respect architectural layers defined in `deptrac.yaml`.
- No cross-layer violations. No circular dependencies.
- If a dependency is required, introduce an interface in the correct layer.
- Do not modify architecture unless explicitly instructed.

### Testing
- Every feature must include automated Pest tests.
- Include both unit and integration tests when appropriate.
- Cover success paths, failure paths, and edge cases.
- Tests must be deterministic.
- Tests validate behavior, not implementation details.

### Process
- Commit after every meaningful iteration with a descriptive message.
- Update `specs/progress.md` continuously.
- Every commit must pass PHPStan + Pest before being made.
- Do not stop or ask questions. If ambiguous, decide and document.
