# Backlog and progress tracker

Updated: 14 November 2026. This is the single task list. Implementation and manual acceptance are separate.

Statuses: Planned, Ready, In progress, In review, Done, Blocked. Manual acceptance: Pending, Passed, Issue found, N/A (document/configuration task). No automated testing tasks.

| ID | Deliverable | Requirements / evidence | Priority | Implementation | Manual acceptance | Depends on |
|---|---|---|---|---|---|---|
| TASK-001 | Research GSSU organization and department domain | Research and clarification docs | High | Done | N/A | — |
| TASK-002 | Inspect/register boilerplate features and risks | Feature register | High | Done | N/A | — |
| TASK-003 | Create core documentation and AI continuation guide | README, AI_START_HERE, docs | High | Done | Pending owner review | TASK-001 |
| TASK-004 | Clone/rebrand root foundation and exclude test tooling | Source provenance; root manifests/files | High | Done | N/A | TASK-002 |
| TASK-005 | Resolve Laravel 13 dependencies without installing | Laravel 13.42.0 lock; Composer validation passed; see PROJECT_STATUS | High | Done | N/A | TASK-004 |
| TASK-006 | Install dependencies and perform build/static checks; prepare safe local runtime | Delivered within TASK-024 (`composer install`, `npm ci`, `vue-tsc`, `vite build`, `pint`) | High | In review | Pending | TASK-005 |
| TASK-007 | Settle first domain/access design details | CORE-001–007; demo subset delivered in TASK-024; production governance open | High | In review (demo subset) | Pending owner review | TASK-003 |
| TASK-008 | Implement organization/internal-unit structure | HIER-001–004; demo subset delivered in TASK-024 | High | In review (demo subset) | Pending | TASK-006, TASK-007 |
| TASK-009 | Implement scoped roles, safe grants and replace global bypass assumptions | CORE-003; permissions; BP-005–008; demo subset delivered in TASK-024 | High | In review (demo subset) | Pending | TASK-008 |
| TASK-010 | Implement people/memberships/account links and lead approval | CORE-004–005, MEM-002/005/006; local registration, invitations, link review & lifecycle delivered in TASK-028/029 | High | In review (local subset in TASK-028/029) | Pending | TASK-009 |
| TASK-011 | Implement member profiles and assigned forms | CORE-006, MEM-001/003/004; member self-service profile & status history delivered in TASK-028/029; assigned forms remain Planned | High | In review (profile subset in TASK-028/029) | Pending | TASK-010 |
| TASK-012 | Implement departments and dated coordinator/member assignments | CORE-007, DEP-001–005; local departments, coordinator appointments & dated member participation history delivered in TASK-024/028/029; cross-school transfers remain Planned | High | In review (local subset in TASK-028/029) | Pending | TASK-009, TASK-010 |
| TASK-013 | Implement department activities and participation | CORE-008, DEP-006 | Medium | Planned | Pending | TASK-012 |
| TASK-014 | Implement reporting cycles, templates and local approval/release | CORE-009–010, REP-001–005/010 | High | Planned | Pending | TASK-012 |
| TASK-015 | Implement upper review, completeness and consistent totals | CORE-011, REP-006/007/011 | High | Planned | Pending | TASK-014 |
| TASK-016 | Implement shared information and publication audiences | CORE-012, SHARE-001/002 | Medium | Planned | Pending | TASK-009 |
| TASK-017 | Adapt media/audit/export/notifications to domain scope | CORE-013–014, GOV-001/002, REP-008/009 | High | Planned | Pending | TASK-009; integrate per feature |
| TASK-018 | Implement Amharic/English core journeys and agreed dates/printing | NFR-001–004 | High | Planned | Pending | Integrate throughout |
| TASK-019 | Select host and configure deployment/recovery | NFR-006, operations | High | Planned | Pending | Budget/provider decision |
| TASK-020 | Pilot manual acceptance, onboarding and support | Pilot guide, acceptance doc | High | Planned | Pending | Core MVP delivered |
| TASK-021 | Prepare implementer/reviewer workflow and report template | GEMINI.md, docs/13, report template | High | Done | N/A | TASK-003 |
| TASK-022 | Gemini read-only understanding report and reviewer assessment | docs/reports/2026-11-14-gemini-onboarding-review.md | High | Done | N/A | TASK-021 |
| TASK-023 | Amharic-first defaults, UTF-8 configuration and core entry translation | Language/data policy; partial TASK-018 foundation | High | In review | Pending | TASK-005 |
| TASK-024 | First hierarchy/access demonstration with fictional accounts (extended with Union Super Administrator comparison) | docs/tasks/TASK-024-hierarchy-access-demo.md; deliver TASK-006/007/008/009 slices | Highest | In review | Pending | TASK-022, TASK-023 |
| TASK-025 | Configure local environment and dedicated PostgreSQL connection | PROJECT_STATUS local connection checkpoint; PHP PDO connectivity confirmed | High | Done | Passed — owner connected in TablePlus; password parity not verified | TASK-005 |
| TASK-026 | Audit duplication/unnecessary files before bounded cleanup | docs/reports/2026-11-14-cleanup-audit-review.md; cleanup deferred | Medium | Done — audit reviewed | N/A | TASK-022 |
| TASK-027 | Autonomous hierarchy review corrections, milestone localization and final handoff | docs/tasks/TASK-027-overnight-hierarchy-completion.md; docs/reports/2026-11-14-overnight-hierarchy-completion.md | Highest | In review | Pending | TASK-024 review findings |
| TASK-028 | Autonomous local member registration, invitations/account links, lifecycle and department participation | docs/tasks/TASK-028-membership-management-batch.md; docs/reports/2026-11-14-membership-management-batch.md; TASK-010–012 subset | Highest | In review | Pending | TASK-027 source prerequisites; manual acceptance still pending |
| TASK-029 | Membership completion pass: invitation/link consistency, participation constraints/history and post-commit mail | docs/tasks/TASK-029-membership-completion-pass.md; docs/reports/2026-11-14-membership-completion-pass.md | Highest | In review | Pending | TASK-028 report |
| TASK-030 | First-review readiness summary, bilingual PPT/PDF manual and authorized Git publication | docs/14-first-review-pack.md; output/presentation; output/pdf | High | Done — materials prepared; inspect live Git for publication result | N/A for documents; application acceptance unchanged | TASK-029 report |

## How to continue

The next executable task is identified in PROJECT_STATUS, not inferred from row order. Expand a planned feature into a bounded implementation plan only when its blocking design decisions are settled. Update affected task, status checkpoint and manual handoff in the same session. Record defects under the owning task with reproduction steps and expected/actual behavior.
