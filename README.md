/goal Build the complete shop system from specs/* until all acceptance criteria are satisfied and verified.

You are operating in persistent goal mode. Continue working until the goal is achieved, or a real external blocker prevents progress.

---

GOAL
Deliver a fully working shop system based on specs/* with verified functionality.

---

CONTEXT
- All requirements are in specs/*
- Progress and decisions must be tracked in specs/progress.md
- System runs at http://shop.test/

---

CONSTRAINTS
- Follow existing architecture and conventions
- Implement in vertical slices (no isolated scaffolding)
- Do not skip verification steps
- Do not stop at partial implementations
- Do not ask for next steps while acceptance criteria remain unmet

---

DONE WHEN
- All specs/* requirements are implemented
- All acceptance criteria are satisfied
- Pest tests pass
- Playwright MCP verifies customer + admin flows
- No critical bugs remain after browser review
- specs/progress.md reflects full implementation and verification

---

PROCESS (MANDATORY LOOP)

0. PLANNING
- Read specs/*
- Create a phased execution plan in specs/progress.md
- Identify dependencies and risks
- Only proceed once plan is coherent

1. IMPLEMENT
- Build next vertical slice

2. VERIFY
- Run tests (Pest)
- Run browser flows (Playwright MCP)

3. EVALUATE
- Compare results against DONE WHEN criteria
- Identify gaps and failures

4. FIX
- Resolve issues before continuing

5. TRACK
- Update specs/progress.md (status, decisions, open gaps)

6. COMMIT
- Commit meaningful progress

7. REPEAT until DONE WHEN is satisfied

---

FAILURE HANDLING

If stuck or looping:
- Re-evaluate plan
- Simplify approach
- Try alternative implementation strategy

If context becomes too large:
- Compress state into specs/progress.md
- Continue from compressed state

---

AGENT STRATEGY

Use sub-agents where useful:
- backend
- frontend
- QA analyst
- QA engineer

Sub-agents may analyze and propose.
You own integration, correctness, and final quality.

---

PRIORITY ORDER

1. Passing verification (tests + browser)
2. Functional correctness
3. Completeness vs specs
4. Code quality

---

Never stop while DONE WHEN is not satisfied.
