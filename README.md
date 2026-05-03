/goal Build the complete Laravel shop system from specs/* until every acceptance criterion is implemented, independently verified, and documented.

You are operating in persistent goal mode. Continue until DONE WHEN is fully satisfied or a real external blocker makes progress impossible.

Never stop because “most” work is done. Never ask for next steps while acceptance criteria remain unmet.

---

GOAL

Deliver a fully working shop system based on specs/*.

---

SOURCE OF TRUTH

- Requirements: specs/*
- Progress log: specs/progress.md
- Local app URL: http://shop.test/
- Existing Laravel architecture and conventions are authoritative unless specs explicitly require otherwise.

---

CORE RULES

- Implement in vertical slices.
- Avoid isolated scaffolding that is not connected to real flows.
- Prefer simple, maintainable Laravel conventions.
- Keep the system working after every slice.
- Verify with objective evidence, not self-judgment.
- Use sub-agents for implementation, review, and independent verification.
- You own final integration quality.

---

DONE WHEN

The goal is complete only when all of the following are true:

1. Every requirement in specs/* is implemented.
2. Every acceptance criterion is mapped to evidence.
3. Pest test suite passes.
4. Playwright MCP verifies critical customer and admin browser flows.
5. Static/code quality checks pass.
6. No critical or high-severity bugs remain after independent QA verification (Chrome/Playwright) AND code review.
7. specs/progress.md contains:
    - implementation status
    - acceptance criteria mapping
    - verification evidence
    - remaining known issues, if any
    - final completion summary
8. All meaningful work is committed.

---

MANDATORY EXECUTION LOOP

Repeat this loop until DONE WHEN is satisfied.

### 0. PLAN

- Read all files in specs/*.
- Build a requirement inventory.
- Extract all acceptance criteria.
- Create or update specs/progress.md with:
    - phased plan
    - dependency map
    - risks
    - verification strategy
    - acceptance criteria checklist
- Ask a planning sub-agent to challenge the plan for gaps.
- Revise the plan before implementation.

### 1. IMPLEMENT NEXT VERTICAL SLICE

Use implementation sub-agents where useful:

- backend agent: models, migrations, services, policies, jobs, APIs
- frontend agent: Blade/Livewire/Inertia/UI flows
- integration agent: wiring, routes, controllers, data flow

Each slice must include:
- data model changes
- business logic
- UI/admin/customer flow
- tests
- seed/demo data where useful
- connected end-to-end behavior

### 2. VERIFY OBJECTIVELY

Run relevant checks after each slice.

Required checks when applicable:

- composer test / pest
- php artisan test
- phpstan or larastan if available
- pint
- rector if useful and safe
- npm test / build if frontend exists
- Playwright MCP browser verification
- database migration fresh test where safe

You may install additional tooling if it improves quality and fits the app, for example:

- larastan/phpstan
- laravel pint
- rector
- pest plugins
- eslint/prettier
- playwright helpers

Do not install tools that create large unrelated rewrites.

### 3. INDEPENDENT QA REVIEW

After each meaningful slice, use separate QA sub-agents.

Required QA roles:

- QA analyst:
    - compares implementation against specs/*
    - checks missing acceptance criteria
    - looks for business logic gaps

- QA engineer:
    - runs or proposes browser/test verification
    - checks edge cases
    - verifies customer and admin flows

- code reviewer:
    - reviews maintainability, Laravel conventions, security, validation, authorization, transactions, and error handling

Important: QA sub-agents must not simply confirm your work. They must actively look for reasons the slice is incomplete or wrong.

### 4. EVALUATE

Compare evidence against DONE WHEN.

For each acceptance criterion, mark one of:

- ✅ implemented and verified
- 🟡 implemented but not fully verified
- 🔴 missing or failing
- ⚪ not started

If any item is 🟡, 🔴, or ⚪, continue.

### 5. FIX

- Fix failed tests.
- Fix browser-flow issues.
- Fix code quality issues.
- Fix spec gaps.
- Re-run verification.

Do not move forward with known critical failures.

### 6. TRACK

Update specs/progress.md after every slice with:

- what changed
- files/areas touched
- decisions made
- tests/checks run
- browser flows verified
- QA findings
- open gaps
- next slice

### 7. COMMIT

Commit meaningful progress with clear messages.

Examples:

- implement product catalog slice
- add cart checkout flow
- verify admin order management
- fix QA findings for promotions

### 8. REPEAT

Continue with the next highest-priority incomplete acceptance criterion.

---

ANTI-BIAS VERIFICATION RULES

Do not rely on your own “looks good” evaluation.

For final verification:

1. Spawn a fresh QA analyst sub-agent with only:
    - specs/*
    - specs/progress.md
    - current app behavior
    - test results

2. Ask it to find missing requirements, contradictions, and unverifiable claims.

3. Spawn a fresh QA engineer sub-agent to independently execute browser flows via Playwright MCP.

4. Spawn a fresh code reviewer sub-agent to inspect:
    - architecture
    - Laravel conventions
    - security
    - validation
    - authorization
    - database integrity
    - test coverage
    - maintainability

5. Fix all critical and high-severity findings.

6. Re-run objective verification.

Only mark complete when independent review finds no blocking issues.

---

QUALITY BAR

Code must be production-quality for a Laravel app:

- clear domain structure
- idiomatic Laravel conventions
- strong validation
- authorization where needed
- safe database migrations
- transactional integrity for order/payment-like flows
- readable tests
- no dead scaffolding
- no duplicated business logic
- no hardcoded demo-only behavior unless explicitly marked
- no ignored failing tests
- no silent exception swallowing

---

FAILURE HANDLING

If stuck or looping:

- stop the current approach
- summarize the loop in specs/progress.md
- ask a planning sub-agent for an alternative
- simplify the slice
- create smaller verification steps
- continue

If context becomes large:

- compress current state into specs/progress.md
- include completed criteria, open criteria, decisions, blockers, and latest verification results
- continue from specs/progress.md

---

PRIORITY ORDER

1. Verified acceptance criteria
2. Functional correctness
3. Customer and admin browser flows
4. Tests and static checks
5. Code quality and maintainability
6. Completeness of progress tracking

---

FINAL RESPONSE REQUIREMENT

When done, provide:

- summary of implemented features
- verification commands run
- Playwright flows verified
- remaining known issues, if any
- final commit hash or commit summary
- confirmation that specs/progress.md is up to date
