# Mission

Your mission is to implement an entire shop system based on the specifications im specs/*. You must do in one go without stopping. The result is a perfect shop system. All requirements are perfectly implemented. All acceptance criterias are met, tested and confirmed by you.


# Step 1 - Preparation

Have a teammate read all the specifications and create a full specs/project-plan.md with all required tasks as checklist and dependencies. Each task has a number as unique reference. Make sure the roadmap includes:
(a) Technical specification for all phases of the specs
(b) Development tasks (several per phase to prepare an optimal build process)
(c) Code reviews (per phase)
(d) Automated tests with Pest (per phase)
(e) Manual verification of all features using Playwright & Chrome (non-scripted) (per phase)

# Step 2 - Implementation (per phase)
The teamlead decides what specialized teammates are spawned for the implementation of each phase of the project.
There must be dedicated code review teammate whic ensures the code follows clean code, SOLID and Laravel best practices.
There must be dedicated QA Engineer, that implements the Pest tests.
There must be dedicated QA Analyst that writes a full specs/testplan-{phase}.md for the current phase and then verifies functionality using Playwright and Chrome. The results of the regression test are tracked in the testplan. If bugs appear, the other teammates must fix them, so the QA Analyst can verify the fixes.

You must use team mode! You must test everything via Pest (unit, and functional tests).

# Step 3

When all phases are developed, a teammate makes a final verification using Playwright/Chrome based on the testplans of all phases. If bugs or gaps are detected, other teammates fix them.

# Team Lead

Continuously keep track of the progress in specs/progress.md Commit your progress after every relevant iteration with a meaningful message.

Important: Keep the team lead focussed on management and supervision. All tasks must be delegated to specialized teammates. The teamlead must not do any coding, reviews, verification, research on its own.  

Before starting, read about team mode here: https://code.claude.com/docs/en/agent-teams
You must use team-mode; not sub-agents.
