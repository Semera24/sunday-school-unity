# Sunday School Unity

Working product name for a hierarchical Ethiopian Orthodox Sunday School membership, departmental coordination and reporting platform. This is an independently prepared project; official sponsorship and naming are not established.

**New AI agent: read [AI_START_HERE.md](AI_START_HERE.md) first, then [AGENTS.md](AGENTS.md).**

Current stage: first-review candidate for hierarchy, scoped access and local membership management through TASK-029. Owner manual acceptance is pending; the broader proposed MVP is not complete. See [project status](docs/PROJECT_STATUS.md) for the actual dependency and runtime state.

## Documentation

| Document | Purpose |
|---|---|
| [Product overview](docs/00-product-overview.md) | Presentation reference: what it is, who it serves, value and MVP |
| [Product brief](docs/01-product-brief.md) | Goals, scope, constraints and ownership questions |
| [Requirements](docs/02-requirements.md) | Numbered behavior and manual acceptance criteria |
| [Roles and permissions](docs/03-roles-and-permissions.md) | Local authority, hierarchy and access boundaries |
| [Data model](docs/04-data-model.md) | Proposed relational structure and integrity rules |
| [Architecture](docs/05-architecture.md) | Application structure and technical choices |
| [User experience](docs/06-user-experience.md) | User journeys and screen inventory |
| [Roadmap](docs/07-roadmap.md) | Phases and exit conditions |
| [Backlog](docs/08-backlog.md) | Single task list and implementation/acceptance tracker |
| [Manual acceptance](docs/09-manual-testing-and-acceptance.md) | Owner's manual-only functional testing policy |
| [Operations](docs/10-operations.md) | Setup, hosting, deployment and recovery |
| [Pilot and rollout](docs/11-pilot-and-rollout.md) | Adoption, training and rollout approach |
| [Foundation register](docs/12-boilerplate-feature-register.md) | Reuse inventory and known adaptation gaps |
| [Decisions](docs/decisions/README.md) | Accepted direction and proposed technical decisions |
| [Status / handoff](docs/PROJECT_STATUS.md) | Latest checkpoint, evidence and next work |
| [AI collaboration](docs/13-ai-collaboration-workflow.md) | Gemini implementation, reviewer assessment and owner manual acceptance |
| [First-review pack](docs/14-first-review-pack.md) | Bilingual presentation, application manual and readiness summary |
| [Gemini entry](GEMINI.md) | Current implementer assignment and handoff |

Research and detailed clarifications are linked from the requirements. All automated functional test suites are excluded at the owner's direction. Builds and proportionate static checks are allowed; functional acceptance is performed manually by the owner.

## Foundation provenance

Based on [Semera24/laravel-production-boilerplate-v2](https://github.com/Semera24/laravel-production-boilerplate-v2.git), source commit `eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5`. The root is a separately named product copy. `RefernceBoilerPlate/` is preserved and ignored by the product repository. Preserve the upstream license; the working product name does not establish trademark or organizational endorsement.

Read the operations guide before running setup scripts. Do not point this application at another project's database or use a documented sample application key.
