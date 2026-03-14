# Mission

Implement the complete shop system defined in `specs/`. Work through `specs/09-IMPLEMENTATION-ROADMAP.md` phase by phase (1-12), referencing the other spec files as needed. Do not stop until all phases are complete and verified.

## Upfront Task List

Before writing any code, read all spec files and create the full task list covering all 12 phases. For every phase, pre-define:

1. The implementation tasks (grouped by teammate role).
2. The lifecycle steps (see below) -- each phase carries the same 9-step sequence, and every step must appear as a trackable task.

This means the task list is not just "what to build" but also "how to verify it." Every lifecycle step for every phase is a first-class task from day one, so nothing gets skipped or forgotten.

Overall, the task list will have ~120-130 tasks (10*12 plus final tasks).

## Phase Lifecycle

Each phase follows this strict sequence. Do not advance to the next phase until all steps are complete.

1. **Plan** -- Break the phase into tasks, assign to teammates, agree on approach.
2. **Develop** -- Implement the phase deliverables. Parallelize aggressively -- teammates work on separate files simultaneously.
3. **Automated Tests** -- Write Pest unit/feature tests, run them, fix failures until all pass.
4. **Manual Test Plan** -- Write a comprehensive manual test plan for the phase covering every user-facing flow and edge case. Maintain this in `specs/test-plan.md`. Test cases must map to specific acceptance criteria from the specs and track: test name, what it verifies, pass/fail status, and the spec section it covers.
5. **Browser Verification** -- Walk through every manual test case using Playwright MCP (non-scripted, interactive browser navigation). Click, fill forms, and visually confirm behavior.
6. **Log & Exception Check** -- Review application logs and browser console logs for errors and exceptions. Fix all issues found.
7. **Verify Background Jobs** -- Confirm all queued jobs, scheduled tasks, and cron jobs execute correctly and without errors.
8. **Fix & Repeat** -- Fix any issues found in steps 5-7, then repeat steps 5-7 until 100% of manual test cases pass with zero exceptions.
9. **Commit** -- Run `vendor/bin/pint --dirty`, commit with a message like `Phase N: <summary>`, update `specs/progress.md`.

## Final Regression

After all 12 phases are done:

1. Run `php artisan test` -- 100% of Pest tests must pass.
2. Run `vendor/bin/pint --dirty` -- zero formatting issues.
3. Audit the test plan against every spec file and confirm full coverage. Fill any gaps.
4. Re-execute every manual test case from every phase using Playwright MCP. Fix any issues and re-run until the full regression passes with zero failures.

## Team Mode

All work uses team mode. The team lead is strictly an orchestrator -- it never writes code, runs tests, does research, or verifies results directly. Every unit of work is delegated to a teammate.

### Delegation

- Use team mode (not sub-agents) for all delegation.
- Require plan mode for complex teammate tasks. Review and approve plans before implementation starts.
- Set up each teammate with focused, well-scoped context. Include all relevant spec excerpts and file paths -- teammates do not inherit the lead's conversation history.
- Proactively split work to prevent context overflow.
- Aim for 3-5 active teammates. Split work so teammates own separate files to avoid conflicts.
- When a teammate finishes, assign the next available task immediately.

### Team Structure

Organize teammates by concern. To avoid context overflow you must use a new set of teammates per phase. Example roles:

- **Backend**: Models, migrations, middleware, services, business logic
- **Admin UI**: Livewire components, admin views, Flux UI integration
- **Storefront UI**: Customer-facing Blade templates, Livewire components, Tailwind styling
- **QA**: Pest feature/unit tests, test data verification, bug reports back to lead
