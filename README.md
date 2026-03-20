# Mission

Implement a complete shop system based on the specifications in `specs/*`. Do not stop until all phases are complete, all requirements are implemented, all acceptance criteria are met, and all 143 test cases pass verification.

Before starting, read the team mode documentation: https://code.claude.com/docs/en/agent-teams  
You must use team mode (not sub-agents).

---

# Phases & Sequencing

- Phases are defined in `specs/*`. Read all spec files before creating the project plan.
- Phases are developed strictly sequentially. No parallel development.
- Only one phase is active at a time.
- For each phase, spawn a fresh team (details below). Do not reuse teammates across phases.
- After all phases: run Final E2E QA, then Adversarial QA, then fix any issues, then re-run E2E QA until clean.

---

# Team Composition

For each phase, the team lead spawns the following roles. All teammates except the Controller are replaced per phase to keep context fresh.

| Role | Scope | Persists across phases? |
|---|---|---|
| **Team Lead** | Management, supervision, delegation. Does no coding, reviewing, or verification. | Yes |
| **Controller** | Artifact auditor with veto power. Does not code, run tools, or use Playwright. Works exclusively from markdown artifacts and team lead reports. Enforces rigor by catching incomplete, ambiguous, or sloppy reporting. | Yes |
| **BDD Expert** | Writes Gherkin specs from the phase requirements. | No (fresh per phase) |
| **Gherkin Reviewer** | Confirms Gherkin specs are 100% complete vs. the phase spec, and consistent with preceding/succeeding phases. | No (fresh per phase) |
| **Developers** (1+, specialized as needed: backend, frontend, schema, etc.) | Implementation using TDD with Pest. Follow the prepared Gherkin specs. No Pest tests required for pure UI work. | No (fresh per phase) |
| **Code Reviewer** | Reviews code for clean code, SOLID, Laravel best practices. Runs static analysis (phpmetrics or similar) for duplication, vulnerabilities, complexity. Produces a quality report as markdown. | No (fresh per phase) |
| **QA Analyst** | Verifies functionality non-scripted using Playwright and Chrome. Tests all routes AND rendered pages (not just route existence). Tracks all checks in a markdown file. | No (fresh per phase) |

### Available tooling

All teammates must use the available MCP servers:
- **Laravel Boost** (MCP server, already configured)
- **PHP LSP** (MCP server, already configured)

The Code Reviewer additionally uses phpmetrics or equivalent for static analysis.

---

# Artifact Requirements

Every teammate must document their work in markdown files. These artifacts are the Controller's sole input. If it's not written down, it didn't happen.

### General rules for all artifacts
- Every artifact must include: **what** was done, **how** it was done, **why** decisions were made, and an **honest self-assessment** of the work (including uncertainties, risks, or areas of concern).
- No artifact may contain only checklists or status tables. There must be prose that explains the reasoning.
- Ambiguous statuses (UNKNOWN, SKIPPED, PARTIAL, N/A, blank) are not permitted. Every item is PASS or FAIL. If something could not be verified, that is a FAIL with an explanation.

### Per-role artifact specifications

**BDD Expert** writes `work/phase-{N}/gherkin-specs.md`:
- List of every acceptance criterion from the phase spec, each mapped to one or more Gherkin scenarios.
- Traceability table: spec requirement ID/description -> Gherkin scenario name.
- Self-assessment: Are there any requirements that were ambiguous or hard to translate? How were they interpreted and why?

**Gherkin Reviewer** writes `work/phase-{N}/gherkin-review.md`:
- Confirmation that every spec requirement has a corresponding Gherkin scenario (with the count).
- Check for consistency with preceding and succeeding phases.
- List of any concerns, ambiguities, or gaps found (even if resolved).
- Self-assessment: Confidence level in completeness. What could be missing? Were any edge cases intentionally excluded and why?

**Developers** write `work/phase-{N}/dev-report.md`:
- What was implemented and how (architecture decisions, patterns used).
- List of Pest test cases created, mapped to Gherkin scenarios.
- Any deviations from the Gherkin specs and why.
- Known limitations or technical debt introduced.
- Self-assessment: What are the weakest parts of this implementation? What would break first under load or edge cases?

**Code Reviewer** writes `work/phase-{N}/code-review.md`:
- Quality metrics defined upfront (what was measured and what thresholds were set).
- Item-level checklist with PASS/FAIL per item.
- Static analysis results (phpmetrics or equivalent) with key numbers.
- For any finding that was fixed: what the issue was, how it was fixed, and confirmation it was re-checked.
- Self-assessment: Overall code quality rating with justification. What are the remaining risks? Is there anything that passed the checklist but still feels fragile?

**QA Analyst** writes `work/phase-{N}/qa-report.md`:
- Every Gherkin scenario mapped to a verification entry.
- For each entry: what was tested, how it was tested (specific Playwright actions), what the expected result was, what the actual result was, and PASS/FAIL.
- For any FAIL that was fixed and re-tested: the original failure, what was fixed, and the re-test result.
- **Asset verification section**: Every page visited must be checked for broken images, missing assets, and broken links. Document each page URL, the number of images/assets found, how many loaded successfully, and PASS/FAIL. If a product image is referenced but doesn't render, that is a FAIL.
- **URL verification section**: Every internal link and navigation element across all pages must be clicked and verified. Document each URL, expected destination, actual result (page loaded / 404 / error / redirect), and PASS/FAIL.
- Regression check: confirmation that previous phase functionality still works.
- Self-assessment: Are there areas that felt undertested? Anything that passed but seemed brittle? Any concerns about behavior that technically meets the spec but feels wrong?

**UAT Analyst** writes `work/final-e2e-qa.md`:
- All 143 test cases with the same detail level as QA Analyst entries above (what, how, expected, actual, PASS/FAIL).
- Self-assessment section at the end.

**Adversarial QA Analyst** writes `work/adversarial-qa.md`:
- Edge cases tested, organized by the baseline test case they extend.
- For each: what was tried, what the expected behavior was, what actually happened, and PASS/FAIL.
- Log file inspection results (any exceptions or errors found).
- Self-assessment: How hard did you try to break it? What attack vectors were explored? What remains untested?

---

# Per-Phase Workflow

Every phase follows this exact sequence:

### 1. BDD Specification
- The **BDD Expert** rewrites the phase requirements into Gherkin specs.
- The **Gherkin Reviewer** confirms the specs are complete, correct, and consistent with adjacent phases.
- The **Controller** approves the Gherkin specs before any coding begins.

### 2. Implementation (TDD)
- The **Team Lead** decides which specialized developer teammates to spawn.
- Developers implement using TDD with Pest, driven by the approved Gherkin specs.
- Pure UI work is exempt from Pest tests but must still follow the specs.

### 3. Code Review
- The **Code Reviewer** reviews all code from the phase.
- Must create a checklist, define quality metrics, and produce results as a markdown report.
- Uses Laravel Boost, PHP LSP, and phpmetrics (or equivalent).
- Code must follow clean code, SOLID, and Laravel best practices.
- No code duplication, no vulnerabilities, no syntax errors.

### 4. QA Verification
- The **QA Analyst** verifies all functionality non-scripted using Playwright and Chrome.
- All links, routes, and rendered pages must work. Route existence alone is insufficient.
- All checks are tracked in a phase-specific markdown file.
- Every check must PASS. No gaps, no compromises, no skipped cases.
- Every page must be visually checked, to ensure there are no UI issues.
- If bugs are found: developers fix them, then the QA Analyst re-verifies.

### 5. Controller Sign-Off

The **Controller** does not look at code, does not run Playwright, and does not use any tooling directly. The Controller works exclusively from the markdown artifacts produced by other teammates and from statements by the Team Lead. The Controller's job is to catch laziness, sloppiness, or incompleteness in other agents' work by auditing the quality and honesty of their documentation.

The Controller reviews the following artifacts and applies these acceptance criteria. Every criterion must be MET. If any criterion is NOT MET, the Controller issues a veto and the relevant step is re-executed.

**A. Gherkin Specs** (`work/phase-{N}/gherkin-specs.md` and `work/phase-{N}/gherkin-review.md`)
- [ ] Both files exist.
- [ ] The traceability table maps every spec requirement to a Gherkin scenario. None are missing.
- [ ] The Gherkin Reviewer's report provides a written confirmation with the exact count of requirements vs. scenarios.
- [ ] Both artifacts contain self-assessments that are substantive (not just "everything looks good"). If the self-assessment raises concerns, those concerns must be addressed or acknowledged.

**B. Dev Report** (`work/phase-{N}/dev-report.md`)
- [ ] The report exists and explains what was built and how.
- [ ] Pest test cases are listed and mapped to Gherkin scenarios.
- [ ] Any deviations from spec are documented with reasoning.
- [ ] The self-assessment section exists and is honest (identifies weaknesses, not just confirms success).

**C. Code Review Report** (`work/phase-{N}/code-review.md`)
- [ ] The report exists with quality metrics, thresholds, and item-level PASS/FAIL.
- [ ] Every item is PASS. No UNKNOWN, SKIPPED, PARTIAL, or blank.
- [ ] Static analysis results are included with actual numbers (not just "passed").
- [ ] The self-assessment provides an overall quality rating with justification and identifies remaining risks.
- [ ] If any finding was initially FAIL: the fix and re-check are documented.

**D. QA Report** (`work/phase-{N}/qa-report.md`)
- [ ] The report exists with an entry for every Gherkin scenario.
- [ ] Every entry describes what was tested, how (Playwright actions), expected vs. actual result, and PASS/FAIL.
- [ ] Every entry is PASS. No UNKNOWN, SKIPPED, PARTIAL, N/A, or blank.
- [ ] If any entry was initially FAIL: the failure, fix, and re-test are all documented.
- [ ] The report contains an **asset verification section** listing every page, the number of images/assets found, how many loaded, and PASS/FAIL per page. No missing or broken images allowed.
- [ ] The report contains a **URL verification section** listing every internal link and navigation element, the expected destination, the actual result, and PASS/FAIL. No 404s, broken links, or dead-end navigation allowed.
- [ ] Regression check for previous phases is documented.
- [ ] The self-assessment is substantive and flags any concerns, even if everything passed.

**E. Completeness & Consistency**
- [ ] The number of Gherkin scenarios matches or exceeds the number of spec requirements.
- [ ] The number of QA entries matches or exceeds the number of Gherkin scenarios.
- [ ] The number of Pest tests matches or exceeds the number of Gherkin scenarios (excluding pure UI).
- [ ] No requirement is unaccounted for across the chain: spec -> Gherkin -> dev -> code review -> QA.
- [ ] Self-assessments across all artifacts are consistent (no contradictions, e.g. dev says "this is fragile" but QA says "no concerns").

**F. Artifact Quality**
- [ ] No artifact is a bare checklist without explanatory prose.
- [ ] Every artifact contains a self-assessment section with honest evaluation.
- [ ] If any self-assessment raises a risk or concern, it has been either resolved (with evidence) or explicitly accepted with justification by the Team Lead.

If all criteria are MET, the Controller produces `work/signoff-phase-{N}.md` containing the filled checklist above with status per item, plus a brief narrative assessment of the phase. Development of the next phase may only begin after this sign-off.

### 6. Progress Tracking
- The **Team Lead** updates `work/progress.md` and makes a git commit with a meaningful message after each phase.

---

# Final E2E QA

After all phases are complete:

1. The Team Lead spawns a **fresh UAT Analyst** (not reused from any phase).
2. The UAT Analyst executes all 143 test cases from `specs/08-PLAYWRIGHT-E2E-PLAN.md` using Playwright and Chrome.
3. Verification is non-scripted (agent-driven, not pre-written test scripts).
4. Every test case must be executed. No skipping.
5. All results are tracked in `work/final-e2e-qa.md` with pass/fail per test case.
6. Every page must be screenshotted, visually checked and confirmed in `work/final-e2e-qa.md`  
7. If any test case fails: developers fix the issue, then the UAT Analyst re-verifies.
8The **Controller** reviews `work/final-e2e-qa.md` and applies these acceptance criteria:
    - [ ] The file contains exactly 143 test case entries (matching `specs/08-PLAYWRIGHT-E2E-PLAN.md`).
    - [ ] Every entry describes what was tested, how, expected vs. actual, and has an explicit PASS status.
    - [ ] No FAIL, UNKNOWN, SKIPPED, PARTIAL, N/A, or blank entries.
    - [ ] If any test was initially FAIL: the failure, fix, and re-test with PASS are all documented.
    - [ ] No test case IDs from the E2E plan are missing.
    - [ ] The file contains a self-assessment section at the end.
    - [ ] Cross-reference: 143 unique IDs, 143 PASS results.
    - [ ] Every page is visually checked and confirmed

---

# Adversarial QA

After the Final E2E QA passes:

1. The Team Lead spawns one **Adversarial QA Analyst**.
2. This analyst takes the existing 143 test cases as a baseline and tests edge cases around them.
3. The goal is to break the system: malformed input, broken URLs, missing routes, unexpected navigation, boundary conditions.
4. The analyst also inspects log files for unhandled exceptions or errors.
5. All findings are tracked in `work/adversarial-qa.md`.
6. If issues are found: developers fix them, then a fresh Final E2E QA round is run (all 143 test cases again).
7. This cycle repeats until the system is clean.

---

# Final Sign-Off

The **Controller** produces `work/final-signoff.md` only after verifying every item below. Each item must reference the specific artifact file that proves it.

- [ ] All phase sign-off files exist (`work/signoff-phase-{N}.md` for every phase) and every checklist item within them is MET.
- [ ] All per-phase artifacts exist in `work/phase-{N}/` (gherkin-specs, gherkin-review, dev-report, code-review, qa-report) for every phase.
- [ ] `work/final-e2e-qa.md` exists, contains 143 test cases all with PASS status, and includes a self-assessment.
- [ ] `work/adversarial-qa.md` exists, all findings are resolved (no open issues), and includes a self-assessment.
- [ ] If a fix cycle occurred after adversarial QA: a subsequent E2E QA round was executed and all 143 test cases passed again.
- [ ] `work/progress.md` is up to date and reflects all phases as complete.
- [ ] No markdown artifact anywhere in the project contains UNKNOWN, SKIPPED, PARTIAL, N/A, or blank statuses.
- [ ] Every artifact contains a substantive self-assessment (not just "all good").
- [ ] No self-assessment raises an unresolved concern.

Only after this sign-off is the project complete.

---

# Team Lead Responsibilities

- Read all specs before creating the project plan. Have the Controller approve the plan.
- Keep `work/progress.md` current. Git commit with a meaningful message after every relevant iteration. Ensure a final commit at the end.
- Spawn and manage teammates per the rules above. Stay focused on management and supervision.
- **Do not** code, review, verify, or research directly. Delegate everything.
- Consult the Controller after every phase and before closing the project.
