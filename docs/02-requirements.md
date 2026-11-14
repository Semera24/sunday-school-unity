# Requirements baseline

Status: confirmed product direction plus proposed MVP behavior; not all workflows approved. Updated 14 November 2026.

Confirmed detail takes precedence over earlier speculative research. Read [hierarchy/member access](requirements/hierarchy-member-access-sharing.md) and [department coordination](requirements/department-coordination.md) for requirement IDs and detailed manual acceptance. Preserve those IDs when turning them into tasks.

## Core behavior

| ID | Requirement | Basis | Manual acceptance |
|---|---|---|---|
| CORE-001 | Represent coordinating organizations and local Sunday Schools with explicit parent/jurisdiction relationships | Confirmed direction; exact levels open | Two schools in one city remain distinct; authorized upper office sees its branch |
| CORE-002 | Represent departments locally and divisions/boards at higher levels without conflating them with geography | Confirmed direction | Add a local department and union division independently of cities |
| CORE-003 | Assign user capabilities to organizational/unit scope | Confirmed direction | A school lead cannot manage another school's private records |
| CORE-004 | Keep person/member information separate from login identity | Confirmed direction | Register a person without a login; later link an approved account |
| CORE-005 | Let the lead approve memberships and existing-record account links | Confirmed | Pending applicant cannot access restricted member data; approved member can access own profile |
| CORE-006 | Let members maintain permitted profile fields, fill assigned forms and read authorized information | Confirmed direction; fields open | Member cannot change official roles or another person's response |
| CORE-007 | Configure school departments and dated coordinator/member assignments | Confirmed direction; catalogue proposed | One person serves in two departments without duplicate person records |
| CORE-008 | Capture department activities and participation | Proposed detail | Coordinator records an activity only within assigned scope |
| CORE-009 | Require local lead approval before upward report release | Confirmed | Upper office cannot read an unapproved draft |
| CORE-010 | Review released reports and retain correction/version history | Proposed detail | Returning and resubmitting creates explainable revision history |
| CORE-011 | Consolidate comparable reports and expose completeness | Confirmed oversight; calculations proposed | Missing school is labelled missing, not zero; superseded totals not double-counted |
| CORE-012 | Publish shared announcements/resources/experience items to explicit audiences | Confirmed sharing; content/moderation detail proposed | Shared item is readable by audience; private records remain inaccessible |
| CORE-013 | Provide authorized exports and private supporting documents | Proposed delivery requirement | Export/download obeys the same scope as screen access |
| CORE-014 | Record significant appointments, approvals, releases and updates | Proposed delivery requirement | Actor, time and relevant scope can be inspected |

## Non-functional requirements — proposed

| ID | Requirement | Manual review/check |
|---|---|---|
| NFR-001 | Mobile-friendly forms, tables and navigation | Owner uses selected small-screen device; actions and errors remain usable |
| NFR-002 | Amharic-primary/English-secondary UI; preserve mixed-language data in PostgreSQL UTF-8 | Inspect core journeys, print output and long Ethiopic names |
| NFR-003 | Ethiopian-calendar entry/display where agreed; preserve correct date semantics | Check known dates and conversions with owner |
| NFR-004 | Bound lists/uploads/imports and visibly distinguish saved/pending/failed | Use representative data and interrupted connections |
| NFR-005 | Preserve relational integrity, scoped authorization and transaction consistency | Owner manually exercises invalid links and interrupted workflows |
| NFR-006 | Recover application data and private files from backups | Operator demonstrates restore in isolated environment before pilot |
| NFR-007 | Keep hosting, configuration and continuation instructions current | New agent/operator follows documented checkpoint and setup |

No numeric performance target has been agreed. No offline synchronization guarantee exists. These must be defined with pilot conditions rather than invented.

## MVP exclusions

Full accounting/POS/payroll, inventory ERP, conventional examinations/promotion, parent portal, electronic voting, native mobile application, real-time messaging and automatic AI features are not approved scope. Full import workflows and specialist department modules require separate acceptance criteria. Automated tests and coverage are excluded by explicit owner instruction.

## Change control

Mark additions Proposed until agreed. Update the relevant requirement, role/data implications, task and decision/status entry together. Do not infer official institutional rules from a local example or from existing boilerplate behavior. See [manual acceptance](09-manual-testing-and-acceptance.md).

Language/storage details and current coverage: [language/data requirements](requirements/language-and-data.md). Hierarchy/privilege demonstration with fictional accounts is the first domain milestone; see [TASK-024](tasks/TASK-024-hierarchy-access-demo.md).
