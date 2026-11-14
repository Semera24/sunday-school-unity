# Decision register

Updated 14 November 2026. “Accepted” means explicitly directed/confirmed by the owner; “Working” is an implementation recommendation; “Open” requires a later decision.

| ID | Decision | State | Rationale / implications |
|---|---|---|---|
| ADR-001 | Hierarchy is central; separate local school workspaces and higher-office scopes | Accepted | Geography, departments and governing bodies are related but distinct |
| ADR-002 | Local lead approves memberships/account links and outgoing reports | Accepted | No routine higher-office countersignature for each member |
| ADR-003 | Member self-service and explicit shared information/experience spaces | Accepted direction | Audience, moderation and field editing details remain proposed |
| ADR-004 | Manual functional testing only; no automated suites | Accepted | Preserve across agent handoffs, dependencies and CI |
| ADR-005 | Reuse separately named boilerplate copy and preserve reference | Accepted action | Owner requested clone/rebrand; do not develop product inside master reference |
| ADR-006 | Laravel 13, PHP 8.5, Inertia/Vue/TypeScript, PostgreSQL | Working technical baseline | Framework update requested earlier; current dependency resolution recorded in status |
| ADR-007 | Keep Inertia 2 initially; SSR disabled | Working | Avoid unrelated major migration; no authenticated-UI SSR need established |
| ADR-008 | Sunday School Unity / `sunday-school-unity` | Working name | User authorized appropriate naming; official identity not established |
| ADR-009 | One application/shared DB with scoped local workspaces | Working | Simpler operations; access isolation must be implemented explicitly |
| ADR-011 | Amharic primary/default; English secondary; preserve mixed-language data in PostgreSQL UTF-8 | Accepted direction | UI translation and stored content are separate; see language/data policy |
| ADR-012 | Hierarchy/access with fictional accounts is the first domain milestone | Accepted priority | Runtime preparation is prerequisite; detailed matrix/schema remains proposed |
| ADR-010 | Hosting/provider, institutional sponsor, exact hierarchy and provisioning | Open | Do not invent these decisions or purchase services |

For a new consequential decision, add a short dedicated record with date, context, alternatives, decision, status, consequences and linked requirements/tasks. Change status only with evidence; implementation convenience is not stakeholder approval.
