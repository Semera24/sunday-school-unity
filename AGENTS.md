# Project instructions for coding agents

Start with [AI_START_HERE.md](AI_START_HERE.md), then [current checkpoint](docs/PROJECT_STATUS.md) and [backlog](docs/08-backlog.md). Update checkpoint and task status at the end of each working session so another agent can continue without chat history.

## Implementation and review collaboration

- Gemini/Antigravity is the intended implementer; the reviewing assistant reviews product alignment, source and delivery reports and may fix code when necessary. The owner performs manual acceptance.
- Current first assignment is read-only onboarding and a chat report. Do not implement TASK-006, install packages or modify files merely because a generic continuation instruction mentions it.
- Follow [GEMINI.md](GEMINI.md) and [collaboration workflow](docs/13-ai-collaboration-workflow.md). After onboarding is assessed, implement only the explicitly assigned task, report using the template, and stop for review before the next task.
- Avoid overlapping edits by different agents. Preserve unrelated changes and distinguish implementation, reviewer assessment and owner manual acceptance.

## Owner's testing policy — accepted 14 November 2026

- The project owner explicitly requires manual testing and will perform functional acceptance testing and report issues to the agents.
- Do not write, install, configure, maintain, or run automated unit, feature, integration, end-to-end, browser, snapshot, regression, or coverage test suites unless the owner later explicitly changes this policy.
- Do not use test-driven development or create automated testing tasks as part of plans, requirements, backlogs, or CI pipelines.
- Do not add testing packages or spend effort on generated starter-kit tests. Avoid enabling test tooling when a scaffold offers a choice.
- Provide short manual verification steps and expected results with each functional change. Record owner-reported issues and their resolution.
- Report implementation status separately from manual acceptance status. Do not claim the owner has verified a feature unless they have confirmed it.
- This policy concerns testing. Application validation, authorization, database constraints, transactions, error handling, and audit records remain part of implementation.
- Ordinary compilation/build and narrowly relevant syntax/type checks are not test suites; keep them proportionate and do not expand them into automated functional testing.

See [manual testing policy](docs/09-manual-testing-and-acceptance.md).

## Current project stage

- Research and domain clarification precede requirements, database schema, and application scaffolding.
- Research observations, proposed designs, accepted decisions, and open questions must be distinguishable.
- Published church responsibilities do not automatically imply software access permissions or approved application features.
- “School management” is the owner's analogy for registration, CRUD and administration. Primary operators are senior coordinators/appointed administrators, including department coordinators, rather than a teacher role. Each Sunday School manages its departments and members and submits reports to upper coordinating offices. Distinguish member records, department membership, appointments and login permissions. Do not assume conventional academic modules.
- Read [department coordination requirements](docs/requirements/department-coordination.md) for the latest owner clarification; workflow details and default department catalogue remain proposals.
- Hierarchy is central to the product. Member access includes own profiles, forms and information. Local schools have separate management workspaces and explicit common sharing spaces. Higher union/board/division responsibilities also require scopes; do not collapse them into one unrestricted admin. Local lead approval precedes upward report release. Read [hierarchy and member access](docs/requirements/hierarchy-member-access-sharing.md); technical and detailed workflow choices there remain proposals where marked.
- The Sunday School lead is the principal administrator within their school and approves memberships and account-to-member links. Higher offices provide general management/coordination/report oversight; do not infer unrestricted local-record editing from organizational rank. School onboarding and appointment of leads remain to be defined.
- Individual member registrations/approvals do not require higher-office countersignature. The local lead acts under higher-office oversight. Distinguish this everyday membership workflow from adding a school organization/initial lead account to the platform; no mandatory higher-office onboarding approval has been agreed.
- Working technical direction: Laravel 13, PHP 8.5, Inertia 2, Vue 3, TypeScript, and PostgreSQL. Root foundation is copied/rebranded and its dependency lock prepared; check PROJECT_STATUS for installation/runtime state. Hosting remains undecided. Working product name: Sunday School Unity.
- Preserve `RefernceBoilerPlate/` unchanged. Product repository and database are independent. Inherited global roles and access are not the final hierarchy-aware design. Do not restore upstream AGENTS or generated testing instructions over this file.
- Read [GSSU domain research](docs/research/2026-11-14-gssu-domain-research.md) before preparing requirements or the data model.
