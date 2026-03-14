# Mission

Implement the complete shop system defined in `specs/`. Work through `specs/09-IMPLEMENTATION-ROADMAP.md` phase by phase (1-12), referencing the other spec files as needed. Do not stop until all phases are complete and verified. Use team mode for all work.

## Upfront Task List

Before writing any code, read all spec files and create the full task list covering all 12 phases. Every task across every phase must be listed upfront so progress can be tracked from the start.

## Phase Lifecycle

Each phase follows this strict sequence:

1. **Planning** -- Break the phase into tasks, assign to teammates, agree on approach.
2. **Development** -- Implement the phase deliverables. Parallelize aggressively -- backend and UI teammates can work simultaneously when they own different files.
3. **Automated Testing & Fixing** -- Write Pest unit/feature tests, run them, fix failures until all pass.
4. **Manual Test Plan** -- Write a comprehensive manual test plan for the phase covering every user-facing flow and edge case. Maintain this in `specs/test-plan.md`. Test cases must map to specific acceptance criteria from the specs and track: test name, what it verifies, pass/fail status, and the spec section it covers.
5. **Browser Verification** -- The agent walks through every manual test case using Playwright MCP (non-scripted, interactive browser navigation). No test scripts -- the agent clicks, fills forms, and visually confirms behavior.
6. **Log & Exception Check** -- Review application logs and browser console logs for errors and exceptions. Fix all issues found.
7. **Verify Background Jobs** -- Confirm all queued jobs, scheduled tasks, and cron jobs execute correctly and without errors.
8. **Fix & Repeat** -- Fix any issues found during browser verification or log checks, then repeat steps 5-7 until 100% of manual test cases pass with zero exceptions.
9. **Commit** -- Run `vendor/bin/pint --dirty`, then commit with a message like `Phase N: <summary>`. Update `specs/progress.md`.

Do not advance to the next phase until all steps are complete.

## Final Regression

After all 12 phases are done:

1. Run `php artisan test` -- 100% of Pest tests must pass.
2. Run `vendor/bin/pint --dirty` -- zero formatting issues.
3. Audit the test plan against every spec file and confirm full coverage. Fill any gaps.
4. Re-execute every manual test case from every phase using Playwright MCP. Fix any issues found and re-run the full regression until it passes with zero failures.

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

## Key Constraints (from specs)

- All monetary values are integers in cents -- never use floats for money.
- Multi-tenant: every tenant-scoped table has `store_id`. Hostname resolves to store.
- SQLite with WAL mode. Single currency per store.
- Admin: Livewire v4 + Flux UI Free only (no Pro components).
- Storefront: Tailwind v4, dark mode, mobile-first, WCAG 2.2 AA.
- Auth: session-based for web, Sanctum for API. Generic error messages on login failure.
- Seeder data must support all E2E tests (see `specs/07-SEEDERS-AND-TEST-DATA.md` for required records).
- Run `vendor/bin/pint --dirty` before every commit.
