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
| TASK-006 | Install dependencies and perform build/static checks; prepare safe local runtime | Operations guide; no DB until dedicated config | High | Planned | Pending | TASK-005 |
| TASK-007 | Settle first domain/access design details | CORE-001–007; parish cardinality, identity, scope | High | Ready | Pending owner review | TASK-003 |
| TASK-008 | Implement organization/internal-unit structure | HIER-001–004 | High | Planned | Pending | TASK-006, TASK-007 |
| TASK-009 | Implement scoped roles, safe grants and replace global bypass assumptions | CORE-003; permissions; BP-005–008 | High | Planned | Pending | TASK-008 |
| TASK-010 | Implement people/memberships/account links and lead approval | CORE-004–005, MEM-002/005/006 | High | Planned | Pending | TASK-009 |
| TASK-011 | Implement member profiles and assigned forms | CORE-006, MEM-001/003/004 | High | Planned | Pending | TASK-010 |
| TASK-012 | Implement departments and dated coordinator/member assignments | CORE-007, DEP-001–005 | High | Planned | Pending | TASK-009, TASK-010 |
| TASK-013 | Implement department activities and participation | CORE-008, DEP-006 | Medium | Planned | Pending | TASK-012 |
| TASK-014 | Implement reporting cycles, templates and local approval/release | CORE-009–010, REP-001–005/010 | High | Planned | Pending | TASK-012 |
| TASK-015 | Implement upper review, completeness and consistent totals | CORE-011, REP-006/007/011 | High | Planned | Pending | TASK-014 |
| TASK-016 | Implement shared information and publication audiences | CORE-012, SHARE-001/002 | Medium | Planned | Pending | TASK-009 |
| TASK-017 | Adapt media/audit/export/notifications to domain scope | CORE-013–014, GOV-001/002, REP-008/009 | High | Planned | Pending | TASK-009; integrate per feature |
| TASK-018 | Implement Amharic/English core journeys and agreed dates/printing | NFR-001–004 | High | Planned | Pending | Integrate throughout |
| TASK-019 | Select host and configure deployment/recovery | NFR-006, operations | High | Planned | Pending | Budget/provider decision |
| TASK-020 | Pilot manual acceptance, onboarding and support | Pilot guide, acceptance doc | High | Planned | Pending | Core MVP delivered |
| TASK-021 | Prepare implementer/reviewer workflow and report template | GEMINI.md, docs/13, report template | High | Done | N/A | TASK-003 |
| TASK-022 | Gemini read-only understanding report and reviewer assessment | Return report in chat; no implementation | High | Ready | Pending report/review | TASK-021 |

## How to continue

The next executable task is identified in PROJECT_STATUS, not inferred from row order. Expand a planned feature into a bounded implementation plan only when its blocking design decisions are settled. Update affected task, status checkpoint and manual handoff in the same session. Record defects under the owning task with reproduction steps and expected/actual behavior.
