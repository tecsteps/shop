# Mission: One-Shot Shop System Implementation

Implement the complete shop system specified in `specs/` in a single uninterrupted
session. Do NOT stop, do NOT ask questions.

**The lead agent MUST operate in delegate mode.** The lead coordinates only:
managing the task list, spawning teammates, and keeping the overall picture.
The lead NEVER writes code and NEVER runs commands - everything is delegated.

## Prime Directives

- **Do not stop.** Complete everything in one go.
- **Use team mode.** Create a team in Phase 0. Delegate ALL work to teammates.
- **Follow `specs/09-IMPLEMENTATION-ROADMAP.md`.** It defines the build order and dependencies.
- **If something is ambiguous, decide, document your decision in `specs/progress.md`, and move on.**
- **Production code only.** No shortcuts, no placeholders, no TODOs.

---

## Team Structure

Create a team using `TeamCreate`. The lead operates in **delegate mode**.

### Lead Agent (Orchestrator)
- Manages the task list and spawns teammates
- Follows the dependency graph in `specs/09-IMPLEMENTATION-ROADMAP.md`
- Parallelizes where the graph allows (e.g. Phases 2+3 can run simultaneously)
- NEVER writes code or runs commands

### Implementation Teammates
Spawn teammates as needed. The lead decides how to split work based on the
roadmap's dependency graph. Teammates that work on foundational or risky areas
should be spawned with `mode: "plan"` for plan approval. Others can use
`mode: "bypassPermissions"`.

Each teammate should:
- Read only the spec sections relevant to their work
- Own a clear set of files - no two teammates edit the same file
- Write tests for their own code
- Commit with a domain prefix (e.g. `feat(catalog):`, `feat(admin):`)

Teammates can message each other directly when it helps coordination.

### Quality + Review (after every roadmap phase)
Spawn fresh, short-lived teammates for each concern. They run their tool, fix
what they find, and shut down. Spawn them fresh each time to avoid context overflow.

- **"qa"** - runs `php artisan migrate:fresh --seed`
- **"phpstan"** - runs PHPStan at level max, fixes any errors
- **"deptrac"** - runs Deptrac, fixes any violations
- **"pest"** - runs Pest, fixes any failures
- **"reviewer"** - read-only Explore agent, reviews the code for quality issues

The qa teammate runs migrations first. PHPStan, Deptrac, and Pest can run in
parallel after that. The reviewer runs last.

### Key Constraints
- **SQLite locking**: Only the "qa" teammate runs `php artisan migrate`.
  Other teammates create migration files but never run them.
- **Test credentials**: `admin@acme.test` / `password`, `customer@acme.test` / `password`
- **Task sizing**: Aim for 5-6 tasks per teammate, each a self-contained unit.

---

## Execution

### Phase 0: Orientation

1. Create the team using `TeamCreate`.
2. Read `specs/09-IMPLEMENTATION-ROADMAP.md` for the build order and dependency graph.
3. Read `specs/08-PLAYWRIGHT-E2E-PLAN.md` - this is the acceptance criteria.
4. Read `deptrac.yaml` and `phpstan.neon` for the quality rules.
5. Create `specs/progress.md` with a summary of requirements, build order,
   risk areas, and decisions about ambiguities.
6. Create tasks for roadmap Phase 1 and spawn the first teammate.

Commit: "Phase 0: Implementation plan"

### Roadmap Phases 1-12: Implementation

Follow `specs/09-IMPLEMENTATION-ROADMAP.md` phases 1 through 12 in order,
respecting the dependency graph. Parallelize where the graph allows.

After each roadmap phase completes: run the qa + review cycle.

### Quality Gates

After all roadmap phases are done, run a final quality cycle: migrate, then
PHPStan + Deptrac + Pest in parallel. Repeat until all clean.

Commit: "Quality gates: all passing"

### End-to-End Verification

Spawn a teammate to walk through every scenario in `specs/08-PLAYWRIGHT-E2E-PLAN.md`
using the Playwright MCP. The app is served by Laravel Herd at `https://shop.test`.

For each test, record pass/fail in `specs/progress.md`. Fix all bugs, re-run
failing scenarios, re-run Pest. Repeat until everything passes.

### Final Codebase Review

Spawn a fresh "reviewer" (read-only Explore agent) for a holistic review:

> You are a strict senior PHP/Laravel reviewer. You have NOT seen this code before.
> Review for: architectural violations (check deptrac.yaml), PHPStan compliance,
> test coverage gaps, security issues, missing edge cases, overengineering,
> and Laravel best practices violations.
> Output a numbered list of findings with severity (critical/major/minor).

Fix critical and major findings. Re-run the quality cycle (fresh PHPStan, Deptrac,
Pest teammates). If critical findings were fixed, spawn another fresh reviewer
for a second pass. Max 3 review rounds.

### SonarCloud Verification

1. Push branch and create a PR via GitHub CLI.
2. Check SonarCloud results via the API (the web page requires JS rendering
   that Playwright can't handle reliably):
   ```bash
   # Quality gate pass/fail status with per-metric breakdown
   curl -s "https://sonarcloud.io/api/qualitygates/project_status?projectKey=tecsteps_shop&pullRequest=<PR_NUMBER>" | python3 -m json.tool

   # Detailed metrics (bugs, vulnerabilities, code smells, duplication, etc.)
   curl -s "https://sonarcloud.io/api/measures/component?component=tecsteps_shop&pullRequest=<PR_NUMBER>&metricKeys=new_security_rating,new_reliability_rating,new_maintainability_rating,new_coverage,new_duplicated_lines_density,new_security_hotspots_reviewed,new_bugs,new_vulnerabilities,new_code_smells" | python3 -m json.tool
   ```
   Rating values: 1=A, 2=B, 3=C, 4=D, 5=E.
3. Fix issues, push, recheck. Max 3 iterations.
4. The quality gate MUST pass. All ratings must be A:
   - **Security**: A (0 vulnerabilities)
   - **Reliability**: A (0 bugs)
   - **Maintainability**: A (0 code smells)
   - **Security Hotspots**: 100% reviewed
   - **Duplications**: below 3%

### Final Showcase

Delegate to a teammate to walk through **every scenario** in
`specs/08-PLAYWRIGHT-E2E-PLAN.md` using Playwright MCP as a final verification.

Walk through customer side, admin side, edge cases, and negative paths.
Report pass/fail for each test. Verify no 404 or 500 errors.

Before starting, delete `storage/logs/browser.log` and `storage/logs/laravel.log`
and check both afterwards for errors.

Write results into `specs/qa_admin.md`. If anything fails, fix it and do a full
regression test. Repeat until 100% working.

**If ANY bug appears, fix it, re-run all quality gates, and restart the showcase.**
