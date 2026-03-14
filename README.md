# Mission

Implement the complete shop system defined in `specs/`. Work through `specs/09-IMPLEMENTATION-ROADMAP.md` phase by phase (1-12), referencing the other spec files as needed. Do not stop until all phases are complete and verified. Use team mode for all work.

## Upfront Task List

Before writing any code, read all spec files and create the full task list covering all 12 phases. Every task across every phase must be listed upfront so progress can be tracked from the start.

## Phase Lifecycle

Each phase follows this strict sequence:

1. **Planning** -- Break the phase into tasks, assign to teammates, agree on approach.
2. **Development** -- Implement the phase deliverables.
3. **Automated Testing & Fixing** -- Write Pest unit/feature tests, run them, fix failures until all pass.
4. **Manual Test Plan** -- Write a comprehensive manual test plan for the phase covering every user-facing flow and edge case.
5. **Browser Verification** -- The agent walks through every manual test case using Playwright MCP (non-scripted, interactive browser navigation). No test scripts -- the agent clicks, fills forms, and visually confirms behavior.
6. **Fix & Repeat** -- Fix any issues found during browser verification, then repeat step 5 until 100% of manual test cases pass.

Do not advance to the next phase until all steps are complete.

## Final Regression

After all 12 phases are done, run a full regression: re-execute every manual test case from every phase using Playwright MCP. Fix any issues found and re-run the full regression until it passes with zero failures.

## Team Mode Rules

**The team lead is strictly an orchestrator.** It must never write code, run tests, do research, or verify results directly. Every unit of work must be delegated to a teammate.

### Delegation Discipline

- Always maintain a task list to track and assign work.
- Use team-mode (not sub-agents) for all delegation.
- Require plan mode for complex teammate tasks. Review and approve plans before implementation starts.
- Set up each teammate with focused, well-scoped context. Proactively split work to prevent context overflow. When spawning a teammate, include all relevant spec excerpts and file paths they need -- they do not inherit your conversation history.
- Keep the lead's own context clean by offloading all implementation details to teammates.
- Aim for 3-5 active teammates. Split work so teammates own separate files to avoid conflicts.
- When a teammate finishes, assign them the next available task immediately. Do not let teammates sit idle.

### Team Structure

Organize teammates by concern, not by phase. Example roles:

- **Backend**: Models, migrations, middleware, services, business logic
- **Admin UI**: Livewire components, admin views, Flux UI integration
- **Storefront UI**: Customer-facing Blade templates, Livewire components, Tailwind styling
- **QA**: Pest feature/unit tests, test data verification, bug reports back to lead

Teammates may rotate roles between phases as needed. The QA teammate is permanent and grows the test plan throughout the project.

## Phase Execution

For each phase in the roadmap:

1. **Delegate implementation** to the appropriate teammates with clear scope.
2. **Delegate tests** for that phase's deliverables to the QA teammate in parallel.
3. **Gate**: Do not advance to the next phase until all tests pass and the QA teammate confirms.
4. **Commit**: After each phase passes its gate, commit with a message like `Phase N: <summary>`.
5. **Update progress**: Keep `specs/progress.md` current after every phase.

Within a phase, parallelize aggressively. Backend and UI teammates can work simultaneously when they own different files.

## Verification Strategy

This is the most critical part. The project is only done when every feature is tested and confirmed working.

### Test Plan (Built Incrementally)

The QA teammate must maintain a living test plan in `specs/test-plan.md`. This file grows phase by phase:

- After each phase, the QA teammate adds test cases covering that phase's deliverables.
- Test cases must map to specific acceptance criteria from the specs.
- The test plan must track: test name, what it verifies, pass/fail status, and the spec section it covers.
- By phase 12, the test plan must cover every acceptance criterion across all spec files.

### Test Layers

1. **Pest Unit/Feature Tests**: Cover all business logic, models, middleware, policies, validation, calculations (monetary math, discounts, tax, shipping). These run fast and catch regressions early. Write them alongside implementation in every phase.
2. **Pest Browser Tests (Playwright)**: Cover all user-facing flows per `specs/08-PLAYWRIGHT-E2E-PLAN.md`. These confirm the UI works end-to-end. The target is 143+ browser tests across 18 suites.

### Final Verification (Phase 12)

Before declaring done:

1. Run `php artisan test` -- 100% of tests must pass.
2. Run the full Pest browser test suite -- all 143 E2E tests must pass.
3. Run `vendor/bin/pint --dirty` -- zero formatting issues.
4. The QA teammate must audit the test plan against every spec file and confirm full coverage. Any gaps must be filled.

## Review Meeting

When all phases are complete and all tests pass, conduct a review meeting:

- Walk through every feature (admin-side and customer-side) using the browser via Playwright MCP.
- Demonstrate each feature works by navigating the actual UI.
- If any bug is found, fix it, re-run all tests, and restart the review from the beginning.
- The review is only complete when the full walkthrough finishes with zero bugs.

## Key Constraints (from specs)

- All monetary values are integers in cents -- never use floats for money.
- Multi-tenant: every tenant-scoped table has `store_id`. Hostname resolves to store.
- SQLite with WAL mode. Single currency per store.
- Admin: Livewire v4 + Flux UI Free only (no Pro components).
- Storefront: Tailwind v4, dark mode, mobile-first, WCAG 2.2 AA.
- Auth: session-based for web, Sanctum for API. Generic error messages on login failure.
- Seeder data must support all E2E tests (see `specs/07-SEEDERS-AND-TEST-DATA.md` for required records).
- Run `vendor/bin/pint --dirty` before every commit.
