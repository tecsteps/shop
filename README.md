# Mission

Your mission is to implement an entire shop system based on the specifications im specs/*. Do not stop until all phases are complete and verified. All requirements are perfectly implemented. All acceptance criteria are met, tested and verified.

# Team Instructions

For each phase of the project a new team must be spawned!
- The teamlead decides what specialized teammates are spawned for the implementation of each phase of the project. The developer teammates  are performing TDD with Pest (not for pure UI work).
- There must be dedicated code review teammate which ensures the code follows clean code, SOLID and Laravel best practices (check with Laravel Boost). The code review teamm mate can also check for syntax errors using the PHP LSP, to ensure the code is perfect.
- There must be dedicated QA Analyst that verifies functionality (non-scripted) using Playwright and Chrome. If bugs appear, then other teammates must fix them, so the QA Analyst can verify the fixes.

All teammates must make use of the available tools: Laravel Boost and PHP LSP.

There must be a dedicated controller teammate that stays for the whole project. This teammate just controls the results of the other agents and guarantees that the entire scope is developed, tested and verified. This teammate is super critical and has a veto right to force rechecks. The teammate must consult this teammate after each phase. This teammate must also approve the projectplan. This teammate must also confirm all testplans for manual testing.

# Final E2E QA

When all phases are developed, the teamlead spawns a dedicated fresh teammate to make a final verification using Playwright/Chrome based on specs/08-PLAYWRIGHT-E2E-PLAN.md. All test cases have to be verified. They must be correct and complete. All verification checks must be tracked in specs/final-e2e-qa.md

If bugs or gaps are detected, other teammates fix them. The controller must confirm everything is working.

# Team Lead

Continuously keep track of the progress in specs/progress.md Commit your progress after every relevant iteration with a meaningful message. Make sure there is a final commit.

Important: Keep the team lead focussed on management and supervision. All tasks must be delegated to specialized teammates. The teamlead must not do any coding, reviews, verification, research on its own.

Before starting, read about team mode here: https://code.claude.com/docs/en/agent-teams
You must use team-mode; not sub-agents.
