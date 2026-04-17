# Mission: One-Shot Shop System Implementation

Implement the complete shop system specified in `specs/` in a single uninterrupted
session. Do NOT stop, do NOT ask questions.

**The lead agent MUST operate in delegate mode EXCLUSIVELY.** The lead's ONLY
responsibilities are:

1. Create tasks in the task list
2. Spawn teammates and assign tasks
3. Set task dependencies (`addBlockedBy` / `addBlocks`)
4. Send coordinating messages to teammates
5. Verify completion via task status (NOT by reading code)

**HARD CONSTRAINT -- the lead NEVER:**
- Reads, edits, or creates implementation files (only `specs/*.md` files are allowed)
- Runs any Bash command except `git status` and `git log`
- Fixes code, tests, or configuration
- Reviews code directly (spawn a reviewer teammate instead)

If the lead finds itself writing code or running commands, it MUST stop immediately,
create a task, and delegate to a teammate instead.

## Prime Directives

- **Do not stop.** Complete everything in one go.
- **Use team mode.** Create a team in Phase 0. Delegate ALL work to teammates.
- **Follow `specs/09-IMPLEMENTATION-ROADMAP.md`.** It defines the build order and dependencies.
- **If something is ambiguous, decide, document your decision in `specs/progress.md`, and move on.**
- **Production code only.** No shortcuts, no placeholders, no TODOs.

---

## Team Structure

Create a team using `TeamCreate`. The lead operates in **delegate mode**.

### Lead Agent (Orchestrator, Delegate-Only)
- Creates and manages tasks in the TaskList
- Spawns teammates based on the dependency graph
- Sets task dependencies to enforce phase ordering
- Sends messages to teammates to coordinate handoffs
- Verifies task completion via task status, not code inspection
- **ZERO implementation work**: no file reads except `specs/*.md` and config files
- **ZERO command execution** except `git status` and `git log`
- Goes IDLE when all available tasks are assigned and in-progress

### Implementation Teammates
Spawn teammates as needed. The lead decides how to split work based on the
roadmap's dependency graph. Teammates that work on foundational or risky areas
should be spawned with `mode: "plan"` for plan approval. Others can use
`mode: "bypassPermissions"`.

Each teammate should:
- Read only the spec sections relevant to their work
- Own a clear set of files -- no two teammates edit the same file
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

## Task Dependency Strategy

Use `TaskUpdate` with `addBlockedBy` and `addBlocks` to enforce sequencing:

1. **Phase ordering**: Phase X+1 implementation tasks are `blockedBy` Phase X QA tasks.
   Next phase literally cannot start until QA passes.
2. **QA ordering**: QA tasks (phpstan, deptrac, pest) are `blockedBy` the QA migrate task.
   The reviewer task is `blockedBy` all other QA tasks.
3. **Post-implementation ordering**: E2E task is `blockedBy` Quality Gates task.
   SonarCloud task is `blockedBy` E2E task. Final Showcase is `blockedBy` SonarCloud task.

This prevents jumping ahead. If a teammate tries to claim a blocked task, TaskList
will show it's blocked. The sequence is enforced automatically.

---

## Done Criteria

### Phase Done
A phase is "done" when ALL of the following are true:
1. All phase implementation tasks in TaskList are `completed`
2. All 5 QA tasks for that phase are `completed`
3. The lead has verified task statuses (not code)

### Quality Cycle Done
A quality cycle is "done" when ALL of the following are true:
1. Fresh migration+seed succeeds (`php artisan migrate:fresh --seed`)
2. PHPStan returns 0 errors
3. Deptrac returns 0 violations
4. Pest returns 0 failures
5. `specs/progress.md` updated with results

If NOT all true: create new fix tasks, assign to teammates, re-run failing checks.

### Final Done
The mission is complete when:
1. All implementation phases (1-12) are done with QA
2. Quality Gates pass (final cycle)
3. E2E Verification passes (100% Playwright scenarios)
4. Final Codebase Review is clean (no critical/major findings)
5. SonarCloud quality gate passes (all A ratings, < 3% duplication)
6. Final Showcase passes (100% scenarios, no log errors)

---

## Lead Idleness and Context Management

The lead MUST go IDLE between phases to preserve context window:

**After each phase's QA passes:**
1. Shut down the QA teammates (send `shutdown_request`)
2. Create next phase tasks and set blockers
3. Spawn teammates for the next phase
4. Send a message: "Phase X QA passed. Phase X+1 tasks are ready."
5. **THEN STOP. Go idle.** Do NOT read code, do NOT plan further.
6. Wait for teammates to report completion via messages.
7. Only re-engage when all next-phase tasks show `completed`.

**Rationale**: Each idle cycle prevents context accumulation, keeping the lead
fresh for the next phase.

---

## Execution

### Phase 0: Orientation

1. Create the team using `TeamCreate`.
2. Read `specs/09-IMPLEMENTATION-ROADMAP.md` for the build order and dependency graph.
3. Read `specs/08-PLAYWRIGHT-E2E-PLAN.md` -- this is the acceptance criteria.
4. Read `deptrac.yaml` and `phpstan.neon` for the quality rules.
5. Create `specs/progress.md` with a summary of requirements, build order,
   risk areas, and decisions about ambiguities.
6. Create tasks for ALL phases upfront (implementation + QA + post-implementation).
   Set all dependencies using `addBlockedBy`.
7. Spawn the first implementation teammate for Phase 1.

Commit: "Phase 0: Implementation plan"

### Roadmap Phases 1-12: Implementation

Follow `specs/09-IMPLEMENTATION-ROADMAP.md` phases 1 through 12 in order,
respecting the dependency graph. Parallelize where the graph allows.

**After each roadmap phase completes, create and run 5 QA tasks:**

1. Create task: "Phase X QA: Run migrate:fresh --seed" -- spawn "qa" teammate
2. Create task: "Phase X QA: Run PHPStan level max" -- `blockedBy` task 1
3. Create task: "Phase X QA: Run Deptrac" -- `blockedBy` task 1
4. Create task: "Phase X QA: Run Pest" -- `blockedBy` task 1
5. Create task: "Phase X QA: Code review" -- `blockedBy` tasks 2, 3, 4

Spawn fresh teammates for each QA task. Tasks 2-4 run in parallel after task 1.
Task 5 runs last.

**Phase X+1 implementation tasks are `blockedBy` task 5.** This ensures the
next phase cannot start until QA is complete.

If QA finds issues: create fix tasks, assign to teammates, re-run failing checks.
Repeat until all 5 QA tasks are `completed`.

### Quality Gates (Phase 13)

Create ONE task: "Quality Gates: Final quality cycle"

Spawn a teammate. They will:
1. Run `php artisan migrate:fresh --seed`
2. Run PHPStan at level max
3. Run Deptrac
4. Run Pest
5. If any failures: fix and re-run. Repeat until all pass.
6. Mark task `completed` when all four pass with zero errors.

Commit: "Quality gates: all passing"

### End-to-End Verification (Phase 14)

Create ONE task: "E2E: Run all Playwright scenarios"
Set `blockedBy` Quality Gates task.

Spawn a teammate. They will:
1. Walk through every scenario in `specs/08-PLAYWRIGHT-E2E-PLAN.md` using Playwright MCP
2. The app is served by Laravel Herd at `https://shop.test`
3. For each test, record pass/fail in `specs/progress.md`
4. If any scenario fails: report which ones failed
5. Lead creates bug-fix tasks, assigns to teammates
6. After fixes: re-run failing scenarios + re-run Pest
7. Repeat until 100% pass. Mark task `completed`.

### Final Codebase Review (Phase 15)

Create ONE task: "Final Review: Holistic code quality audit"
Set `blockedBy` E2E task.

Spawn a fresh "reviewer" (read-only Explore agent) with this prompt:

> You are a strict senior PHP/Laravel reviewer. You have NOT seen this code before.
> Review for: architectural violations (check deptrac.yaml), PHPStan compliance,
> test coverage gaps, security issues, missing edge cases, overengineering,
> and Laravel best practices violations.
> Output a numbered list of findings with severity (critical/major/minor).

If critical or major findings exist:
1. Lead creates fix tasks from the findings list
2. Teammates fix them
3. Re-run quality cycle (fresh PHPStan, Deptrac, Pest teammates)
4. Spawn another fresh reviewer for a second pass
5. Max 3 review rounds total

Mark task `completed` when no critical/major findings remain.

### SonarCloud Verification (Phase 16)

Create ONE task: "SonarCloud: Push PR, verify quality gate, fix issues"
Set `blockedBy` Final Review task.

Spawn a teammate. They will:
1. Push branch: `git push origin <branch>`
2. Create PR: `gh pr create --title "..." --body "..."`
3. Wait ~5 minutes for SonarCloud analysis
4. Query the API:
   ```bash
   # Quality gate status
   curl -s "https://sonarcloud.io/api/qualitygates/project_status?projectKey=tecsteps_shop&pullRequest=<PR_NUMBER>" | python3 -m json.tool

   # Detailed metrics
   curl -s "https://sonarcloud.io/api/measures/component?component=tecsteps_shop&pullRequest=<PR_NUMBER>&metricKeys=new_security_rating,new_reliability_rating,new_maintainability_rating,new_coverage,new_duplicated_lines_density,new_security_hotspots_reviewed,new_bugs,new_vulnerabilities,new_code_smells" | python3 -m json.tool
   ```
   Rating values: 1=A, 2=B, 3=C, 4=D, 5=E.
5. If quality gate PASSES: mark task `completed`.
6. If quality gate FAILS:
   a. Identify failing metrics
   b. Create targeted fix tasks (bugs, code smells, security hotspots, duplication)
   c. Teammates fix them, run quality cycle (PHPStan/Pest/Deptrac)
   d. Push fix commit, recheck SonarCloud
   e. Max 3 total iterations
7. The quality gate MUST pass. All ratings must be A:
   - **Security**: A (0 vulnerabilities)
   - **Reliability**: A (0 bugs)
   - **Maintainability**: A (0 code smells)
   - **Security Hotspots**: 100% reviewed
   - **Duplications**: below 3%

### Final Showcase (Phase 17)

Create ONE task: "Showcase: Walk through all E2E scenarios live"
Set `blockedBy` SonarCloud task.

Spawn ONE showcase teammate. They will:
1. Delete logs: `rm -f storage/logs/browser.log storage/logs/laravel.log`
2. Walk through EVERY scenario in `specs/08-PLAYWRIGHT-E2E-PLAN.md` using Playwright MCP:
   - Customer side: home, browse, product detail, cart, checkout, account
   - Admin side: login, dashboard, product CRUD, order management, settings
   - Edge cases: out of stock, invalid discount, empty cart
   - Negative paths: 404, 403
3. For each scenario, record PASS or FAIL in `specs/qa_results.md`
4. After all scenarios: check `storage/logs/browser.log` and `storage/logs/laravel.log`
5. Report: total scenarios, passed, failed, log errors (yes/no)
6. If 0 failures AND no log errors: mark task `completed`. Showcase is DONE.
7. If failures or log errors exist:
   a. Report which scenarios failed
   b. Lead creates bug-fix tasks
   c. Teammates fix bugs
   d. Re-run quality cycle (PHPStan/Pest/Deptrac)
   e. Re-run showcase from step 1
   f. Max 3 total iterations

---

## Team Lifecycle

**Spawning:**
- Phase 0: Create team, spawn first implementation teammate
- Phases 1-12: Spawn implementation teammates as phases start
- After each phase: Spawn fresh QA teammates (5 per cycle)
- Phases 13-17: Spawn one teammate per phase

**Shutting down:**
- After each phase's QA cycle passes: shut down that cycle's QA teammates
- After Final Showcase passes: send `shutdown_request` to ALL remaining teammates
- Wait for all shutdown acknowledgments
- Mission complete
