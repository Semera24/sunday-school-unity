# AI implementation and review workflow

Status: owner-directed collaboration model; established 14 November 2026.

## Responsibilities

| Participant | Responsibility |
|---|---|
| Owner | Product decisions, task authorization, manual functional testing and acceptance |
| Gemini / Antigravity | Read-only onboarding, then bounded implementation tasks and evidence-backed delivery reports |
| Reviewing assistant | Product/scope review, source/diff inspection, quality assessment and next-task preparation; may fix code when necessary |

The reviewer does not replace the institutional sponsor, owner or manual acceptance. No automated test suites. Product validity, build results, reviewer acceptance and owner manual acceptance are separate statuses.

## Stage 1: read-only understanding report

The implementer reads GEMINI.md, AI_START_HERE, AGENTS, checkpoint/backlog, product overview, requirements, permissions, conceptual data model, architecture, detailed hierarchy/department requirements and the relevant feature-register findings. Inspect representative actual code for identity, role assignment, API authentication, query scope, imports and reporting-related exports. Read research only where it resolves a domain question.

Read-only means no file writes, report files, installs, application execution, package scripts, migrations, seeding, database actions or Git mutations. Commands such as `git status`, listing files and reading source are appropriate. Do not read secrets. Do not run broad verification routines during onboarding.

Return the following report in chat:

1. **Product understanding:** purpose, primary users, hierarchy, local authority and sharing boundaries, in your own words.
2. **Settled rules:** manual-only testing; local lead membership/account-link approval; lead-approved upward reporting; member self-service; scoped higher-office/division roles; distinction between member and account.
3. **Actual repository state:** root/reference separation; manifest and locked versions; presence/absence of installed dependencies, configuration and domain models. Distinguish inspected facts from unverified checkpoint claims.
4. **Reusable foundation:** identify concrete modules/source paths, noting partial features such as imports and localization.
5. **Material risks:** confirm/refute inherited access, role-grant, API-login, import and export findings using source paths and line numbers. This is not a demand to fix them now.
6. **Requirements gaps:** only unresolved decisions that would affect the first implementation work. Do not reopen confirmed local approval rules.
7. **First bounded task proposal:** objective, exact files/areas, permitted commands, expected outputs, scope exclusions and manual handoff. TASK-006 is the likely next setup task, not authority to execute it.
8. **No-change statement:** what was read, commands used, and confirmation of no modifications/install/database actions. Disclose any accidental change rather than hiding it.

Keep the report focused; aim for roughly 700–1,000 words. Do not copy documents verbatim or claim the app works from static inspection.

## Stage 2: reviewer assessment

The owner relays the report to the reviewing assistant. There is no configured live agent-to-agent channel. The reviewer checks its claims against repository evidence and records one outcome: understanding accepted, corrections requested, or owner decision needed. Save the reviewed report/outcome under `docs/reports/` when actually received; do not create fictitious completed reports.

Initial implementation begins only after the report is assessed and the owner passes an explicit task brief to the implementer. Ordinary read-only review is already authorized by this workflow.

## Stage 3: one coding task at a time

Each brief must state task ID, goal, linked requirements, exact deliverable, allowed source areas/commands, dependencies, known open decisions, acceptance criteria and stopping point. Mark unspecified behavior Proposed rather than deciding silently. Infrastructure tasks must spell out whether installs, environment setup or database changes are included.

The implementer inspects current changes before editing and preserves unrelated work. Record a baseline commit if one exists; otherwise disclose that the repository has no commit and identify files touched explicitly. Do not create commits or run destructive commands merely to manufacture a baseline.

At completion, provide a report from the template, update the backlog/checkpoint within the authorized task, and stop. No automatic progression to the next row. A task blocked on a product decision should report the exact question and work completed without treating elapsed time as approval.

## Stage 4: review and correction

The reviewer reads actual changed source and surrounding flows; a report alone is not proof. Review scope, hierarchy/authorization, relational consistency, workflow state, maintainability and permitted check evidence. Perform proportionate static/build checks if useful and authorized; never introduce automated testing.

Review result: **Accepted for owner manual check**, **Changes requested**, or **Owner decision needed**. Cite actionable findings with paths/lines and expected behavior. Accepting code does not mark manual acceptance Passed.

Prefer one focused correction brief. If the reviewing assistant fixes code directly, stop overlapping implementer edits first, inspect current files, make the bounded fix and publish its own report/checkpoint update. The next implementer must reread those changes before continuing. Do not let two agents edit the same workspace concurrently.

## Stage 5: manual acceptance and continuation

The owner performs the provided steps. Record Pending/Passed/Issue found separately from code/review status. Attach owner-reported issues to the task and deliver corrections as bounded work. Once the current report/review is assessed, issue the next brief. Avoid repeated full-project reviews between small tasks.

## Evidence discipline

- Include commands actually run, exit status/result and what they do not establish.
- “Dependencies resolved,” “installed,” “build passed,” “reviewed” and “manually accepted” are different facts.
- Do not put keys, passwords, tokens, database dumps or real member information in reports.
- Never edit approved requirements to make an implementation mismatch disappear.
- Do not rewrite historical audit evidence; append current corrections/checkpoints.

## Current handoff update — 14 November 2026

The initial onboarding report has been received and [reviewed](reports/2026-11-14-gemini-onboarding-review.md). Its suggested setup-only next task is superseded by the owner’s hierarchy-first priority. Follow [TASK-024](tasks/TASK-024-hierarchy-access-demo.md) and current GEMINI/PROJECT_STATUS instructions. Return the bounded design/setup breakdown first; do not repeat onboarding or begin the whole backlog.
