# Product brief

Status: initial baseline for review; 14 November 2026.

## Intent

Build a maintainable platform for Ethiopian Orthodox Sunday School membership, local departmental administration, approved upward reporting and experience sharing. “School management” was an analogy for CRUD and registration, not approval to build a conventional academic school ERP.

## Confirmed direction

- Hierarchy governs jurisdiction and reporting from local schools to higher coordinating offices.
- Senior coordinators/administrators are the operational users; teacher is not the default administrative role.
- The local lead is the principal school administrator and approves member/account-link requests and outgoing reports.
- Members have self-service profile/form/information access.
- Local management is private to the authorized school scope; shared spaces are explicit.
- Union board/divisions also have distinct responsibilities and scopes.
- Functional testing is manual, performed by the owner. No automated test suites.

## Proposed delivery boundary

One Laravel application with separate logical workspaces and one PostgreSQL database. Start with a limited pilot and reusable department/reporting structures. Build basic activity and reporting support before specialist systems. See requirements and roadmap.

## Goals

Make registration and local work practical; establish trustworthy report approval/history; give upper offices useful reporting completeness; support local language use and mobile access; keep deployment and handover understandable.

## Non-goals and failure conditions

No default examinations/payroll/full accounting, mandatory teacher portal, public member directory or automated voting. Avoid unauthorized cross-school visibility, duplicated member counts, hidden assumptions about church authority, excessive setup burden and dependence on conversation memory.

## Stakeholders and ownership

Project owner: user directing this workspace. Institutional sponsor, pilot schools, product-data responsibilities, hosting payer, support operator and final official name are not established. Do not assert an official GSSU partnership. Public research is context, not permission to use internal data or authoritative bylaws.

## Open decisions

Exact coordinating hierarchy and exceptional intervention powers; initial school/lead provisioning; parish-to-school cardinality; multiple-school memberships; member verification and child/assisted access; required report fields/cycles; sharing moderation; hosting/payment/location; latency/performance targets and retention. Resolve each when its implementation depends on it, using specific questions rather than reopening settled local approval decisions.
