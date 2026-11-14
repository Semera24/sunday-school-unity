# TASK-028 Implementation Report — Autonomous Member Registration and Administration Batch

## 1. Task ID & Title
- **Task ID:** `TASK-028` (delivers the local membership, invitation/account-link, lifecycle, and department-participation subset of `TASK-010`, `TASK-011`, and `TASK-012`)
- **Title:** Autonomous member registration and administration batch
- **Date:** 14 November 2026
- **Implementation Status:** `In review` (Code implemented, additive migration applied, idempotent demo seeder executed, static/type/build checks passed)
- **Manual Acceptance Status:** `Pending` (No owner manual functional acceptance has been performed yet)

---

## 2. Summary of Deliverables & Prerequisite Verification

### Prerequisite Access Guard Verification (`TASK-027` + Pre-Membership Invited Identity Protection)
- **Verified `TASK-027` guards:** Confirmed in source that `User::hasTechnicalIdentity()`, `User::hasBusinessIdentity()`, `UserPolicy`, `UserManagementController`, and `ScopedAppointment::assertValidScopeAndDates()` remain intact and prevent technical operators (`tech-admin@example.test`) from viewing, editing, resetting credentials, granting roles, or deleting business/member accounts, and prevent technical accounts from receiving business appointments.
- **Extended business-identity classification to pre-approval invited accounts:**
  - Added `users.is_business_account` (`boolean`, default `false`), `User::pendingSchoolMemberships()` (`pending_user_id`), and `User::acceptedMembershipInvitations()` (`accepted_by_user_id`).
  - Updated `User::hasBusinessIdentity()` and `User::scopeTechnicalOnly()` so any user account created via or linked to a school invitation is **immediately and permanently** classified as a protected business identity—even while their membership/account-link is still in `pending_lead_approval`. Consequently, `tech-admin@example.test` receives `403` on `/admin/users/{id}/edit`, `PUT /admin/users/{id}`, `PUT /admin/users/{id}/roles`, and `DELETE /admin/users/{id}` for newly invited users before and after local-lead approval, and those users are excluded from technical global search and CSV/XLSX/XML/PDF exports.

### Deliverable 1 — Membership Model & Schema Alignment
- Updated `docs/requirements/hierarchy-member-access-sharing.md` and `docs/04-data-model.md` to record working defaults for `TASK-028` without inventing final church governance policy.
- Created and executed one additive migration (`database/migrations/2026_11_14_000003_add_membership_lifecycle_and_invitations.php`):
  - Extended `users` with `is_business_account`.
  - Extended `school_memberships` with `pending_user_id`, `account_link_status` (`none|pending_acceptance|pending_lead_approval|approved|rejected`), `status_reason`, `created_by_user_id`, `rejected_by_user_id`, `rejected_at`, `suspended_by_user_id`, `suspended_at`, `archived_by_user_id`, `archived_at`, and `ended_on`, supporting statuses `pending`, `approved`, `rejected`, `suspended`, and `archived`.
  - Extended `department_memberships` by dropping the single-row unique constraint on `['school_membership_id', 'organizational_unit_id']` (replacing it with a lookup index and transactional active-row validation) and adding `assigned_by_user_id` and `ended_by_user_id` so ended historical department participations are preserved alongside new active participations.
  - Created `membership_decisions` (`id`, `school_membership_id`, `organization_id`, `actor_user_id`, `target_user_id`, `action`, `from_status`, `to_status`, `reason`, `timestamps`).
  - Created `membership_invitations` (`id`, `organization_id`, `school_membership_id`, `token_hash` unique SHA-256 digest, `invited_email`, `invited_name`, `status` in `pending_acceptance|pending_lead_approval|approved|rejected|revoked|expired`, `created_by_user_id`, `expires_at`, `accepted_at`, `accepted_by_user_id`, `revoked_at`, `revoked_by_user_id`, `reviewed_by_user_id`, `reviewed_at`, `review_note`, `timestamps`).

### Deliverable 2 — Local Member Directory & Record Administration (School Lead Scope)
- **Scoped Directory (`/schools/{organization}`):**
  - Added server-scoped search (`search` across `full_name`, `name_en`, `membership_code`, `phone`, and linked/pending `email` in UTF-8), status filter (`all`, `approved`, `pending`, `suspended`, `archived`, `rejected`), department filter (`department_id`), status summary counters (`statusCounts`), and 10-per-page pagination (`withQueryString()`).
- **Local Member Registration (`POST /schools/{organization}/members`):**
  - Allows the assigned Sunday School Lead to register a member with or without a login account (`full_name` required; `name_en`, `phone`, `membership_code`, `joined_on`, `initial_status` in `approved|pending`, `department_ids`, `department_role_label_am`, `service_notes`, `application_note`, and optional `invite_email`).
  - **Local Duplicate Suggestion & Explicit Confirmation (`DATA-005`):** Surfaces existing members in the **same Sunday School** matching the entered `full_name` or `phone` both in the UI and on the server, requiring explicit lead confirmation (`confirm_duplicate_override`) before creating a separate person record and never exposing or auto-merging cross-school people.
- **Member Detail & Lifecycle Administration (`/members/{schoolMembership}`, `PUT /members/{schoolMembership}`, `POST /memberships/{schoolMembership}/status`):**
  - Allows the local Sunday School Lead to edit local member fields (`full_name`, `name_en`, `phone`, `membership_code`, `joined_on`, `service_notes`).
  - Enforces validated lifecycle transitions (`approve`, `reject`, `suspend`, `archive`, `reactivate`) inside `DB::transaction` with `lockForUpdate()`, requiring a `reason` for `reject`, `suspend`, and `archive`, recording every transition in `membership_decisions` and `activity_logs`.
  - Suspending or archiving a membership immediately blocks the member's private access and automatically deactivates any active school-scoped `department_coordinator` appointment for that user in that school (`HierarchyScopeContext` also filters out `department_coordinator` appointments in schools where the user's membership is `suspended`, `archived`, or `rejected`).

### Deliverable 3 — Invitations, Account Provisioning & Local-Lead Account Linking
- Public `/register` remains disabled.
- **School Lead Invitation Issuance & Revocation (`POST /schools/{organization}/invitations`, `POST /invitations/{membershipInvitation}/revoke`):**
  - Generates a 48-character random token, stores only `hash('sha256', $rawToken)` in `membership_invitations.token_hash`, and delivers `SchoolMembershipInvitationMail` to local Mailpit (`127.0.0.1:1025`). Raw tokens are never stored in the database or written to logs.
- **Throttled Invitation Acceptance (`GET /invitations/{token}`, `POST /invitations/{token}/accept` — `throttle:10,1`):**
  - Handles `invalid`, `revoked`, `expired`, and `already_accepted` states without disclosing private member data.
  - **New email path:** Invitee sets their name, preferred language (`am`/`en`), and password to create a `User` account (`is_business_account = true`, `email_verified_at = now()`).
  - **Existing email path:** Requires the visitor to be signed in as that exact email address (never overwriting an existing account's password/email and never disclosing another user's identity); rejects technical operator accounts (`hasTechnicalIdentity()`).
  - **Lead Approval Gate (`MEM-005`):** Accepting an invitation transitions the invitation and membership `account_link_status` to `pending_lead_approval` (`pending_user_id` set, `user_id` unchanged) and places the request in the **Local Lead Review Queue**. Private member access is granted only after the local Sunday School Lead approves the link (`POST /invitations/{membershipInvitation}/approve`).

### Deliverable 4 — Department Membership & Member Self-Service
- **Department Participation & History (`POST /members/{schoolMembership}/departments`, `PATCH /department-memberships/{departmentMembership}`):**
  - Local Sunday School Lead can assign a member to multiple local departments with `role_label_am` and `starts_on`, end a participation (`is_active = false`, `ends_on` set) while preserving its historical row, or reactivate an ended row.
  - Department Coordinator (`coord-hymns-a@example.test`) sees only `approved` members with an active participation in their assigned department, and receives `403` on member registration, member editing, lifecycle transitions, invitation management, and department assignment endpoints.
- **Member Self-Service (`/my-profile`, `PUT /my-profile`):**
  - Approved member (`member-a@example.test`) can view their membership, active department assignments, and decision history, and edit permitted personal fields (`full_name`, `name_en`, `phone`, `service_notes`).
  - Pending, pending-link, rejected, suspended, and archived accounts see only their own status/reason banner and decision history on `/my-profile`, and receive `403` on `PUT /my-profile` and all private school, department, member, and report routes.

### Deliverable 5 — Bilingual (`am` / `en`) Catalogues & Audit Privacy
- Added full Amharic (`lang/am/messages.php`) and English (`lang/en/messages.php`) catalogue parity for all new member registration, directory search/filter, duplicate warning, review queue, lifecycle transition, invitation acceptance, department participation, decision history, and self-service status banners.
- Centralized `ActivityLog::PRIVATE_SCHOOL_EVENTS` and `scopeExcludePrivateSchoolEvents()` across `ActivityLogController`, `ExportCenterController`, `GlobalSearchController`, and `DashboardController` so technical operators cannot view or export private membership, invitation, department-membership, or draft report events.

---

## 3. Files Changed

### Documentation & Requirements
- `docs/requirements/hierarchy-member-access-sharing.md`
- `docs/04-data-model.md`
- `docs/PROJECT_STATUS.md`
- `docs/08-backlog.md`
- `docs/reports/2026-11-14-membership-management-batch.md`

### Database Migration & Seeder
- `database/migrations/2026_11_14_000003_add_membership_lifecycle_and_invitations.php`
- `database/seeders/HierarchyAccessDemoSeeder.php`

### Backend Models, Mail, Support & Controllers
- `app/Models/MembershipDecision.php`
- `app/Models/MembershipInvitation.php`
- `app/Models/SchoolMembership.php`
- `app/Models/DepartmentMembership.php`
- `app/Models/User.php`
- `app/Models/ActivityLog.php`
- `app/Mail/SchoolMembershipInvitationMail.php`
- `app/Support/HierarchyScopeContext.php`
- `app/Http/Controllers/HierarchyDemoController.php`
- `app/Http/Controllers/MembershipInvitationController.php`
- `app/Http/Controllers/ActivityLogController.php`
- `app/Http/Controllers/ExportCenterController.php`
- `app/Http/Controllers/GlobalSearchController.php`
- `app/Http/Controllers/DashboardController.php`
- `routes/web.php`

### Localization & Frontend Vue Pages
- `lang/am/messages.php`
- `lang/en/messages.php`
- `resources/js/pages/schools/Show.vue`
- `resources/js/pages/members/Show.vue`
- `resources/js/pages/members/MyProfile.vue`
- `resources/js/pages/invitations/Show.vue`

---

## 4. Exact Commands Executed & Results

1. **Additive Migration:**
   - Command: `php artisan migrate --no-interaction`
   - Result: Exit `0` (`2026_11_14_000003_add_membership_lifecycle_and_invitations .... 62.91ms DONE`).
2. **Idempotent Local Demo Seeder:**
   - Command: `php artisan db:seed --class=HierarchyAccessDemoSeeder --no-interaction`
   - Result: Exit `0` (`HierarchyAccessDemoSeeder .... 116 ms DONE`). Preserved all 11 existing accounts and passwords in `storage/app/private/demo-credentials.md` and `storage/app/private/demo-credentials.json`, added suspended member fixture `SSU-A-SUSP-004` (`#5`) with ended department history, and seeded initial `MembershipDecision` rows.
3. **PHP Formatting & Lint Check (Laravel Pint):**
   - Command: `./vendor/bin/pint && composer run lint:check`
   - Result: Exit `0` (`{"tool":"pint","result":"passed"}`).
4. **Frontend Formatting & ESLint Check:**
   - Command: `npx prettier --write resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue && npx eslint --fix resources/js/pages/members/Show.vue && npx eslint resources/js/pages/schools/Show.vue resources/js/pages/members/Show.vue resources/js/pages/members/MyProfile.vue resources/js/pages/invitations/Show.vue`
   - Result: Exit `0` (0 errors, 0 warnings).
5. **TypeScript Validation (`vue-tsc`):**
   - Command: `npm run types:check`
   - Result: Exit `0` (`vue-tsc --noEmit` completed with 0 errors).
6. **Production Frontend Bundle Build (`vite build`):**
   - Command: `npm run build`
   - Result: Exit `0` (`3300 modules transformed`, `built in 4.60s`).
7. **Git Whitespace & Diff Hygiene Check:**
   - Command: `git diff --check`
   - Result: Exit `0` (no whitespace errors).
8. **Testing Policy Compliance:**
   - Zero automated test suites or ad hoc CLI functional/policy/password assertion scripts were created or executed.

---

## 5. Seeded Accounts, Credentials Location & Preserved Data

- **Git-ignored local credential files:**
  - `storage/app/private/demo-credentials.md`
  - `storage/app/private/demo-credentials.json`
- **All 11 existing demo accounts and their passwords remain unchanged.**
- **Seeded School A & School B Membership Records:**
  - `#1` (`SSU-A-001` — `ሐና Hanna ደሞ` / `Hanna Demo`, School A, `approved`, linked to `member-a@example.test`, active in Hymns `#3` and Membership `#4`, with `MembershipDecision` history).
  - `#2` (`SSU-A-PEND-002` — `ዮናስ በቀለ (Yonas Bekele)`, School A, `pending`, linked to `pending-a@example.test`).
  - `#3` (`SSU-A-003` — `ማርታ ገብሩ`, School A, `approved` member without a login account, active in Hymns `#3`, ready for invitation/account-link testing).
  - `#4` (`SSU-B-001` — `ሳሙኤል ታደሰ`, School B, `approved` member in School B for cross-school isolation testing).
  - `#5` (`SSU-A-SUSP-004` — `ተስፋዬ መኮንን`, School A, `suspended` member with ended historical participation in Hymns `#3` and two `MembershipDecision` entries).

---

## 6. Allowed vs. Denied Permission Matrix (`TASK-028` Endpoints)

| Persona / Scope | View/Filter Own School Directory (`GET /schools/6`) | Register/Edit Member (`POST /schools/6/members`, `PUT /members/1`) | Lifecycle Transitions (`POST /memberships/{id}/status`) | Issue/Revoke/Approve Invitations (`POST /schools/6/invitations`, `/invitations/{id}/*`) | Manage Department Participations (`POST /members/1/departments`, `PATCH /department-memberships/{id}`) | Self-Service Profile Edit (`PUT /my-profile`) |
|---|---|---|---|---|---|---|
| **Sunday School Lead A (`lead-a@example.test`, School A `#6`)** | **Allowed** (School A `#6` only; School B `#7` directory hidden) | **Allowed** (School A `#6` / `#1`, `#2`, `#3`, `#5`); **Denied (`403`)** on School B (`#7` / `#4`) | **Allowed** (School A memberships); **Denied (`403`)** on School B (`#4`) | **Allowed** (School A); **Denied (`403`)** on School B | **Allowed** (School A); **Denied (`403`)** on School B | N/A (Unlinked officer account) |
| **Sunday School Lead B (`lead-b@example.test`, School B `#7`)** | **Denied (`403`)** on `/schools/6`; **Allowed** on `/schools/7` | **Denied (`403`)** on School A; **Allowed** on School B | **Denied (`403`)** on School A; **Allowed** on School B | **Denied (`403`)** on School A; **Allowed** on School B | **Denied (`403`)** on School A; **Allowed** on School B | N/A |
| **Department Coordinator A (`coord-hymns-a@example.test`, Hymns `#3`)** | **Denied** (School A directory hidden; views active Hymns `#3` members only) | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | N/A |
| **Approved Member A (`member-a@example.test`, `#1`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Allowed** (`PUT /my-profile` updates permitted personal fields only) |
| **Pending / Invited / Suspended / Rejected / Archived Member (`pending-a@example.test` or newly invited user)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** (Read-only status & reason on `/my-profile`) |
| **Union Super Admin (`union-admin@example.test`) & Higher Offices** | **Denied** (Sees aggregate approved count only; raw directory & `/members/*` return `403`) | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | N/A |
| **Technical Operator (`tech-admin@example.test`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** | **Denied (`403`)** (Also denied editing/resetting any member or invited user on `/admin/users/*`) |

---

## 7. Numbered Manual Acceptance Walkthrough

### Prerequisites
- Start the application server if not already running: `php artisan serve` (`http://127.0.0.1:8000`).
- Open local Mailpit web UI (typically `http://127.0.0.1:8025` with SMTP on `127.0.0.1:1025`) to inspect invitation emails.
- Open `storage/app/private/demo-credentials.md` for demo account passwords.
- Recommended order: complete **View-Only & Filter Checks (Steps 1–2)** before performing **Mutating Workflow Checks (Steps 3–9)**.

### Step 1 — View-Only Directory Search, Status Filters, and Suspended Sample Inspection (`lead-a@example.test`)
1. Log in as **Sunday School Lead A** (`lead-a@example.test`) and open `/schools/6`.
2. Verify the **Member Directory** displays status summary counts (`Total: 4 · Approved: 2 · Pending: 1 · Suspended: 1 · Archived: 0 · Rejected: 0`).
3. Use the **Status** filter to select `ለጊዜው የታገደ` (`Suspended`) and click `አጣራ` (`Filter`).
   - **Expected:** Only `ተስፋዬ መኮንን` (`SSU-A-SUSP-004`) is listed.
4. Click `ተስፋዬ መኮንን` (`/members/5`).
   - **Expected:** Shows status `ለጊዜው የታገደ` (`Suspended`), reason `ለ3 ወራት የትምህርት ፈቃድ በመውሰዱ የአገልግሎት ተሳትፎው ለጊዜው ታግዷል።`, an ended historical department participation row (`የመዝሙር ክፍል — የተጠናቀቀ (2026-01-15)`), and two entries in the **Membership & Account-Link Decision History** table (`አባልነት ጸድቋል` and `አባልነት ለጊዜው ታግዷል`).
5. Return to `/schools/6`, click `አጽዳ` (`Reset`), and search `Hanna` or `+251911000101`.
   - **Expected:** Matches `ሐና Hanna ደሞ` (`SSU-A-001`) preserving mixed Amharic/English UTF-8 text.

### Step 2 — Local Duplicate Warning & Registering a Member Without a Login Account (`lead-a@example.test`)
1. Still on `/schools/6` as `lead-a@example.test`, scroll to **Register Local Sunday School Member**.
2. Type `ማርታ ገብሩ (ያለ መለያ የተመዘገበች አባል)` or phone `+251911000103` into the registration form.
   - **Expected:** An amber **Possible Local Match in This Sunday School** alert appears immediately showing the existing local member link (`/members/3`) and a confirmation checkbox. Submitting without checking the confirmation box returns a validation error refusing silent duplication.
3. Clear the form and register a new member without a login account:
   - Full Name: `ቃለአብ ታደሰ (Kaleab Tadesse)`
   - English Name: `Kaleab Tadesse`
   - Phone: `+251911000199`
   - Initial Status: `ወዲያውኑ የጸደቀ` (`Approved Immediately`)
   - Check `የመዝሙር ክፍል` and `የአባላት ጉዳይ ክፍል`
   - Leave **Invitation Email** blank and submit.
   - **Expected:** Redirects to the new member detail page (`/members/{id}`) with `Approved` status, `No login account`, two active department participations, and a `Member Registered / Approved` decision history row.

### Step 3 — Sending an Onboarding Invitation, Revoking, and Re-Issuing (`lead-a@example.test` + Mailpit)
1. Open `/members/3` (`ማርታ ገብሩ` — registered person without a login account).
2. In **Account Onboarding & Link Invitations**, enter `marta-invite@example.test` and click **Send Onboarding Invitation**.
   - **Expected:** Invitation is created with status `pending_acceptance` (`Invitation Sent`), and an email is delivered to local Mailpit (`127.0.0.1:1025`).
3. Click **Revoke Invitation** on that invitation row, then open the acceptance link from the first email in Mailpit.
   - **Expected:** The invitation page (`/invitations/{token}`) displays **Invitation Revoked** and does not allow account creation.
4. Return to `/members/3` as `lead-a@example.test` and send a fresh invitation to `marta-invite@example.test`.

### Step 4 — Accepting the Invitation as a New User & Verifying Pre-Approval Boundaries (`marta-invite@example.test` + `tech-admin@example.test`)
1. Log out of `lead-a@example.test` (or use a private browser window) and open the acceptance URL `/invitations/{token}` from the second Mailpit email.
2. Verify the page displays School A (`አንቀጸ ብርሃን ሰንበት ት/ቤት`), member name (`ማርታ ገብሩ`), and invited email (`marta-invite@example.test`).
3. Choose a password and submit **Accept Invitation & Request Lead Approval**.
   - **Expected:** Logs in as `marta-invite@example.test` and redirects to `/my-profile`, displaying the **Pending Account-Link Approval** banner (`የተቀበሉት የመለያ ትስስር ግብዣ የሰንበት ት/ቤቱን ዋና አስተባባሪ የመጨረሻ ማጽደቂያ በመጠባበቅ ላይ ነው።`).
   - Verify the self-service edit form is hidden and direct navigation to `/schools/6`, `/departments/3`, `/members/3`, or `/reports` returns `403`.
4. **Pre-approval technical takeover check:** Log in as `tech-admin@example.test` and open `/admin/users`.
   - **Expected:** `marta-invite@example.test` is shown as a **Protected Business Identity** (`የተጠበቀ የአገልግሎት መለያ`) even before lead approval; direct `GET /admin/users/{id}/edit` or `PUT /admin/users/{id}` returns `403`.

### Step 5 — Local Lead Approval of the Account Link (`lead-a@example.test`)
1. Log in as `lead-a@example.test` and open `/schools/6`.
2. In **Local Lead Review Queue**, locate `ማርታ ገብሩ` under **Pending Account-Link Requests**, enter a review note, and click **Approve Account Link**.
   - **Expected:** `marta-invite@example.test` is atomically linked to `ማርታ ገብሩ` (`/members/3`) without creating a duplicate `Person` or `SchoolMembership` row, and `account_link_approved` is recorded in decision history.
3. Log back in as `marta-invite@example.test` and open `/my-profile`.
   - **Expected:** Status is now `Approved`, assigned departments are visible, and the self-service profile edit form is active.

### Step 6 — Department Participation Assignment & History (`lead-a@example.test`)
1. Log in as `lead-a@example.test` and open `/members/1` (`ሐና Hanna ደሞ`).
2. In **Service Department Participations & History**, assign her to `የኦዲትና ኢንስፔክሽን ክፍል` (`#5`), then click **End Participation** on that row.
   - **Expected:** The row transitions to `የተጠናቀቀ` (`Ended`) with today's end date and remains visible in history alongside her active participations.

### Step 7 — Lifecycle Suspension, Coordinator Deactivation, Archive & Reactivation (`lead-a@example.test`)
1. As `lead-a@example.test`, open `/members/5` (`ተስፋዬ መኮንን` — currently `Suspended`).
2. In **Membership Lifecycle & Status Transitions**, select `ወደ ማኅደር አዛውር / አጠናቅቅ` (`Archive`), enter reason `ወደ ሌላ ሀገረ ስብከት በመዛወሩ ወደ ማኅደር ተዛውሯል።`, and submit.
   - **Expected:** Status becomes `Archived (Ended)` and a dated `Membership Archived` row is appended to decision history.
3. Next, select `ወደ ጸደቀ አባልነት መልስ (አንቃ)` (`Reactivate to Approved`), enter reason `ተመልሶ አገልግሎት በመጀመሩ ነቅቷል።`, and submit.
   - **Expected:** Status returns to `Approved` and all prior decision history rows remain intact.
4. Optional suspension check on an active member (`#1` `ሐና Hanna ደሞ`): suspending `#1` with a reason immediately blocks `member-a@example.test` from editing `/my-profile` (`403`) and deactivates any active `department_coordinator` appointment for that user in School A; reactivating `#1` restores approved member self-service.

### Step 8 — Cross-School, Coordinator, Union, and Technical Isolation Checks
1. **Department Coordinator (`coord-hymns-a@example.test`):**
   - Open `/departments/3` -> **Allowed** (views active approved Hymns members).
   - Open `/members/1` -> **Allowed** (read-only view; edit, lifecycle, invitation, and department-assignment forms are hidden and `POST`/`PUT`/`PATCH` return `403`).
   - Open `/members/2` (pending applicant not in Hymns) or `/members/5` (if suspended) -> **Denied (`403`)**.
2. **Other School Lead (`lead-b@example.test`):**
   - Open `/schools/6` or `/members/1` -> **Denied (`403`)**.
3. **Union Super Admin (`union-admin@example.test`):**
   - Open `/schools/6` -> Sees school summary and reports, but **Member Directory is hidden**.
   - Open `/members/1` -> **Denied (`403`)**.
4. **Technical Operator (`tech-admin@example.test`):**
   - Open `/activity-logs` -> Verify private events (`memberships.registered`, `memberships.approved`, `memberships.suspended`, `invitations.created`, `invitations.link-approved`, `department-memberships.assigned`, etc.) are excluded.

---

## 8. Remaining Limitations & Deferred Scope

1. **Public Self-Registration & School/Lead Onboarding Policy Open:** Public `/register` remains disabled; school-lead invitation-only onboarding is the conservative working default for `TASK-028`.
2. **Cross-School Transfers Deferred:** Cross-school member transfers (`DEP-005`) require source/destination school coordination rules and are deferred beyond `TASK-028`.
3. **Assigned Forms & Responses (`TASK-011` Remainder):** Member self-service profile editing and `service_notes` are implemented; versioned form definitions/audiences/responses (`MEM-003`) remain planned under `TASK-011`.
4. **Local PostgreSQL Password Parity (`TASK-025` Note):** Local PostgreSQL `127.0.0.1:5432` connects under local trust authentication; `.env` password parity with the PostgreSQL role has not been tested under `scram-sha-256`.

