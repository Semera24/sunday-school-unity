# Boilerplate feature register and reuse assessment

Reviewed: 14 November 2026, Africa/Addis_Ababa.
Reference: `RefernceBoilerPlate/` (folder spelling preserved).
Status: inspected source code; reuse recommendations proposed; no application copied, installed, upgraded, or executed.

**Later checkpoint:** the owner subsequently authorized project setup. The root foundation has now been copied from a fresh remote clone and rebranded; Laravel 13 dependencies have been resolved without installation. The historical audit below describes the reference as reviewed. For current product state, use [PROJECT_STATUS](PROJECT_STATUS.md), not this audit's original status. Inherited behavior findings remain unresolved unless a later task records a fix.

## Recommendation

Create the SundaySchool application at the workspace root from a selective copy of this domain-neutral boilerplate, then upgrade that project copy to Laravel 13. Preserve the reference folder and its Git history unchanged. Reuse the integrated generic features rather than install a fresh starter and manually transplant each one.

This is a foundation with useful code, not an already completed Sunday School MVP. Naming changes alone will not make its global authorization suitable for multiple Sunday School organizations. The owner's comparison with school-management software describes similar registration and CRUD patterns; it does not establish a conventional academic-school feature set.

### Approaches considered

| Approach | Benefit | Cost/risk | Assessment |
|---|---|---|---|
| Project copy of boilerplate, cleanup, upgrade to 13 | Retains connected controllers, requests, policies, routes, migrations, types, navigation, and components | Must adapt global assumptions and resolve upgrades | Recommended |
| Fresh Laravel 13/Vue starter, selected feature ports | Starts with current starter conventions | Reintegrates the same features across many files; dependencies and authorization still require review | Fallback if the dependency resolution or chosen UI direction makes reuse impractical |
| Develop directly inside the reference repository | Minimal initial copying | Mixes product work with reusable master; conflicts with its documented intended use | Do not use |

## Review method and evidence limits

Inspected manifests and lock files; README, master reference, and extraction handoffs; routes; middleware/providers; models/migrations inventory; policies and write requests; user/role controllers; media/import/settings services; search/dashboard/export/notification controllers; representative Vue components, navigation, localization; and CI configuration.

The application contains 69 PHP files under `app/` and 25 Vue pages at review time. There are no installed `vendor/`, `node_modules/`, `.env`, or compiled `public/build/` directories. The reference Git working tree was clean during review.

No dependencies were installed, no application booted, no database accessed, no migrations run, and no automated test suites run. Older handoffs report previous verification; those reports are provenance, not fresh verification of this checkout or Laravel 13 compatibility.

“Code present” below means implementation paths were inspected. It does not mean current runtime behavior or manual acceptance was established.

## Recorded baseline

| Component | Declared constraint | Locked version / observation |
|---|---|---|
| PHP | `^8.2` | Misleading for full dependency set: Spatie Permission requires PHP 8.4; locked Symfony Filesystem requires >=8.4.1. README expects 8.5+ |
| Laravel | `^12.0` | 12.54.1 |
| Inertia Laravel | `^2.0` | 2.0.22 |
| Inertia Vue | `^2.3.7` | 2.3.18 |
| Vue | `^3.5.13` | 3.5.30 |
| TypeScript | `^5.2.2` | 5.9.3 |
| Vite | `^7.0.4` | 7.3.1 |
| Spatie Permission | `^7.0` | 7.2.3 |
| Fortify | `^1.30` | 1.36.1 |
| Tinker | `^2.10.1` | 2.11.1; locked requirements exclude Illuminate 13 |
| Database | PostgreSQL sample configuration | No live database inspected |
| Tooling | Node 22 in README/CI | No runtime compatibility validation performed |

Source at reviewed commit: [composer.json](https://github.com/Semera24/laravel-production-boilerplate-v2/blob/eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5/composer.json), [composer.lock](https://github.com/Semera24/laravel-production-boilerplate-v2/blob/eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5/composer.lock), [package.json](https://github.com/Semera24/laravel-production-boilerplate-v2/blob/eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5/package.json), [package-lock.json](https://github.com/Semera24/laravel-production-boilerplate-v2/blob/eec1ad2b4b44e999b9c0e57ba3bc69fe3a352ee5/package-lock.json). The reference folder was present during the original audit but absent at the later collaboration checkpoint; use this pinned provenance if it remains unavailable locally.

## Feature register

Disposition applies to the future product copy, not the preserved reference.

| ID | Feature | Observed implementation and evidence under reference root | Proposed disposition |
|---|---|---|---|
| BP-001 | Login/logout, password reset and email verification | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `resources/js/pages/auth/` | Reuse; establish staff provisioning and email/username policy |
| BP-002 | Two-factor authentication | Fortify config, security controller, challenge/setup/recovery components | Reuse; define requirements for privileged staff |
| BP-003 | Open self-registration | Fortify registration enabled; `app/Actions/Fortify/CreateNewUser.php` creates account without organization/approval | Disable public registration initially unless an approved onboarding workflow requires it |
| BP-004 | Account settings | Profile, password, locale, appearance, profile deletion | Reuse settings; replace unrestricted account deletion with agreed deactivation/retention workflow |
| BP-005 | Role/permission infrastructure | Spatie Permission, permission middleware, policies, five generic roles | Reuse library/conventions; redesign assignments and scopes |
| BP-006 | Super Admin bypass | `app/Providers/AppServiceProvider.php` globally grants Gate abilities to named role | Redesign deliberately; distinguish technical recovery from program administration |
| BP-007 | User administration | Create/edit/delete, role assignment, filtering, pagination; `Admin/UserManagementController.php` and Vue pages | Reuse interface patterns; add organizational access and role-grant restrictions |
| BP-008 | Role administration | Create/edit, grouped permissions, protected generic role names; `Admin/RoleManagementController.php` | Adapt; generic protected names are not church roles |
| BP-009 | Dashboard | Counts of users, roles, media, audit activity; empty `queues` array | Reuse layout/cards; replace metrics with scoped domain measures; remove empty placeholder |
| BP-010 | Global search | Users, roles, personal notifications, audit events; `GlobalSearchController.php` | Adapt; domain searches and organizational filtering are absent |
| BP-011 | In-app notifications | Database notifications, bell/preview, pagination/read status; `SystemMessageNotification.php`, `NotificationController.php` | Reuse; future announcement targeting requires recipient rules |
| BP-012 | Activity/audit records | Custom `ActivityLogger`, actor, polymorphic subject, properties, IP; listing/detail pages | Reuse with school/org attribution and visibility; logs are explicitly recorded, not automatic history for every model |
| BP-013 | System settings | Registry, database values, settings UI, shared branding; `SettingRegistry.php`, `SettingStore.php` | Reuse application settings; model each church/school as entities, not global key/value settings |
| BP-014 | Private media library | Upload/list/download/delete, private disk enforcement, hashing, attachment hook; media controller/model/services | Reuse with school/document visibility rules and approved upload types |
| BP-015 | Media preview/thumbnails | PDF/JPEG/PNG/WebP preview with size/type/hash checks; optional GD thumbnails | Reuse after environment/manual checks; uploads have broader rules than preview |
| BP-016 | S3-compatible storage | Flysystem adapter and filesystem configuration | Foundation present; select private bucket/disk and verify actual hosting integration |
| BP-017 | CSV/XLSX user export | `ExportCenterController.php`; exports accounts and roles | Reuse tools; build scoped student/report exports and manage large-data memory use |
| BP-018 | XML user export | Same export controller and ArrayToXml | Omit from MVP unless an integration requires XML; remove dedicated dependency only if unused elsewhere |
| BP-019 | Print/PDF workspace summary | Vue print page and Blade/DomPDF summary | Reuse technique; replace generic content; verify Ethiopic font rendering manually |
| BP-020 | CSV import engine | `Support/Import/CsvImportEngine.php`, preview/error objects, `import_runs` | Partial: no route/UI/domain mapper; add school authorization, failure policy, duplicate handling and commit safety |
| BP-021 | API baseline | `/api/v1`, Sanctum tokens, current user, notifications/media/admin/audit resources | Defer exposure; browser Inertia application does not require these token endpoints |
| BP-022 | Localization infrastructure | Locale middleware, user locale, shared translations, `useT()` | Reuse foundation; originally English only. Reviewer added Amharic defaults/catalogues and core entry labels; many inherited page/controller strings remain English |
| BP-023 | Application shell and reusable UI | Sidebar/layouts, tables, toolbars, forms, pagination, dialogs, loading/empty/error states | Reuse; adapt branding/navigation, accessibility and mobile workflows |
| BP-024 | Typed route generation | Wayfinder PHP package and Vite plugin | Reuse; regenerate bindings as routes change |
| BP-025 | Queue/cache/session foundation | Database migrations/configuration, worker development command | Reuse; no domain background jobs or production process management supplied |
| BP-026 | Optional SSR | SSR entry and `dev:ssr`/build scripts | Leave disabled initially; no runtime SSR server required for the proposed authenticated UI |
| BP-027 | Automated testing infrastructure | `tests/`, Pest/Mockery dependencies, Composer scripts, GitHub test workflow, inherited agent instructions | Exclude from product copy in accordance with owner's manual-testing policy |
| BP-028 | Formatting/type/build tooling | Pint, ESLint, Prettier, vue-tsc, Vite | Keep proportionate build/static tooling; remove test-suite calls from combined commands/CI |

## Sunday School domain records not yet present

The inspected boilerplate provides generic accounts, roles, media, settings, and related administrative tools. It does not yet contain Sunday School organization records or a separate register of students/disciples/members. These domain records would be added after requirements are agreed; a registered disciple need not have an application account.

Owner clarification: “school management” was an analogy for registration, CRUD and administration. Primary operators are senior coordinators/appointed administrators who guide members, with coordinators in local departments. Each Sunday School manages its own departments and reports upward. Do not treat classes, subjects, examinations, marks, academic promotion, guardian portals, or separate teacher accounts as approved requirements. See [department coordination requirements](requirements/department-coordination.md) for the current direction and proposed workflows.

Curriculum, attendance/participation, transfers, and assemblies have appeared in the discussion or source research. Their precise workflows and MVP inclusion remain proposals, not conclusions from this boilerplate audit. No conventional school-management module should be added merely because it was listed in earlier research.

There is no completed Amharic UI, Ethiopian-calendar support, offline attendance synchronization, SMS/Telegram delivery, school-scoped access, or end-user CSV import screen. Database notifications do not establish a general messaging or broadcast system.

## Material adaptation findings

### 1. Global access must become scoped access

`config/permission.php` has `teams => false`. User and media policies check capability names without resource organization membership. User/media/audit/search/export queries have no school context. The `User` model has no organization association. This is suitable evidence of a generic single-workspace foundation, not completed multi-school isolation.

Choose an explicit scoped-assignment design before domain migrations. Enabling Spatie teams alone would not implement hierarchy traversal or protect all queries, route bindings, exports, documents, and reports.

### 2. Role assignment currently allows more authority than ordinary school staff should have

`StoreUserRequest` accepts any existing role name for callers with `users.create`; `UpdateUserRolesRequest` similarly relies on `users.update`. The seeded Admin has both permissions. Role options include all roles; the controller calls `syncRoles()` without a grantable-role restriction. Static inspection therefore identifies a path for an Admin to assign the Super Admin role. Do not carry this into school administration.

Separate permission to edit identity details from permission to grant roles. Limit both target people and assignable roles to the actor's authorized scope. Also preserve recovery access during deletion, deactivation, and role changes. The existing self-role-change guard is tied to retaining Super Admin even when the editor is not a Super Admin; redesign that condition rather than copying it unchanged.

### 3. API authentication is not equivalent to Fortify web authentication

`LoginApiRequest::authenticate()` uses `Auth::attempt()` and `AuthTokenController` immediately creates a token. No second-factor step or verified-email check is visible in that flow. The API routes do not apply the web `verified` middleware. No explicit login throttle is attached to the API login route, while Fortify web login has named limiters. Sanctum token expiry is null and token creation supplies no restricted ability list.

Recommendation: do not expose the token API in the MVP until its authentication and authorization requirements are defined. This is a source-level finding, not an executed authentication exploit or a complete security audit.

### 4. Import is reusable infrastructure with incomplete failure handling

The CSV engine accumulates valid rows in memory. `commit()` invokes persisters one by one, then creates the audit record. It supplies no transaction or failed-run recording of its own. A later row throwing can leave earlier rows persisted without an `import_runs` outcome, unless the caller supplies an appropriate transaction/failure wrapper. Header mismatch handling exists, but row association pads/truncates columns and requires domain-specific review.

Define all-or-nothing versus partial import, retry behavior, duplicate detection, file/row limits, scope and failure recording before exposing student imports.

### 5. Export and translation completeness must not be overstated

User export builds all rows in memory even for streamed output. Workspace summary includes user details and audit events behind `exports.view`, rather than individually checking all the permissions needed for its included content. Adapt query scope and disclosure rules before exposing these reports to school staff.

At source audit only English was registered. Current reviewer changes register Amharic/English and translate core entry points; this is not complete bilingual coverage. See [language/data policy](requirements/language-and-data.md). PDF/print output needs appropriate Ethiopic fonts and manual review.

### 6. Settings, audit and files need domain-aware ownership

Global settings represent a single application/organization profile; they cannot serve as the church registry. Media and audit records require ownership/access scope beyond generic permissions. Upload validation currently enforces file/size but no domain-specific MIME allowlist. The polymorphic attachment hook provides flexibility but does not itself enforce relational ownership or content-publication workflows.

## Laravel 13 upgrade assessment

The locked metadata for Inertia Laravel 2.0.22, Fortify 1.36.1, Wayfinder 0.1.14, Spatie Permission 7.2.3, DomPDF integration 3.1.2 and Simple Excel 3.10.0 explicitly permits Laravel/Illuminate 13. That is encouraging package evidence, not proof that the entire dependency graph resolves or that custom code runs correctly.

Tinker 2.11.1 is a concrete dependency change: its locked Illuminate requirements stop at 12. Laravel's [upgrade guide](https://laravel.com/framework/docs/13.x/upgrade) specifies Tinker `^3.0` and framework `^13.0`. Review all production/dev constraints and regenerate the lock file for the new application. Do not blindly reproduce the guide's testing-package changes because automated tests are excluded here.

Use PHP 8.5 as the provisional runtime target, matching the reference README/CI, subject to hosting and full dependency resolution. At minimum, the present locked production graph already needs PHP 8.4.1; Laravel 13's own PHP 8.3 floor is not sufficient for this exact dependency set.

Keep Inertia 2 for the initial framework upgrade unless compatibility requires otherwise. A Laravel major upgrade does not require also changing the Inertia major. Updating to current starter-kit conventions is a separate decision; avoid combining unrelated migrations without a benefit.

Review request-forgery middleware references (including Sanctum config), cache serialization, session/cache naming and any custom framework hooks against the upgrade guide. No package installation or dependency resolver run has been performed yet.

## Copy and cleanup inventory

| Item | Future product action |
|---|---|
| Application source, selected routes/config, migrations, Vue components | Copy as a coherent foundation, then adapt using feature IDs above |
| `RefernceBoilerPlate/.git` | Do not copy; product needs independent repository identity |
| Existing root `AGENTS.md` and research/manual-acceptance documents | Preserve; merge relevant code conventions without replacing owner's instructions |
| Nested boilerplate `AGENTS.md` and generated Boost guidance | Do not copy unchanged: version data and enforced testing conflict with this project's direction |
| `tests/`, `phpunit.xml`, test CI and test-only dependencies/autoload/scripts | Omit from product copy; remove combined command references |
| Build/static-check workflow | Adapt paths/runtime as needed; exclude functional test execution |
| `.env`, application keys, database files, logs/caches, uploads, dependencies/build output | Never transfer runtime state; configure a new key and a dedicated project database |
| Composer/npm identity, logos, settings defaults, example text | Replace after working product name is agreed |
| Generic roles/persona seeds | Replace with scoped product roles and controlled local demo data; review seeders before running |
| Extraction provenance docs | Keep in reference; do not present as SundaySchool implementation history |
| Master technical reference | Contains an inline sample application key; do not copy credentials/key examples into product docs or use them for the new app |
| Unneeded XML/API/SSR surfaces | Omit/defer their entry points and dependencies where unused; record removals by feature ID |
| `composer.lock`, `package-lock.json` | Carry only through deliberate dependency changes and reproducible regeneration, not ad hoc edits |

The Composer post-update hook runs `boost:update`; verify that generated guidance does not reinstate test requirements or overwrite project-specific policy after dependency updates. Review the setup script before use: it runs installs, migrations, seeding and builds, so it is not merely an environment checker.

## Suggested sequence after requirements and reuse direction are agreed

1. Record product scope, actor/permission rules and baseline decisions; retain this register as the inventory.
2. Create the product copy at SundaySchool root, preserving existing root documents and the reference directory.
3. Apply copy exclusions, manual-testing policy, product identity, dependency cleanup, new key and separate database configuration.
4. Upgrade to Laravel 13 and resolve dependencies with a consistent PHP target. Keep Inertia-major changes separate.
5. Perform proportionate build/type/syntax checks and hand the owner concise manual checks for retained features.
6. Implement organization/person/access foundations before exposing reused administrative modules across schools.
7. Extend domain features against approved requirements and update each feature's implementation/manual-acceptance status.

No runtime acceptance has occurred. No automated testing work is proposed.
