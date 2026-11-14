# AI agent: start here

This file lets a new AI continue without the original conversation. Read it before editing or running setup commands.

**Current collaboration mode:** Gemini/Antigravity implements assigned tasks; the reviewing assistant reviews scope/code/reports and may make fixes. Implementation through TASK-029 and the TASK-030 bilingual review pack are prepared. Current next action is owner first-review/manual acceptance, not an automatic next implementation task. Read [GEMINI.md](GEMINI.md) and [workflow](docs/13-ai-collaboration-workflow.md). A current bounded brief takes precedence over the generic continuation prompt below.

## 1. Owner instructions that must survive every handoff

- **No automated unit, feature, integration, end-to-end, browser, snapshot, regression or coverage tests.** Do not install test tooling, run suites or introduce TDD/CI test tasks. The owner manually tests and reports issues. Builds and proportionate syntax/type checks are allowed.
- Work in the SundaySchool root. Preserve `RefernceBoilerPlate/` as an unchanged external reference. Never use its database, keys or Git remote for the product.
- Hierarchy is the heart of the app. Each school has its own management scope; higher offices have jurisdiction and functional responsibilities.
- Primary administrative actors are senior/department coordinators and local leads, not a default teacher role.
- Members can use their own profiles, assigned forms and information. A member record is not the same as a login account.
- The **Sunday School lead approves memberships/account links and outgoing reports**, under higher-office oversight. Do not ask again whether higher offices approve every member: they do not.
- Shared experience/resources are explicitly published; private records are not shared automatically.
- Amharic is the default interface, English secondary. Preserve Amharic/English/mixed data verbatim in PostgreSQL UTF-8; see [language/data policy](docs/requirements/language-and-data.md).
- Keep work bounded and documentation current to reduce repeated analysis and credit use. Do not infer unspecified academic ERP modules.

## 2. Read in this order

1. [AGENTS.md](AGENTS.md) — binding project instructions.
2. [Current checkpoint](docs/PROJECT_STATUS.md) — actual state, last actions, evidence and next task.
3. [Backlog](docs/08-backlog.md) — task statuses/dependencies; do not assume “planned” means implemented.
4. [Product overview](docs/00-product-overview.md) and [requirements](docs/02-requirements.md).
5. [Permissions](docs/03-roles-and-permissions.md), [data model](docs/04-data-model.md), [architecture](docs/05-architecture.md).
6. Read the specific requirement clarification and source files needed for the current task. The [feature register](docs/12-boilerplate-feature-register.md) describes inherited capabilities and unresolved gaps.

Do not reread all historical research for a small task unless it affects the decision. Current code is authoritative about implementation; owner-confirmed requirements are authoritative about intended behavior. Research suggestions and inherited code are not stakeholder approval.

## 3. Product and source identity

Working name: **Sunday School Unity**, slug `sunday-school-unity`, Composer name `sunday-school-unity/platform`. Institutional sponsorship and final official brand are unconfirmed.

Root foundation copied from a real fresh clone of `https://github.com/Semera24/laravel-production-boilerplate-v2.git`, commit `eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5`. The product has an independent Git repository on `main`, with `origin` set to `https://github.com/Semera24/sunday-school-unity.git`. The owner authorized initial commit/push. Inspect `git status`, `git log` and branch tracking for current publication state. Reference repository history is separate and not copied to product history.

Technical direction: Laravel 13, PHP 8.5, PostgreSQL, Inertia 2, Vue 3, TypeScript, Tailwind 4, Wayfinder. Exact lock/install/runtime state belongs in PROJECT_STATUS. Do not confuse a successful dependency resolution with installed packages or verified workflows.

## 4. Current hazards to carry forward

Inherited administration is global. School/diocese scoping and domain entities do not exist. User role assignment currently permits a path to Super Admin; API token login lacks the visible Fortify 2FA/verification flow; import commits lack their own atomic/failure wrapper; exports/summary/search need access and scale adaptations. These were inspected, not fixed. No real member data or public deployment until appropriate access work is done.

Generic roles and local demo personas are not agreed church roles. Do not merely rename them. Do not run seeding or migrations until a dedicated new database is confirmed. No automated tests should be restored from the source reference. Boost guideline generation is disabled and its automatic post-update hook removed to protect project instructions.

## 5. Continuation protocol

Before work, inspect `git status`, manifests and the current task's relevant files. Preserve user changes. Select one bounded deliverable from PROJECT_STATUS/backlog. Ask only for a missing decision that materially blocks that work; do not reopen settled local-approval questions.

Before ending a session:

1. Update backlog implementation status separately from owner manual acceptance.
2. Update PROJECT_STATUS with files changed, commands actually run/results, unresolved issues and exact next action.
3. Add decision changes to the decision register; mark assumptions Proposed or Working.
4. Supply concise manual steps/expected results for functional changes. Leave manual acceptance Pending until owner confirms.
5. Do not claim a feature works merely because code, a lock file, syntax or a build exists.

## 6. Copyable prompt for a new agent

> Continue Sunday School Unity from this folder. Read AI_START_HERE.md, AGENTS.md, docs/PROJECT_STATUS.md and docs/08-backlog.md first. Follow the recorded hierarchy and local-lead approval rules. No automated test suites; I manually test. Inspect the current code and continue the next bounded task from the checkpoint. Preserve RefernceBoilerPlate and my existing changes. Keep task/status documents updated and distinguish implemented work from manual acceptance. Do not install, migrate, deploy or seed blindly; check the checkpoint and active configuration first.
