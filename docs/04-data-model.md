# Conceptual relational data model

Status: conceptual model plus implemented local demonstration/member-management schema through TASK-029. Applied additive migrations are recorded in PROJECT_STATUS. Unimplemented entities and production governance choices remain proposed; this document does not independently authorize further migrations.

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

## TASK-024 bounded hierarchy/access demonstration schema (Proposed for production)

Implemented as an additive demonstration baseline (`2026_11_14_000002_create_hierarchy_access_demo_tables.php`):

- `organizations`: Self-referencing coordinating tree (`parent_id`, `code`, `type` in `union|diocese|district|sunday_school`, `name_am`, `name_en`) with separate geography and parish context columns (`city_am`, `city_en`, `parish_name_am`, `parish_name_en`). Enforces parent-type progression and prevents hierarchy cycles.
- `organizational_units`: Internal units (`organization_id`, `code`, `unit_kind` in `department|division|board`, `category_code`, `name_am`, `name_en`) kept distinct from geographic hierarchy levels.
- `people`: Human identity (`full_name`, `name_en`, `phone`, `service_notes`) stored independently of login accounts (`users`).
- `school_memberships`: Links `person_id` to a Sunday School `organization_id` and optional `user_id`, tracking `status` (`pending|approved|rejected`), `application_note`, `approved_by_user_id` and `approved_at`.
- `department_memberships`: Links `school_membership_id` to a local `organizational_unit_id` with effective dates (`starts_on`, `ends_on`, `is_active`) so one person can serve in multiple departments without duplicate person rows.
- `scoped_appointments`: Dated role/capability assignments (`user_id`, `person_id`, `organization_id`, `organizational_unit_id`, `role_key`, `title_am`, `title_en`, `functional_category`, `starts_on`, `ends_on`, `is_active`). Active appointment selection is tracked per session (`active_appointment_id`) and validated server-side; switching appointments never combines privileges.
- `demo_reports`: Minimal fixture records (`organization_id`, `organizational_unit_id`, `category_code`, `period_label`, `title_am`, `summary_am`, `activity_count`, `status` in `draft|submitted_local|released`, `submitted_by_user_id`, `approved_by_user_id`, `released_at`) used strictly to demonstrate the local-draft versus lead-released upward visibility boundary.

## TASK-028 membership lifecycle, invitations & department history extension (Proposed for production)

Implemented as an additive migration (`2026_11_14_000003_add_membership_lifecycle_and_invitations.php`):

- `users` (extended): Adds `is_business_account` (`boolean`, default `false`) so accounts created through school invitations are immediately classified as protected church/business identities (`User::hasBusinessIdentity()`) even before their membership link is approved by a local Sunday School Lead.
- `school_memberships` (extended):
  - Supports lifecycle `status` values `pending`, `approved`, `rejected`, `suspended`, and `archived`.
  - Adds `pending_user_id` (`nullable()->constrained('users')->nullOnDelete()`) and `account_link_status` (`none|pending_acceptance|pending_lead_approval|approved|rejected`, default `'none'`) so an accepted invitation on an existing member record awaits explicit local-lead approval before setting `user_id`.
  - Adds lifecycle audit fields: `status_reason`, `created_by_user_id`, `rejected_by_user_id`, `rejected_at`, `suspended_by_user_id`, `suspended_at`, `archived_by_user_id`, `archived_at`, and `ended_on`.
- `department_memberships` (extended):
  - Drops the single-row unique constraint on `['school_membership_id', 'organizational_unit_id']` (replacing it with a non-unique lookup index and transactional active-row validation) and adds `assigned_by_user_id` and `ended_by_user_id`, allowing ended historical department participations (`is_active = false`, `ends_on` set) to be preserved alongside new active participations.
- `membership_decisions`: Immutable dated decision log (`id`, `school_membership_id`, `organization_id`, `actor_user_id`, `target_user_id`, `action`, `from_status`, `to_status`, `reason`, `timestamps`) preserving every registration, approval, rejection, suspension, reactivation, archive, and account-link decision.
- `membership_invitations`: School/member-scoped single-use onboarding and account-link invitations (`id`, `organization_id`, `school_membership_id`, `token_hash` unique SHA-256 digest, `invited_email`, `invited_name`, `status` in `pending_acceptance|pending_lead_approval|approved|rejected|revoked|expired`, `created_by_user_id`, `expires_at`, `accepted_at`, `accepted_by_user_id`, `revoked_at`, `revoked_by_user_id`, `reviewed_by_user_id`, `reviewed_at`, `review_note`, `timestamps`). Raw invitation tokens are never stored in the database or written to logs.

## TASK-029 database-backed integrity & mail-state hardening (Proposed for production)

Implemented as an additive migration (`2026_11_14_000004_harden_membership_invitations_and_department_participations.php`):

- `membership_invitations` (extended):
  - Adds `mail_delivery_status` (`pending|sent|failed`, default `'pending'`), `mail_sent_at` (`timestamp`, nullable), and `mail_error` (`varchar(255)`, nullable sanitized code) so invitation email delivery happens strictly post-commit and failed sends can be retried via `POST /invitations/{membershipInvitation}/resend`.
  - Adds PostgreSQL partial unique index `membership_invitations_one_open_per_member_uidx` on `(school_membership_id) WHERE status IN ('pending_acceptance', 'pending_lead_approval')` enforcing at most one open invitation or pending link request per membership.
- `school_memberships` (extended):
  - Adds PostgreSQL partial unique indexes `school_memberships_org_user_uidx` on `(organization_id, user_id) WHERE user_id IS NOT NULL` and `school_memberships_org_pending_user_uidx` on `(organization_id, pending_user_id) WHERE pending_user_id IS NOT NULL` preventing a login account from binding to multiple member records in the same Sunday School.
- `department_memberships` (extended):
  - Adds PostgreSQL partial unique index `dept_memberships_one_active_per_member_unit_uidx` on `(school_membership_id, organizational_unit_id) WHERE is_active = true` enforcing at most one active participation per member and department while allowing unlimited ended historical intervals (`is_active = false`).
- **Transaction lock acquisition order:** Controllers that touch memberships, invitations, users, department participations, or appointments lock rows in a fixed order: `SchoolMembership` -> `MembershipInvitation` -> `User` -> `DepartmentMembership` / `ScopedAppointment`.
