/goal Build the complete Laravel shop system from specs/* until all acceptance criteria are implemented, independently verified, and documented.

Persistent goal mode. Continue until DONE WHEN is fully satisfied or a real blocker occurs. Do not stop early or ask for next steps.

---

GOAL
Fully working shop system based on specs/*.

---

SOURCE OF TRUTH
- Requirements: specs/*
- Progress: specs/progress.md
- App: http://shop.test/
- Follow existing Laravel architecture unless specs require otherwise.

---

CORE RULES
- Build vertical slices (end-to-end).
- Keep system runnable after each slice.
- No partial implementations.
- Prefer simple, idiomatic Laravel.
- Verification must be evidence-based.
- Use sub-agents for build + independent QA.
- You own final integration and quality.

---

DONE WHEN
All must be true:

1. All specs/* requirements implemented.
2. Each acceptance criterion mapped to evidence.
3. Pest tests pass.
4. Playwright MCP verifies key customer + admin flows.
5. Code quality checks pass (e.g. pint, phpstan/larastan if added).
6. No critical/high bugs after independent QA.
7. specs/progress.md includes:
    - plan, status
    - acceptance checklist
    - verification evidence
    - decisions + open issues
    - completion summary
8. Meaningful commits exist.

---

LOOP (REPEAT UNTIL DONE)

0. PLAN
- Read specs/*
- Extract all acceptance criteria
- Build phased plan in specs/progress.md
- Include dependencies, risks, verification strategy
- Let a planning sub-agent challenge gaps, then refine

1. IMPLEMENT
- Build next vertical slice using sub-agents:
  backend, frontend, integration
- Include logic, UI, tests, real flow

2. VERIFY (OBJECTIVE)
   Run when applicable:
- pest / php artisan test
- pint
- phpstan/larastan (install if useful)
- build/tests for frontend
- Playwright MCP flows

3. INDEPENDENT QA
   Use separate sub-agents:
- QA analyst: compare vs specs, find missing criteria
- QA engineer: verify flows, edge cases
- code reviewer: Laravel conventions, security, validation, structure

They must look for failures, not confirm success.

4. EVALUATE
   Mark each acceptance criterion:
- done + verified
- partial
- missing
  Continue if anything not fully verified.

5. FIX
   Resolve all issues, re-run verification.

6. TRACK
   Update specs/progress.md:
- changes, decisions
- tests + flows run
- QA findings
- open gaps
- next slice

7. COMMIT
   Commit meaningful progress.

---

ANTI-BIAS RULE

For final validation:
- Use fresh QA analyst (only specs + current state)
- Use fresh QA engineer (Playwright MCP)
- Use fresh code reviewer

Fix all critical/high findings.
Do not rely on self-judgment.

---

QUALITY BAR
- Idiomatic Laravel
- Clear structure
- Validation + authorization
- Safe DB + transactions
- No dead code
- No duplicated logic
- No ignored failures
- Maintainable + testable

---

FAILURE HANDLING
If stuck:
- re-evaluate plan
- simplify slice
- try alternative approach
- document in specs/progress.md

If context grows:
- compress state into specs/progress.md
- continue from there

---

PRIORITY
1. Verified acceptance criteria
2. Functional correctness
3. Browser flows
4. Tests + checks
5. Code quality

---

Never stop until DONE WHEN is fully satisfied.
