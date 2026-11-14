# Implementation report — TASK-024

Date/time (Africa/Addis_Ababa): 2026-11-14 02:44 EAT
Implementer: Antigravity (Gemini)
Task brief / linked requirements: [TASK-024](../tasks/TASK-024-hierarchy-access-demo.md), CORE-001–007, CORE-009, CORE-011, HIER-001–005, MEM-001, MEM-004–006, DEP-001–005, REP-003, REP-010–011, NFR-002
Baseline commit, or “no committed baseline”: `5c85b29` (`main`, plus preserved uncommitted reviewer TASK-023/TASK-025 edits)
Implementation status: Complete (In review)
Reviewer status: Pending
Owner manual acceptance: Pending

## Delivered behavior

- Prepared the local runtime (`TASK-006` slice) by installing locked Composer and npm dependencies without invoking destructive setup scripts, verifying `sunday_school_unity` PostgreSQL connectivity (`UTF8` server/client encoding), and running pending additive migrations.
- Implemented the bounded hierarchy and access demonstration (`TASK-007`/`008`/`009` subset):
  - Coordinating hierarchy (`organizations`: Union -> Dioceses -> Districts -> Sunday Schools) with cycle/parent-type validation and separate church/parish and city context columns.
  - Internal units (`organizational_units`: Union Governing Board, Union Planning & Reporting Division, and local Sunday School departments).
  - Distinct human identity (`people`), school membership/account link (`school_memberships` with local lead approval), multi-department participation (`department_memberships`), dated scoped appointments (`scoped_appointments`), and report fixtures (`demo_reports`).
  - Session-backed active scope resolution (`HierarchyScopeContext`) supporting safe appointment switching (`lead-a@example.test` holds both School A Lead and District A1 Representative appointments; switching replaces rather than combines privileges).
- Closed/isolated inherited global hazards:
  - Removed the global `Gate::before` Super Admin bypass in `AppServiceProvider.php`.
  - Constrained assignable roles via `SystemRole::assignableRoleNamesFor()` in `StoreUserRequest`, `UpdateUserRolesRequest`, `UserPolicy`, and `UserManagementController`.
  - Scoped `ExportCenterController::buildSummary()` by `users.view` and `activity-logs.view`.
  - Blocked uninvited public account creation in `CreateNewUser.php` (BP-003) and disabled `/api/v1/*` token endpoints with HTTP `403` (BP-021).
- Seeded fictional Amharic-first and mixed-language sample data via idempotent `HierarchyAccessDemoSeeder` and wrote local demo login credentials to Git-ignored `storage/app/private/demo-credentials.md`.
- **Union Super Administrator Extension (`union-admin@example.test`):**
  - Added `ScopedAppointment::ROLE_UNION_SUPER_ADMIN` (`union_super_admin`) with model-level scope and effective-date validation (`assertValidScopeAndDates()`).
  - Added stable permission codes (`HierarchyScopeContext::permissionCodes()`) and capability checks (`hasCapability()`) across all personas.
  - Enabled nationwide organization create/update (`POST /hierarchy/organizations`, `PUT /hierarchy/organizations/{organization}`) and local Sunday School lead appointment management (`POST /hierarchy/appointments`, `PATCH /appointments/{scopedAppointment}`) for `union_super_admin`, plus local department-coordinator appointment management (`POST /schools/{organization}/appointments`) for `school_lead`, all audited via `ActivityLogger::record`.
  - Enforced strict privacy boundaries: `union_super_admin` sees nationwide hierarchy, released reports, reporting completeness, and national summaries, but is denied (`403`) private member directories, `/members/{id}`, membership approvals, and unreleased local drafts (`draft` / `submitted_local`).
  - Added all new UI labels and flash messages to `lang/am/messages.php` (Amharic default) and `lang/en/messages.php` (English secondary) with zero hard-coded bilingual labels for new functionality.

## Changed files

| File | Change and purpose |
|---|---|
| `database/migrations/2026_11_14_000002_create_hierarchy_access_demo_tables.php` | Additive migration creating `organizations`, `organizational_units`, `people`, `school_memberships`, `department_memberships`, `scoped_appointments`, and `demo_reports` |
| `app/Models/Organization.php` | Organization hierarchy model with ancestry/subtree helpers and cycle/parent-type validation |
| `app/Models/OrganizationalUnit.php` | Internal unit model (`department`, `division`, `board`) |
| `app/Models/Person.php` | Human identity model independent of login accounts |
| `app/Models/SchoolMembership.php` | Person-to-school membership and account-link model (`pending`, `approved`, `rejected`) |
| `app/Models/DepartmentMembership.php` | Dated participation of a school member in local departments |
| `app/Models/ScopedAppointment.php` | Dated scoped role/capability assignment model |
| `app/Models/DemoReport.php` | Minimal report fixture model (`draft`, `submitted_local`, `released`) |
| `app/Models/User.php` | Added `scopedAppointments()` and `schoolMemberships()` relationships; preserved reviewer `booted()` locale hook |
| `app/Support/HierarchyScopeContext.php` | Server-side active scope resolver and authorization boundary checks |
| `app/Http/Controllers/HierarchyDemoController.php` | Scoped controllers for scope switching, hierarchy, schools, departments, members, approvals, reports, and self-service profile |
| `app/Http/Controllers/DashboardController.php` | Added active scope summary, allowed/denied matrix, and jurisdiction school links to dashboard |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared active `scope` payload and domain navigation capability flags; preserved reviewer translation merge |
| `app/Providers/AppServiceProvider.php` | Removed global `Gate::before` Super Admin bypass |
| `app/Support/SystemRole.php` | Added `assignableRoleNamesFor()` role-grant boundary helper |
| `app/Policies/UserPolicy.php` | Required `roles.update` in `updateRoles()` |
| `app/Http/Requests/Admin/StoreUserRequest.php` | Restricted assignable roles to `SystemRole::assignableRoleNamesFor()` |
| `app/Http/Requests/Admin/UpdateUserRolesRequest.php` | Required `roles.update` and restricted assignable roles |
| `app/Http/Controllers/Admin/UserManagementController.php` | Filtered role options and guarded Super Admin role modification |
| `app/Http/Controllers/ExportCenterController.php` | Checked `users.view`, `roles.view`, and `activity-logs.view` inside `buildSummary()` |
| `app/Actions/Fortify/CreateNewUser.php` | Blocked open public self-registration while keeping route definition for Wayfinder |
| `routes/api.php` | Disabled `/api/v1/*` token endpoints with HTTP `403` during this milestone |
| `routes/web.php` | Registered scoped hierarchy, school, department, member, report, and self-service profile routes |
| `database/seeders/HierarchyAccessDemoSeeder.php` | Idempotent local demo seeder writing credentials to Git-ignored `storage/app/private/demo-credentials.md` |
| `database/seeders/DatabaseSeeder.php` | Delegated local seeding to `HierarchyAccessDemoSeeder` instead of creating global demo admins |
| `lang/am/messages.php`, `lang/en/messages.php` | Added `nav.hierarchy`, `nav.reports`, and `nav.my_profile` keys; preserved reviewer translations |
| `resources/js/types/auth.ts`, `resources/js/navigation/app.ts` | Wired domain navigation items (`Hierarchy Directory`, `Reports`, `My Member Profile`) |
| `resources/js/pages/Dashboard.vue` | Scope banner, safe scope switcher, allowed/denied summary, and jurisdiction school cards |
| `resources/js/pages/hierarchy/Index.vue` | Top-to-bottom hierarchy view with separate church/city badges and scoped subordinate registration |
| `resources/js/pages/schools/Show.vue` | Scoped Sunday School workspace, department creation, member directory, and lead approval/release actions |
| `resources/js/pages/departments/Show.vue` | Scoped department workspace, multi-department member list, and draft submission |
| `resources/js/pages/members/Show.vue` | Scoped member detail and local lead approval action |
| `resources/js/pages/members/MyProfile.vue` | Member self-service profile editor (UTF-8 mixed-text safe) and pending application status view |
| `resources/js/pages/reports/Index.vue`, `Show.vue` | Scoped report list/detail and upper-office reporting completeness table (`missing_submission` vs `submitted_released`) |
| `docs/04-data-model.md`, `docs/08-backlog.md`, `docs/PROJECT_STATUS.md`, `docs/tasks/TASK-024-hierarchy-access-demo.md` | Recorded schema assumptions, checkpoint evidence, and `In review` task status |

Preserved unrelated reviewer changes in `config/app.php`, `config/database.php`, `app/Support/Locales.php`, `resources/css/app.css`, `resources/js/composables/useT.ts`, `resources/js/layouts/settings/Layout.vue`, `resources/js/pages/auth/Login.vue`, `resources/js/pages/settings/Profile.vue`, and `database/migrations/2026_11_14_000001_default_new_user_locale_to_amharic.php`.

## Decisions and scope

- Followed all settled owner rules: manual testing only; local Sunday School lead approves memberships/account links and upward report release; higher offices see only released reports in their jurisdiction; person identity is separate from login accounts; Amharic (`am`) is default and English (`en`) secondary.
- Marked schema and conservative demo role matrix as `Proposed` for production governance; open production questions (parish-to-school cardinality, skipped hierarchy levels, initial school/lead onboarding, and exceptional upper-office intervention) remain deferred.

## Commands and evidence

| Command actually run | Result / exit code | What it establishes |
|---|---|---|
| `composer install --no-interaction && npm ci` | Exit 0 (130 Composer packages, 406 npm packages installed) | Locked dependencies installed without running convenience migrate/seed scripts |
| `php -r '...SHOW server_encoding; SHOW client_encoding...'` | Exit 0 (`DB: sunday_school_unity`, `UTF8` / `UTF8`) | Connected to dedicated local PostgreSQL DB with UTF-8 encoding |
| `git check-ignore -v storage/app/private/demo-credentials.md storage/app/private/demo-credentials.json` | Exit 0 (`storage/app/private/.gitignore:1:*`) | Local credential files are ignored by Git before writing |
| `php artisan migrate --no-interaction` | Exit 0 (16 migrations run) | Infrastructure, locale-default, and TASK-024 demo tables created on `sunday_school_unity` |
| `php artisan db:seed --class=HierarchyAccessDemoSeeder --no-interaction` (run twice) | Exit 0 on both runs | Fictional hierarchy, 10 personas, and report fixtures seeded idempotently; passwords preserved on rerun |
| `composer run lint:check` (`pint --parallel --test`) | Exit 0 (`{"tool":"pint","result":"passed"}`) | PHP code formatting conforms to project Pint rules |
| `npm run types:check` (`vue-tsc --noEmit`) | Exit 0 | TypeScript types across Vue pages and Wayfinder routes compile cleanly |
| `npx eslint ...` (all 10 TASK-024 TS/Vue files) | Exit 0 | Zero ESLint errors in TASK-024 frontend files |
| `npm run build` (`vite build`) | Exit 0 (`built in 3.32s`) | Production frontend bundle and Wayfinder route bindings compiled |

Explicitly: **no automated test suites were written, installed, or run.**

## Manual verification for owner

- **Credentials file (Git-ignored):** `storage/app/private/demo-credentials.md`
- **Start local server:** `php artisan serve --host=127.0.0.1 --port=8000`
- **Local URL:** `http://127.0.0.1:8000/login`
- **Stop local server:** press `Ctrl+C` in the terminal running `php artisan serve`.

| Step | Action | Expected result |
|---|---|---|
| 0 | Sign in as **Union Super Administrator** (`union-admin@example.test`) | Sees nationwide hierarchy (`/hierarchy`), national summary on `/dashboard` and `/reports`, can create/update organizations (`POST /hierarchy/organizations`, `PUT /hierarchy/organizations/{id}`) and manage local-lead appointments (`POST /hierarchy/appointments`, `PATCH /appointments/{id}`). Opening `/schools/6` shows school summary without private member directory. Direct URLs `/members/1`, `/members/2`, `POST /memberships/2/approve`, `/reports/2` (draft), `/reports/3` (submitted_local), `/reports/5` (draft), and `/admin/users` return `403` |
| 1 | Open `http://127.0.0.1:8000/login` signed out | Amharic login labels render with Noto Sans Ethiopic; signing in with each persona from `storage/app/private/demo-credentials.md` lands on `/dashboard` |
| 2 | Sign in as **Local Lead A** (`lead-a@example.test`) | Can open `/schools/6` (School A), manage departments and department-coordinator appointments (`POST /schools/6/appointments`), view pending member `#2` (`ዮናስ በቀለ`), approve membership, view draft/submitted reports (`#2`, `#3`), and release Report `#3`. Direct URL `/schools/7` (School B), `/schools/8` (School C), `/members/4`, `PUT /hierarchy/organizations/6`, `POST /hierarchy/appointments`, or `/admin/users` returns `403` |
| 3 | As **Local Lead A**, use the **Safe Scope Switcher** on `/dashboard` to switch to 2nd appointment (**District A1 Representative**) | Active scope changes to District A1. Now `/schools/7` (School B summary) is visible, while School A's unapproved draft `/reports/2` and `/reports/3` return `403` (proving scope switching never combines privileges) |
| 4 | Sign in as **Department Coordinator A** (`coord-hymns-a@example.test`) | Can open `/departments/3` (School A Hymn Dept) and submit draft `/reports/2` to Local Lead A. Direct URL `/departments/4` (Membership Dept), `/departments/5` (Audit Dept), `/schools/7`, or `POST /reports/2/release` returns `403` |
| 5 | Sign in as **Approved Member A** (`member-a@example.test`) | Opens `/my-profile`, sees approved status and dual department memberships (`የመዝሙር ክፍል` and `የአባላት ጉዳይ ክፍል`). Save mixed text `ሐና Hanna ደሞ — 2019 ዓ.ም.` and switch interface language between `አማርኛ` and `English` in `/settings/profile`: stored name/notes remain verbatim. Direct URLs `/hierarchy`, `/schools/6`, `/members/2`, `/reports` return `403` |
| 6 | Sign in as **Pending Account** (`pending-a@example.test`) | Sees pending status on `/dashboard` and `/my-profile`. Direct URLs `/hierarchy`, `/schools/6`, `/departments/3`, `/reports`, and `PUT /my-profile` return `403` |
| 7 | Sign in as **District Officer** (`district-aa-north@example.test`) and **Diocese Officer** (`diocese-aa@example.test`) | On `/reports`, see released reports in District A1 / Diocese 1 (`#1`, `#4`) and completeness table showing School B as `ያልተለቀቀ / የሚጎድል (Missing — Not Zero)`. Direct URL to draft `/reports/2`, School B draft `/reports/5`, or Diocese 2 `/schools/8` / `/reports/6` returns `403` |
| 8 | Sign in as **Union Division Coordinator** (`union-reporting@example.test`) | Sees released reports in `hymns`/`membership`/`development` (`#1`, `#6`) across the Union, but direct URL to released Audit report `/reports/4` or draft `/reports/2` returns `403` |
| 9 | Sign in as **Union Board Member** (`union-board@example.test`) | Defaults to saved `en` locale; sees released Union report summaries (`#1`, `#4`, `#6`), but direct URL to local drafts (`/reports/2`), member records (`/members/1`), or `/admin/users` returns `403` |
| 10 | Sign in as **Technical Operator** (`tech-admin@example.test`) | Can access `/admin/users`, `/admin/roles`, `/admin/settings`, `/activity-logs` (and cannot grant `Super Admin`). Direct URLs to private domain records (`/schools/6`, `/departments/3`, `/members/1`, `/reports/2`) return `403` |

## Remaining issues / limitations

- Local PostgreSQL connection succeeds under localhost trust (`127.0.0.1:5432`); password authentication parity after the owner's `.env` password edit remains unverified as noted in TASK-025.
- Full membership onboarding/claim workflows, configurable form definitions/responses, department activity tracking, and multi-revision report authoring/return workflows remain planned for TASK-010 through TASK-015.
- Three pre-existing ESLint formatting warnings/errors exist in reviewer-edited files (`resources/js/composables/useT.ts` and `resources/js/layouts/settings/Layout.vue`); those files were left untouched to preserve reviewer changes.

## Handoff

- Backlog/checkpoint entries updated: `docs/08-backlog.md`, `docs/PROJECT_STATUS.md`, `docs/04-data-model.md`, `docs/tasks/TASK-024-hierarchy-access-demo.md`.
- Reviewer attention requested: verify backend scope enforcement in `HierarchyScopeContext.php` and `HierarchyDemoController.php`, role-grant restrictions in `SystemRole.php`, and removal of `Gate::before` in `AppServiceProvider.php`.
- Suggested next task (proposal only): after reviewer assessment and owner manual acceptance of TASK-024, proceed to **TASK-007 / TASK-010** (settle remaining production onboarding/field decisions and implement full membership registration and account-linking workflows).

Stop here pending review. Do not automatically start another task.

