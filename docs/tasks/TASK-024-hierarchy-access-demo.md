# TASK-024 — First Antigravity domain milestone: hierarchy and access demonstration

Status: In review (Owner manual acceptance: Pending). See [TASK-024 implementation report](../reports/2026-11-14-task-024-implementation-report.md). Owner explicitly requested top-to-bottom hierarchy, sample accounts/passwords and manual privilege review as the next task. The permission matrix below is a conservative demo baseline, not confirmation of every production governance rule.

Read AI_START_HERE, PROJECT_STATUS, backlog, permissions/data-model documents and language/data requirements. No automated tests or test tooling. Use fictional local sample data only. Preserve current reviewer edits; do not reset or overwrite them. Implement only this milestone, report, then stop for reviewer/owner acceptance before other modules.

## Active assignment and coordination

- VS Code Antigravity owns implementation of this milestone and its related runtime setup. The terminal cleanup agent is limited to TASK-026 read-only audit/report; no simultaneous cleanup writes, deletions, refactors or formatting. If that agent is already editing files, pause overlapping edits until it finishes and review its diff first.
- Begin with a short plan listing scope/schema assumptions, files and role matrix, then proceed with the minimal demonstrable implementation. Do not require another approval for routine implementation choices already covered here. Flag a material unresolved governance decision rather than inventing additional authority.
- Existing dedicated local PostgreSQL database/role `sunday_school_unity` is configured and owner reports TablePlus connection success. Read local environment privately as needed; never print credentials or replace existing nonempty app keys. Do not recreate the database or alter other local databases/roles/cluster authentication.
- User changed the local environment password after setup. Successful connection under localhost trust does not establish password parity. Report this limitation; do not silently reset credentials or cluster authentication while implementing hierarchy.
- Install locked dependencies after inspecting scripts. Build/type/syntax checks are allowed; automated test suites/tooling are not. No deployment, commits or pushes under this brief.
- Before migrations, inspect actual project schema and migration status; only additive pending project migrations are allowed. Never `migrate:fresh`, reset/wipe the DB or invoke generic bootstrap seeders that install global demo authority. Run only the explicit local hierarchy-demo seeder once implementation is ready.

## Outcome

The owner can sign in as each sample actor and manually inspect their jurisdiction, allowed actions, and denied access to other schools. A hidden menu alone is insufficient: direct URLs, requests, search, exports, media and inherited global admin/API routes must not bypass scope.

## Suggested slices

1. Inspect current diff and locked scripts; safely install dependencies and prepare dedicated local PostgreSQL runtime (TASK-006). Never use convenience setup that migrates/seeds blindly. Reuse the confirmed project DB and verify UTF8/client encoding and migration state. Document the minimal hierarchy/access design before adding migrations. Do not use other existing databases or generic scaffold seed data.
2. Implement minimal organizations, department/division/board representation and scoped appointments (TASK-007/008/009 subset). Separate login, member record, appointment and permission. Use database foreign keys, validation and authorization. Explain how scope follows ancestry and function; prevent cycles/invalid parents and unsafe role grants. Do not merely rename inherited global roles.
3. Add development-only idempotent sample seeder and simple scope/account demonstration pages; repeat seeding must not destroy owner data. Use clearly fictional Amharic-first names with some English/mixed samples. Seed only on explicit local/demo invocation, never as automatic production boot behavior. Generate usable email-login usernames and local demo passwords for each persona. Save the account/role/scope/password table to an explicitly Git-ignored local file and provide its location to the owner; verify it is ignored before writing credentials. Store only password hashes in the database. Do not reuse the DB password for demo login accounts or commit passwords/secrets. On rerun, preserve existing passwords unless reset was explicitly requested. No real personal information.

## Proposed sample structure (fictional, not an asserted church directory)

- One union; two dioceses; at least one district per diocese.
- Schools A and B belong to different churches in the same city/district. School C belongs to the other diocese. City is geography, not an authorization parent.
- At least two departments in School A; an upper functional division and a board assignment demonstrate function scope separate from geographic rank.
- Fake member/report records can be minimal fixtures solely for the access demonstration. Do not implement full registration, forms or reporting modules in this milestone. Report fixtures should include local draft and locally released examples if necessary to demonstrate visibility.
- Parish-to-school cardinality, skipped levels and initial lead provisioning are still open production rules. Use fixture associations without asserting nationwide constraints. Propose flexible relationships and record assumptions before migrations.

## Minimum usable screens

- Login for all demo personas using existing authentication; fictional account emails may use `example.test`.
- Amharic-first hierarchy view from union to schools, with church/city context displayed separately. Provide organization management only for explicitly authorized demo appointments.
- Workspace dashboard showing current actor, appointment, active scope and allowed actions; support safe scope switching when an account has multiple appointments.
- Local school/department/member sample listings constrained to the active actor; own-profile view for member accounts.
- Minimal local-draft/released report sample visibility sufficient to prove the lead-to-upper boundary. Full report authoring/approval workflow remains later work; do not present fixture statuses as a completed workflow.
- Clear forbidden/empty states; direct access to out-of-scope record IDs must be denied on the server without exposing record details.

## Manual demonstration matrix — proposed least-privilege baseline

| Sample actor | Expected visible/allowed | Expected denied |
|---|---|---|
| Union Super Administrator | Nationwide hierarchy, organization create/update, local-lead (`school_lead`) appointments, released reports, reporting completeness and national summaries | Private school member directories/details, membership approval, unreleased local drafts (`draft`/`submitted_local`), unrestricted `Gate::before` bypass, technical platform settings |
| Local lead A | Own school administration, its assigned coordinators/members and local approval actions when implemented | School B/C private records, granting upper/technical authority |
| Local lead B | Own school B workspace | Private school A/C data |
| Department coordinator A | Assigned department workspace; appropriate draft submission when implemented | Other departments' restricted records, final local approval, other schools |
| Approved member A | Own permitted profile, forms and published information | Other member profiles and administrative functions |
| Pending/unlinked account | Own application/link status only | Restricted member/school workspace |
| District officer | Released reports/approved aggregates within assigned district | Local drafts, unrelated districts, unrestricted local editing |
| Diocese officer | Released reports/approved aggregates in own diocese | Other diocese data, local drafts and unrestricted local editing |
| Union division coordinator | Released information within assigned function/jurisdiction | Unrelated functional/private records or blanket local editing |
| Union board account | Agreed governance summaries within appointment scope | Automatic full member/record access based only on title |
| Technical operator | Explicitly defined platform operations | Implicit business authority through inherited Super Admin bypass |

Detailed production personal-field visibility, appointment powers, exceptional intervention and exact board/division powers remain open. Implement the listed conservative demo baseline; default deny unspecified actions and clearly list deferred powers in the report. Include two appointments for one account if implementation supports switching scopes: switching must not combine unrelated privileges.

## Inherited hazards to close or isolate before demo acceptance

- Remove/replace the global Super Admin bypass for domain access; constrain role grants on the backend.
- Scope user listing/search/exports/media/audit surfaces or explicitly disable unsafe inherited routes for demo actors. API auth routes must be secured consistently or disabled during this local milestone.
- Cover read and write paths; do not let an arbitrary request `school_id` choose an unauthorized workspace.
- Do not claim inherited global features are domain-ready because demo navigation hides them.

## Report and manual handoff

Provide runnable local URL and start/stop commands, files/migrations changed, sample hierarchy diagram, account-role-scope matrix and ignored local credential-file path, exact setup/seed commands actually run, build/static results, and numbered manual sign-in/direct-URL scenarios with expected allowed/denied results. Include Amharic/English preference and mixed-text preservation checks. Report unresolved access paths honestly. Owner manual acceptance remains Pending until confirmed. Stop after the report; do not begin membership/reporting CRUD or deploy.
