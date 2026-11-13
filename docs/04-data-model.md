# Conceptual relational data model

Status: proposed design for discussion; domain tables do not exist yet. No SQL migrations in this document are approved for execution.

## Core entity groups

| Entity/group | Purpose and relationship |
|---|---|
| Organization / organization type | Coordinating unit with an allowed parent; prevent cycles |
| Location / parish | Geography and church identity, separate from authorization hierarchy |
| Sunday School | Local management scope associated with parish and coordinating unit; parish cardinality open |
| Organizational unit / category | Actual department/division within an organization; comparable category retained despite local labels |
| Governing body / appointment | Board and dated responsibilities; separate from department and geography |
| Person | Member's human identity, independent of login |
| School membership | Person-school association, status and service dates; linked approval history |
| User account / member-account link | Login identity and verified link to person/membership; final linking approved by lead |
| Department membership | Person/unit association; one person can participate in several units |
| Scoped role assignment | Account, role, organization/unit scope, effective dates; active scope drives authorization |
| Membership request / decision | Pending application or record claim; requester, lead decision, time and reason |
| Form definition/version / audience / response | Assigned forms with stable version and controlled answers |
| Activity / participation | Department event/work record with people and involvement |
| Reporting cycle / template version | Periods, required indicators, due dates and comparable definitions |
| Report / revision / review / release | Origin, immutable submitted version, local decision and upper recipient/history |
| Shared item / audience / publication decision | Experience/resource/announcement with explicit visibility |
| Document / file attachment | Private file ownership and authorized association |
| Audit finding / follow-up | Business audit work, separate from application activity logs |

## Integrity rules

- Stable primary keys, foreign keys, explicit join records; no comma-separated relationship IDs.
- Validate that parent/child, member/department, report/template and attachment/owner scopes are compatible. Existing IDs alone do not establish permission.
- Give associations effective dates and preserve history instead of overwriting appointments or memberships.
- Define uniqueness per real business scope. Names and family phone numbers are not unique person identifiers.
- Use atomic operations for linked membership approval/account association and report approval/release.
- Preserve form/template/report versions so a later edit cannot silently rewrite an earlier response or approved total.
- Distinguish unique members, department participations and submitted totals; never sum them interchangeably.
- Define deletion/anonymization/retention before destructive features. Deactivation must preserve necessary history.
- Use dates for date-only values and timezone-aware timestamps for events; preserve original calendar meaning in conversion workflows.

## Inherited schema

Generic users, authentication, permission pivots, settings, notifications, media, activity logs, import runs, queue/cache/session tables exist as migrations. These support infrastructure and do not replace the domain entities above. Proposed extensions require changes to authorization, not just new foreign keys.

## Decisions before first domain migration

School/parish cardinality, hierarchy types, person visibility/duplicate resolution, multi-school membership, organization versus unit scope representation, lead provisioning, required profile fields, child access and deletion rules. Resolve with the owner in bounded groups before implementing the affected entity group.
