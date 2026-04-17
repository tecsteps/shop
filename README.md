Your mission is to implement an entire shop system based on the specifications im specs/*. You must do in one go without stopping. You must use team mode! You must test everything via Pest (unit, and functional tests). You must also additional simulate user behaviour using the Playwright MPC and confirm that all acceptance criterias are met. If you find bugs, you must fix them. The result is a perfect shop system. All requirements are perfectly implemented. All acceptance criterias are met, tested and confirmed by you.

Continuously keep track of the progress in specs/progress.md Commit your progress after every relevant iteration with a meaningful message.

When implementation is fully done, then make a full review meeting and showcase all features (customer- and admin-side) to me. In case bugs appear, you must fix them all and restart the review meeting.

You are writing production PHP (Laravel) code.

STRICT CONSTRAINTS:

1. PHPStan Compliance
- Must pass PHPStan at max level.
- No mixed.
- Explicit return types everywhere.
- Fully typed properties.
- No dynamic properties.
- No suppressed errors.
- No relying on docblocks to hide real type problems.

2. Deptrac Compliance
- Respect architectural layers.
- No cross-layer violations.
- No circular dependencies.
- If a dependency is required, introduce an interface in the correct layer.
- Do not modify architecture unless explicitly instructed.

3. Pest Testing (Mandatory)
- Every feature must include automated tests.
- Include both unit and integration tests when appropriate.
- Cover success path and failure paths.
- Cover edge cases.
- Tests must be deterministic.
- Tests must validate behavior, not implementation details.

4. QA Self-Verification (Mandatory)
   Before finalizing:
- List each acceptance criterion.
- Explicitly confirm how it is implemented.
- Explicitly confirm which test covers it.
- Validate edge cases.
- Validate negative paths.
- Ensure no undefined behavior exists.

5. Fresh Agent Review (Mandatory)

After implementation:

Step 1: A NEW agent instance must review the code.
The reviewer must:
- Ignore prior reasoning.
- Re-evaluate architecture.
- Re-evaluate PHPStan compliance.
- Re-evaluate Deptrac boundaries.
- Re-evaluate test coverage.
- Act as a strict senior reviewer.

Step 2: The reviewer must:
- Identify weaknesses.
- Identify overengineering.
- Identify missing edge cases.
- Identify architectural drift.
- Suggest concrete improvements.

Step 3:
If issues are found:
- Fix them.
- Run review again with another fresh agent.
- Repeat until no critical issues remain.

No feature is complete without independent review.
