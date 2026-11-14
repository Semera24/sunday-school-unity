# TASK-028 — Autonomous member registration and administration batch

Owner requests the next sustained implementation assignment after TASK-027 report. Complete this bounded membership milestone autonomously, make routine implementation decisions conservatively and document them, then stop with a detailed report. Per-slice review pauses are waived within this assignment only. Previous hierarchy implementation remains In review / owner acceptance Pending; do not describe it as accepted.

## Entry and prerequisite review

Read AGENTS, AI_START_HERE, current PROJECT_STATUS/backlog, TASK-027 report, member-access/department requirements and language/data policy. Inspect current diff and actual runtime/schema. Preserve all existing work, secrets, accounts, passwords, owner-modified samples and language preferences.

Check prior access findings in the source first: target-aware technical account-mutation denial and durable technical/business incompatibility. Carry any unresolved relevant defect into this task before extending account linking. The reviewer's source spot-check saw the new guards but is not a full authorization approval.

**No automated functional verification:** no tests/packages/suites, ad hoc CLI policy assertion scripts, password-hash assertion batches, HTTP scenario scripts or browser automation. TASK-027 reported CLI policy/password checks despite the narrower static-only instruction; do not repeat those patterns. PHP lint/format, relevant ESLint, type checks and asset builds are permitted. Functional scenarios are written as manual steps for owner execution.

## Outcome

A Sunday School lead can register and administer local members with or without login accounts, manage department participation and approve incoming memberships/account links. An approved member can view/edit permitted own data. Pending/rejected/suspended/archived memberships cannot gain private school access. Upper offices keep agreed summaries/released-report oversight without private directories; technical operators cannot use new provisioning/linking paths to take over business identities.

## Working defaults for this batch (implementation recommendations, not permanent governance decisions)

- Required registration field: original full name. Optional English spelling, phone and service notes. Reuse existing fields before introducing more. Do not collect national IDs, religious/confession history, health data, photos or extensive child/guardian data in this batch.
- Names accept Amharic, English and mixed input; no required English copy or alphabet restriction. Phone/email are not unique human identifiers; families can share contact numbers. Do not match/merge people automatically by name/phone/email.
- Lead-created records may be approved in the same transaction by the acting lead; record the approval actor/time/history. Member-originated requests and account links require explicit local-lead approval. No higher-office countersignature.
- Existing public signup remains disabled. Implement account access through school-scoped invitations, rather than opening nationwide anonymous registration. An invited person chooses their own password; local leads/technical operators must not set passwords for existing business accounts.
- Support one normal school membership context for newly onboarded members in this milestone. Do not introduce a blanket DB rule that forbids future multi-school membership. Transfers, global identity reconciliation, cross-school account claims and shared-Person cross-school edits are deferred. Do not expose other-school matches or overwrite shared person data across jurisdictions.
- No destructive deletion: archive/reactivate membership with history. Suspension/archive blocks member private access and school-scoped appointments as applicable; do not disable unrelated legitimate school contexts. A lead/admin appointment with no membership still obeys its own explicit appointment status/dates.

## Deliverables — finish as one coherent batch

### 1. Design and schema

Start with a concise plan and record assumptions in the membership requirements/data-model docs. Reuse Person, SchoolMembership, DepartmentMembership, User and ScopedAppointment relationships. Add only needed additive migrations for invitation/link request records, lifecycle history and integrity constraints. Separate member identity, login and appointment; membership never automatically grants coordinator/admin privileges. Use explicit ownership, foreign keys, appropriate uniqueness and transaction boundaries. Do not rewrite historical migrations or collapse member requests into global User roles.

### 2. Local member directory and record administration

- Paginated own-school list with scope-safe search (Amharic/mixed text and case-insensitive English), status/department filters, count labels and useful empty states.
- Lead create/view/edit local member records. Reuse a locally visible person only through an explicit local choice; suggest local possible duplicates without exposing nationwide personal data or automatically merging.
- Member detail includes membership status, account-link status, department participation and dated decision history. Clearly distinguish original person text from optional English spelling.
- Coordinator sees only authorized departmental members. Full registration/approval and school-wide edits remain the lead's authority. Avoid upgrading department coordinator access accidentally.
- Archive, suspend/reactivate/reject only through explicit validated transitions and reason where appropriate. Preserve approval history. Reject invalid/out-of-scope transitions; no generic free-form status mass assignment.

### 3. Invitations, account provisioning and local-lead account linking

- Lead can create/revoke a school/member-scoped invitation, with hashed, expiring, single-use token; bind it to intended recipient verification as appropriate. Prevent enumeration and token reuse; throttle relevant public endpoints.
- Use local Mailpit only for development delivery, not real external mail. Never put invitation secrets/passwords in logs or tracked reports. Do not ask owner to share credentials in chat.
- Invitation acceptance creates a new account only where needed or requires sign-in/verification of an existing account. Never overwrite an existing account's password/email or reveal another user's identity on email collision.
- Acceptance/request remains pending local-lead approval before an account gains access to an existing member's private record. Bind approval to the actual requested account/person/school; a typed arbitrary user_id or possession of another person's email/name must not bypass ownership proof.
- Show a lead review queue for pending new membership and account-link requests, with approve/reject and audit history. Use transactions/locking/constraints so duplicate submits and competing approvals cannot attach one record incorrectly. Establish safe idempotent behavior for double clicks.
- Existing invitation-free pending demo accounts still work. Members without an account remain valid records. Do not require login for every person registered by a school.
- Keep technical identities ineligible for business membership/account linking as well as business appointments. A later global-role edit must not turn a business account into a technical identity or vice versa. Protect newly created invitation-only users during the pre-membership period from technical credential takeover; classification cannot rely solely on an already-approved membership existing.

### 4. Department membership and member self-service

- Lead can assign one member to multiple local departments, record participation start/end dates and optional local service label, end an assignment and view history without duplicating the Person.
- Department assignment does not itself grant login privileges/coordinator appointments. Keep coordinator management in its existing audited, separately authorized flow.
- Approved members can view their own memberships/department assignments and edit only permitted personal fields. Do not allow status, school, person_id/user_id, appointments or official assignment edits through self-service payloads.
- Pending/rejected/suspended/archived access should show only permitted own request/status information and account settings. Remove private access on the next request after status change; audit/history remains available to the lead.

### 5. Authorization, history and language across every new path

Backend scope all lists, detail/update/create endpoints, invitation reviews, exports/search/media integrations if used. Do not trust posted school_id or active scope supplied by the browser. Preserve scope switching and technical/union privacy boundaries. Audit changes/decisions with actor, school, target, action and timestamps; audit logs themselves must not expose sensitive member data to technical accounts.

Put all new UI, validation, flashes and status labels in matching am/en catalogues. Amharic is default, English selectable, entered text preserved. Use responsive existing UI components, field errors, disabled busy state and clear success/empty/forbidden feedback. Do not rework the whole design system or translate unrelated admin modules. Use database-native dates; Ethiopian-calendar UX remains deferred.

### 6. Sample data and handoff

Preserve all 11 demo credentials and owner changes. Add a minimal set of clearly fictional locally scoped examples only if necessary: lead-created record without login, pending request/link, multi-department member and archived/suspended membership. Add them without resetting existing fixture statuses. Do not commit passwords, invitation tokens or real member data. Credentials stay in verified Git-ignored local files; tracked report lists emails and file location only.

Provide a morning/manual walkthrough showing: create a member without login; assign two departments; invite/accept; lead approves link; member updates mixed text; pending/rejected user denied private access; suspend/archive/reinstate; other-school access denied; coordinator cannot approve; union/technical cannot view private member details or take over an invited account. Include expected results and effects on later steps. Do not mark these scenarios passed until owner confirms.

## Boundaries

No transfers, CSV imports, billing/finance, assemblies, custom form builder, attendance, full report templates/revision workflows, cross-school identity merging, new production recovery system, third-party integrations or public deployment. No unrelated cleanup, broad formatting, framework upgrades, commit/push or external messages.

Use existing local dedicated PostgreSQL DB. Only necessary pending additive migrations are authorized; inspect existing schema first. Never reset/drop/wipe/reseed over modified records, rotate APP_KEY, overwrite .env credentials or change shared cluster authentication. No autonomous external mail delivery. Local Mailpit delivery by the implemented invitation flow is within this local task.

## Final verification/report

Run only proportionate static/type/build checks; fix relevant failures. Self-review source and diff for new account-takeover paths, cross-school data leaks, mass assignment, partial approvals, invite replays and history preservation. Do not claim functional verification from static review.

Update current checkpoint/backlog, membership requirements/data model and write `docs/reports/2026-11-14-membership-management-batch.md` with:

1. Completed/deferred behavior and exact files/migrations.
2. Assumptions and decisions still needing owner confirmation.
3. Role/scope/status matrix, relevant routes and UI entry points.
4. Account invitation/link design and how it avoids technical takeover.
5. Actual commands/results, data effects and remaining failures; no secrets.
6. Demo account/credential-file locations and local start/stop URL.
7. Numbered manual view-only and mutation scenarios, expected results and known limits.
8. Implementation In review, reviewer Pending, owner acceptance Pending; one recommended next task.

Continue independent safe work if a portion is blocked; document genuine blockers instead of guessing material governance powers. Stop after this milestone/report; do not automatically start another module.
