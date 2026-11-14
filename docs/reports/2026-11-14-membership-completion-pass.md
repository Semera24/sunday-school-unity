# Implementation Report — TASK-029 Membership Management Completion & Hardening Pass

- **Task ID:** `TASK-029` (Completion and hardening pass over `TASK-028`; advances local subsets of `TASK-010`, `TASK-011`, and `TASK-012`)
- **Date:** 14 November 2026
- **Implementer:** Gemini / Antigravity
- **Implementation Status:** `In review` (implemented and verified via permitted static/lint/type/build checks)
- **Owner Manual Acceptance Status:** `Pending` (not yet manually verified by the project owner)

---

## 1. Executive Summary & Scope

This pass completes and hardens the `TASK-028` local Sunday School membership management milestone without rebuilding existing features or starting another domain module:

1. **Competing Invitation & Account-Link State Consistency (`MEM-002`, `MEM-005`, `MEM-006`):**
   - Enforces at most one open invitation (`pending_acceptance` or `pending_lead_approval`) per `SchoolMembership` at both the PostgreSQL index layer (`membership_invitations_one_open_per_member_uidx`) and the transactional controller layer.
   - Blocks issuing or resending a new invitation while an accepted request is awaiting Local Lead review (`pending_lead_approval`), and never silently overwrites `pending_user_id`.
   - Enforces a deterministic row-lock acquisition order (`SchoolMembership` -> `MembershipInvitation` -> `User` -> `DepartmentMembership` / `ScopedAppointment`) across all invitation and membership lifecycle transitions.
   - Binds `approveInvitationLink` and `rejectInvitationLink` strictly to the current pending request (`membership.account_link_status === 'pending_lead_approval'`, `membership.pending_user_id === invitation.accepted_by_user_id`, and `membership.user_id === null`).
   - Separates membership lifecycle approval (`transitionMembershipStatus` with `approve` or `reactivate`) from unreviewed invitation links so approving/reactivating a membership never blindly approves an unreviewed `pending_user_id` or restores deactivated appointments, while `reject`, `suspend`, and `archive` revoke open invitations and clear `pending_user_id`.
   - Handles duplicate button clicks and two-tab decisions idempotently or with translated validation messages rather than `500` errors.
   - Prevents same-school duplicate user links (`school_memberships_org_user_uidx`, `school_memberships_org_pending_user_uidx`), defers cross-school account claims (`error_cross_school_claim_deferred`), and blocks accepting an invitation while signed in as a different account (`wrongSignedInAccount`).
2. **Department Participation Integrity & History (`DEP-004`):**
   - Adds PostgreSQL partial unique index `dept_memberships_one_active_per_member_unit_uidx` enforcing at most one `is_active = true` row per `(school_membership_id, organizational_unit_id)` while preserving unlimited ended historical intervals (`is_active = false`).
   - Serializes department assignment and interval updates under the parent `SchoolMembership` lock and catches `UniqueConstraintViolationException` as a translated validation error.
   - Preserves ended interval history: returning a member to a department (`action = 'reactivate'`) creates a **new** dated `DepartmentMembership` row (`department-memberships.reassigned`) with `assigned_by_user_id` set to the acting Local Lead rather than erasing the prior row’s `ends_on` or `ended_by_user_id`.
   - Enforces explicit date validation (`ends_on >= starts_on`, no past `ends_on` on active assignments, no erasing `ends_on` on ended rows, and new service periods cannot start before the previous ended interval’s `ends_on`) without silently clamping invalid dates.
   - Applies `DepartmentMembership::currentlyEffective()` (`is_active = true`, `starts_on <= today`, and `ends_on IS NULL OR ends_on >= today`) to department coordinator member visibility and active department summaries.
3. **Post-Commit Mail Delivery, Failure Visibility & Resend (`MEM-002`):**
   - Moves `SchoolMembershipInvitationMail` delivery strictly outside database transactions (`deliverCommittedInvitationMail()`).
   - Tracks `mail_delivery_status` (`pending|sent|failed`), `mail_sent_at`, and sanitized `mail_error` on `membership_invitations`.
   - Provides a Lead-only resend/retry endpoint (`POST /invitations/{membershipInvitation}/resend`) that rotates a fresh single-use SHA-256 token hash, extends `expires_at`, records `MembershipDecision::ACTION_INVITATION_RESENT` and `invitations.resent`, and sends the email post-commit.
   - Moves `Auth::login()` and session regeneration in `MembershipInvitationController::accept()` strictly post-commit.
4. **Original Scope Omissions & Bilingual Parity (`NFR-001`, `DATA-001`, `DATA-002`):**
   - Protects `Person` records shared across multiple Sunday Schools from unilateral local or self-service edits (`shared_person_edit_deferred`).
   - Ensures `/my-profile` (`showMyProfile`) withholds target-member private fields (`phone`, `serviceNotes`, `membershipCode`, `joinedOn`, `departments`) while a link is in `pending_lead_approval`, and shows a read-only rejected/revoked status card if an unlinked user’s invitation was rejected or revoked.
   - Exposes `application_note` on the School Lead member registration form and removes hardcoded English fallback strings from Vue review actions in favor of localized backend defaults (`lang/am/messages.php` and `lang/en/messages.php`).

---

## 2. Resolution of Reviewer Findings

### Finding 1 — Competing Invitation and Account-Link State
- **Single Open Invitation Rule:**
  - `HierarchyDemoController::storeMember`, `storeInvitation`, and `resendInvitation` lock the `SchoolMembership` row (`lockForUpdate()`) and reject issuing/resending an invitation when `$membership->user_id !== null`, `$membership->account_link_status === 'approved'`, `$membership->account_link_status === 'pending_lead_approval'`, `$membership->pending_user_id !== null`, or when an open `pending_lead_approval` invitation exists (`error_pending_lead_review_exists`).
  - Only unaccepted (`pending_acceptance` or `expired`) invitations are superseded when a new invitation is issued or resent.
- **Bound Approval & Rejection:**
  - `HierarchyDemoController::approveInvitationLink()` and `rejectInvitationLink()` lock `SchoolMembership` first and `MembershipInvitation` second, handle idempotent repeat clicks (`status === 'approved'` or `status === 'rejected'`), and verify that `$membership->account_link_status === 'pending_lead_approval'`, `(int) $membership->pending_user_id === (int) $lockedInv->accepted_by_user_id`, and `$membership->user_id === null`.
  - `approveMembership()` and `transitionMembershipStatus(action: 'approve' | 'reactivate')` approve/reactivate the membership status and only auto-approve an invitation when the user is **already bound** as `$membership->user_id`; if an unreviewed `$membership->pending_user_id` exists while `$membership->user_id === null`, `account_link_status` remains `pending_lead_approval` for explicit review.
  - `transitionMembershipStatus(action: 'reject' | 'suspend' | 'archive')` revokes any open invitations (`pending_acceptance`, `pending_lead_approval`), clears `pending_user_id`, and resets unapproved `account_link_status`.
- **Same-School Uniqueness, Cross-School Deferral & Wrong Signed-In Account Guard:**
  - `storeInvitation` and `MembershipInvitationController::accept()` check whether the invited/accepting `User` already holds `user_id` or `pending_user_id` on another `SchoolMembership` in the same school (`error_user_already_member_same_school`) or in another school (`error_cross_school_claim_deferred`).
  - `MembershipInvitationController::show()` and `accept()` detect when a user is signed in with an email different from `invitation.invited_email` (`wrongSignedInAccount`), displaying a localized warning card with a Sign Out button and rejecting `POST /invitations/{token}/accept` with `error_wrong_signed_in_account`.

### Finding 2 — Department Participation Integrity and History
- **Partial Unique Index & Row Locking:**
  - Migration `2026_11_14_000004_harden_membership_invitations_and_department_participations.php` creates `dept_memberships_one_active_per_member_unit_uidx` on `department_memberships (school_membership_id, organizational_unit_id) WHERE is_active = true`.
  - `storeDepartmentMembership` and `updateDepartmentMembership` lock the parent `SchoolMembership` row (`lockForUpdate()`) before checking or mutating `DepartmentMembership` rows, and catch `UniqueConstraintViolationException` as `messages.demo.department_membership.already_active`.
- **Preserved Ended Intervals on Re-assignment (`action = 'reactivate'`):**
  - When a member returns to a department after an ended interval (`is_active = false`), `updateDepartmentMembership` leaves the historical ended row untouched (`starts_on`, `ends_on`, `assigned_by_user_id`, `ended_by_user_id` preserved) and creates a **new** active `DepartmentMembership` row (`department-memberships.reassigned`) with validated `starts_on >= previous.ends_on`.
- **Strict Date Validation & Effective-Date Semantics:**
  - `storeDepartmentMembership` and `updateDepartmentMembership` validate `ends_on >= starts_on` (`error_end_before_start`), forbid past `ends_on` on active participations (`error_active_end_in_past`), forbid erasing `ends_on` on ended rows (`error_ended_requires_end_date`), and never silently clamp invalid end dates to start dates.
  - `DepartmentMembership::scopeCurrentlyEffective()` and `isCurrentlyEffective()` require `is_active = true`, `starts_on <= today`, and `(ends_on IS NULL OR ends_on >= today)`. Future-dated active rows display a `Scheduled (Starts :date)` badge on `/members/{id}` and do not grant department coordinator visibility until their start date arrives.

### Finding 3 — Post-Commit Mail Delivery, Resend & Session Safety
- **Post-Commit Mail Delivery (`deliverCommittedInvitationMail`):**
  - `issueInvitationForMembership()` creates the invitation with `mail_delivery_status = 'pending'` inside the DB transaction and returns `['invitation' => $invitation, 'rawToken' => $rawToken]` without calling `Mail::send()`.
  - After the DB transaction commits, `deliverCommittedInvitationMail()` sends `SchoolMembershipInvitationMail` inside a `try/catch (\Throwable)`. On success, it updates `mail_delivery_status = 'sent'` and `mail_sent_at = now()`; on failure, it updates `mail_delivery_status = 'failed'` and `mail_error = 'mail_transport_failed'`, records `invitations.mail-failed` in `ActivityLog`, and returns a clear warning flash message to the Lead without rolling back the created member or invitation.
- **Resend / Retry Endpoint (`POST /invitations/{membershipInvitation}/resend`):**
  - Revokes the prior unaccepted/expired invitation row, issues a new single-use SHA-256 `token_hash` with a fresh 7-day expiry, records `MembershipDecision::ACTION_INVITATION_RESENT` and `invitations.resent`, and delivers the email post-commit.
- **Post-Commit Login & Persistent Expiry:**
  - `MembershipInvitationController::show()` and `accept()` persist `status = 'expired'` outside throwing validation transactions so expiry state is never rolled back.
  - `MembershipInvitationController::accept()` performs `Auth::login($user)` and `$request->session()->regenerate()` only after `DB::transaction(...)` commits.

---

## 3. Exact Database Indexes, Locks & Validation Rules Added

### PostgreSQL Partial Unique Indexes (`2026_11_14_000004_harden_membership_invitations_and_department_participations.php`)
1. `dept_memberships_one_active_per_member_unit_uidx`:
   ```sql
   CREATE UNIQUE INDEX dept_memberships_one_active_per_member_unit_uidx
   ON department_memberships (school_membership_id, organizational_unit_id)
   WHERE is_active = true;
   ```
2. `membership_invitations_one_open_per_member_uidx`:
   ```sql
   CREATE UNIQUE INDEX membership_invitations_one_open_per_member_uidx
   ON membership_invitations (school_membership_id)
   WHERE status IN ('pending_acceptance', 'pending_lead_approval');
   ```
3. `school_memberships_org_user_uidx`:
   ```sql
   CREATE UNIQUE INDEX school_memberships_org_user_uidx
   ON school_memberships (organization_id, user_id)
   WHERE user_id IS NOT NULL;
   ```
4. `school_memberships_org_pending_user_uidx`:
   ```sql
   CREATE UNIQUE INDEX school_memberships_org_pending_user_uidx
   ON school_memberships (organization_id, pending_user_id)
   WHERE pending_user_id IS NOT NULL;
   ```

### Transaction Lock Order
Every mutating controller action acquires row-level `FOR UPDATE` locks in this fixed order:
1. `SchoolMembership` (`lockForUpdate()`)
2. `MembershipInvitation` (`lockForUpdate()`)
3. `User` / `Person` (`lockForUpdate()`)
4. `DepartmentMembership` / `ScopedAppointment` (`lockForUpdate()`)

---

## 4. Files Created or Modified

### Created
- [2026_11_14_000004_harden_membership_invitations_and_department_participations.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/database/migrations/2026_11_14_000004_harden_membership_invitations_and_department_participations.php)
- [2026-11-14-membership-completion-pass.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/docs/reports/2026-11-14-membership-completion-pass.md)

### Modified
- [HierarchyDemoController.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Http/Controllers/HierarchyDemoController.php)
- [MembershipInvitationController.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Http/Controllers/MembershipInvitationController.php)
- [ActivityLog.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Models/ActivityLog.php)
- [DepartmentMembership.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Models/DepartmentMembership.php)
- [MembershipDecision.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Models/MembershipDecision.php)
- [MembershipInvitation.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Models/MembershipInvitation.php)
- [HierarchyScopeContext.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/app/Support/HierarchyScopeContext.php)
- [HierarchyAccessDemoSeeder.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/database/seeders/HierarchyAccessDemoSeeder.php)
- [web.php](file:///Users/yonassayfu/Documents/Project/SundaySchool/routes/web.php)
- [messages.php (Amharic)](file:///Users/yonassayfu/Documents/Project/SundaySchool/lang/am/messages.php)
- [messages.php (English)](file:///Users/yonassayfu/Documents/Project/SundaySchool/lang/en/messages.php)
- [schools/Show.vue](file:///Users/yonassayfu/Documents/Project/SundaySchool/resources/js/pages/schools/Show.vue)
- [members/Show.vue](file:///Users/yonassayfu/Documents/Project/SundaySchool/resources/js/pages/members/Show.vue)
- [invitations/Show.vue](file:///Users/yonassayfu/Documents/Project/SundaySchool/resources/js/pages/invitations/Show.vue)
- [hierarchy-member-access-sharing.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/docs/requirements/hierarchy-member-access-sharing.md)
- [04-data-model.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/docs/04-data-model.md)
- [PROJECT_STATUS.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/docs/PROJECT_STATUS.md)
- [08-backlog.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/docs/08-backlog.md)

---

## 5. Exact Commands Executed & Results

1. **Read-only pre-migration conflict check on `sunday_school_unity`:**
   - Command: `/Users/Shared/DBngin/postgresql/17.0/bin/psql -h 127.0.0.1 -p 5432 -U sunday_school_unity -d sunday_school_unity -c "SELECT count(*) AS dept_memberships_count FROM department_memberships; ..."`
   - Result: Exit `0` (`0` duplicate active department participations, `0` duplicate open invitations, `0` duplicate org user links).
2. **Additive Migration & Idempotent Demo Seeder:**
   - Command: `php artisan migrate --no-interaction && php artisan db:seed --class=HierarchyAccessDemoSeeder --no-interaction`
   - Result: Exit `0` (`2026_11_14_000004_harden_membership_invitations_and_department_participations 26.45ms DONE`, `HierarchyAccessDemoSeeder 107 ms DONE`). All 11 demo accounts and passwords in `storage/app/private/demo-credentials.md` and `storage/app/private/demo-credentials.json` preserved unchanged.
3. **PHP Formatting & Lint Check:**
   - Command: `./vendor/bin/pint && composer run lint:check`
   - Result: Exit `0` (`{"tool":"pint","result":"passed"}`).
4. **Frontend Formatting, ESLint, TypeScript Type Check & Production Bundle Build:**
   - Command: `npx eslint --fix resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue && npx eslint resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue && npm run types:check && npm run build`
   - Result: Exit `0` (`vue-tsc --noEmit` passed with 0 errors; `vite build` succeeded in `4.55s`).
5. **Prettier & ESLint Verification:**
   - Command: `npx prettier --write resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue && npx eslint resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue`
   - Result: Exit `0` (all 4 Vue pages unchanged and 0 lint warnings/errors).

*(In strict compliance with the owner's testing policy and `TASK-029` instructions, zero automated tests or ad hoc CLI functional/policy assertion scripts were written or run.)*

---

## 6. Known Limitations & Deferred Decisions

1. **Cross-School Multi-Membership & Shared-Person Governance Deferred:**
   - A `User` account already linked to (or awaiting link review in) one Sunday School cannot accept an invitation for a second Sunday School in this milestone (`error_cross_school_claim_deferred`). Similarly, if a `Person` row is ever linked to more than one Sunday School, direct edits to shared `Person` fields are blocked (`shared_person_edit_deferred`) until cross-school transfer/sharing rules (`DEP-005`) are designed.
2. **Assigned Custom Member Forms (`MEM-003`, `MEM-004`):**
   - Dynamic versioned questionnaires/forms assigned to members remain planned under `TASK-011`.
3. **Inherited Non-Demo Surfaces:**
   - Inherited boilerplate screens outside the core Sunday School journeys remain progressively localized as recorded in `docs/requirements/language-and-data.md`.

---

## 7. Numbered Manual Acceptance Checklist for the Owner

Use the credentials in Git-ignored [demo-credentials.md](file:///Users/yonassayfu/Documents/Project/SundaySchool/storage/app/private/demo-credentials.md) and local Mailpit (`http://127.0.0.1:8025` or SMTP `127.0.0.1:1025`).

1. **Single Open Invitation & Blocked Competing Issuance (`pending_lead_approval`):**
   - Sign in as `lead-a@example.test`, open `/members/3` (`ማርታ ገብሩ`), and send an onboarding invitation to `marta-link@example.test`.
   - Open the invitation link from Mailpit in a private/incognito window (or after signing out) and accept it by creating the login account for `marta-link@example.test`.
   - Return to `lead-a@example.test` and refresh `/members/3`:
     - **Expected:** The invitation form is replaced by the amber notice (`An accepted account-link request is currently awaiting Local Lead approval...`). Attempting to `POST /schools/4/invitations` or `POST /invitations/{id}/resend` while the link is in `pending_lead_approval` is rejected with a translated validation error.
2. **Wrong Signed-In Account Protection on Invitation Acceptance:**
   - As `lead-a@example.test`, register a new member or open an unlinked member and send an invitation to `new-invitee@example.test`.
   - While still signed in as `lead-a@example.test` (or `member-a@example.test`), open the `/invitations/{token}` URL from Mailpit.
   - **Expected:** The page displays the **Signed In as a Different Account** warning card showing both `current_email` and `invited_email` with a **Sign Out of Current Account** button, and does not allow submitting the link under the wrong signed-in user.
3. **Separated Membership Status Approval vs. Account-Link Approval:**
   - As `lead-a@example.test`, register a new member with **Initial Status = Pending Review Queue** and **Invite Email = `pending-invitee@example.test`**.
   - Accept the invitation as `pending-invitee@example.test` so `account_link_status = pending_lead_approval`.
   - As `lead-a@example.test`, approve the **membership status** (`POST /memberships/{id}/status` with `action = approve`) without clicking **Approve Account Link** yet.
   - **Expected:** The membership status becomes `approved`, but `pending_user_id` is **not** silently promoted to `user_id`; the account link remains in the **Pending Account-Link Requests** queue until the Lead explicitly clicks **Approve Account Link**.
4. **Target Member Privacy During `pending_lead_approval` and After Link Rejection:**
   - While `marta-link@example.test` is in `pending_lead_approval` for existing member `#3` (`ማርታ ገብሩ`), sign in as `marta-link@example.test` and open `/my-profile`.
   - **Expected:** Only the school name, pending-link banner, and the user’s own link request decision are shown; Marta’s existing phone (`+251911000103`), service notes, membership code (`SSU-A-003`), and departments are withheld until the Lead approves the link.
   - As `lead-a@example.test`, reject the account-link request on `/members/3` without entering a reason first, then with a reason:
     - **Expected:** Rejecting without a reason returns a translated validation error (`reason_required`). Rejecting with a reason clears `pending_user_id` on `#3`, and when `marta-link@example.test` refreshes `/my-profile`, they see the read-only rejected banner with the recorded reason and still cannot access `/schools/4` (`403`).
5. **Post-Commit Mail Status & Resend / Retry Flow:**
   - As `lead-a@example.test`, register a member or issue an invitation on `/schools/4`.
   - Observe the `Email Sent` (`ኢሜይል ተልኳል`) badge next to the active invitation in the Review Queue (`/schools/4`) and on `/members/{id}`.
   - Click **Resend / Retry Email** (`ግብዣ እንደገና ላክ`) on an unaccepted invitation:
     - **Expected:** The previous invitation token is revoked (`Superseded by a newly re-issued invitation link`), a new single-use token is emailed to Mailpit, and `invitation_resent` is recorded in the member’s Decision History. Opening the old link shows **Invitation Revoked**.
6. **Department Participation Partial Unique Constraint, Date Validation & New Service Period on Return:**
   - Sign in as `lead-a@example.test` and open `/members/1` (`ሐና Hanna ደሞ`).
   - Try assigning `ሐና` to `የመዝሙር ክፍል` again while she already has an active participation:
     - **Expected:** Rejected with translated validation error (`already_active`).
   - Try assigning a department with `ends_on` earlier than `starts_on`:
     - **Expected:** Rejected with translated validation error (`error_end_before_start`) rather than silently clamping the end date.
   - End an active department participation on `/members/1`:
     - **Expected:** The row’s status changes to `Ended (YYYY-MM-DD)` and preserves its original `starts_on` and `ends_on`.
   - Click **Start New Service Period** (`አዲስ የአገልግሎት ጊዜ ጀምር`) on that ended row:
     - **Expected:** A **new** active `DepartmentMembership` row is created for the new period while the ended row remains intact in the participation history list below it.
