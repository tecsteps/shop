# Mission

Your mission is to implement an entire shop system based on the specifications im specs/*. Do not stop until all phases are complete and verified. All requirements are perfectly implemented. All acceptance criteria are met, tested and verified.

# Team Instructions

- The teamlead decides what specialized teammates are spawned for the implementation of each phase of the project (e.g. backend-dev, frontend-dev, schema-dev, etc.). All developer teammates  are performing TDD with Pest (not for pure UI work). For each phase of the project a new team must be spawned to keep their context fresh.
- There must be dedicated code review teammate which ensures the code follows clean code, SOLID and Laravel best practices (check with Laravel Boost). The code review teamm mate can also check for syntax errors using the PHP LSP, to ensure the code is perfect. The code review teammate must be replaced per phase to keep the context fresh.
- There must be dedicated QA Analyst that verifies functionality (non-scripted) using Playwright and Chrome. If bugs appear, then other teammates must fix them, so the QA Analyst can verify the fixes. The QA Analyst teammate must be replaced per phase to keep the context fresh. The QA analyst must also ensure that al links are working (it's not enough to check the routes).

All teammates must make use of the available tools: Laravel Boost and PHP LSP.

There must be a dedicated controller teammate that stays for the whole project. This teammate just controls the results of the other agents and guarantees that the entire scope is developed, tested and verified. This teammate is super critical and has a veto right to force rechecks. The teammate must consult this teammate after each phase. This teammate must also approve the projectplan. This teammate must also confirm that the entire E2E test actually happend and there are not open bugs or gaps (like skipped test cases). Only when all 143 test cases were verified non-scripted the project can be signed-of.

# Final E2E QA

When all phases are developed, the teamlead spawns a dedicated fresh UAT teammate to make a final verification using Playwright/Chrome based on specs/08-PLAYWRIGHT-E2E-PLAN.md. All 143 test cases have to be verified manually. They must be correct and complete. All verification checks must be tracked in specs/final-e2e-qa.md It's not allowed to skip any test cases or accept any bug or gap.

If bugs or gaps are detected, other teammates fix them. The controller must confirm everything is working and the final E2E test was performed and all test cases were successfully verified.

# Team Lead

Continuously keep track of the progress in specs/progress.md Commit your progress after every relevant iteration with a meaningful message. Make sure there is a final commit.

Important: Keep the team lead focussed on management and supervision. All tasks must be delegated to specialized teammates. The teamlead must not do any coding, reviews, verification, research on its own.

Phases are developed one after the other; no parallel development of any phase allowed!

Before starting, read about team mode here: https://code.claude.com/docs/en/agent-teams
You must use team-mode; not sub-agents.
