# TASK-027 — Autonomous overnight hierarchy/access completion

Owner instruction: work autonomously while the owner is away, complete one coherent milestone, and return a detailed final report. This task overrides the normal stop-for-review-after-each-slice rule **within this assignment only**. Do not wait for routine decisions or repeated confirmations; use conservative documented defaults. Do not expand this into implementing the entire product.

## Scope and order

1. Read current checkpoint, TASK-024, language/data policy and `docs/reports/2026-11-14-task-024-extension-review.md`. Inspect current diff and preserve all user/reviewer/implementer work. Do not repeat onboarding research or reset to the initial commit.
2. Fix both review findings before adding polish: target-aware restrictions on technical modification of business accounts (password, email, role grants, deletion and equivalent takeover/recovery paths), and durable technical/business identity/appointment incompatibility that does not disappear when a global role is removed. Preserve owner self-service and existing login credentials. Do not invent a recovery system tonight; deny unsafe technical mutations and document deferred recovery.
3. Self-review all reachable hierarchy/demo paths for scope leaks. Check controllers/policies/queries, not just menus: active appointment dates, same-city separate schools, department scope, pending members, safe scope switching, organization/appointment mutations, local approval/release, technical user/role management, media, audit, search, exports and API endpoints. Scope or deny inherited surfaces that could leak business data; document surfaces deliberately unavailable. Add validation/constraints/transactions and durable audit records where relevant. Do not add new business privileges or academic modules.
4. Finish bilingual coverage for the hierarchy milestone and the auth/account-setting paths used by the demo. Amharic default, English selectable; replace remaining inline bilingual labels and raw status/type codes with catalogue-based labels. Include visible buttons, forms, tables, empty/error states, navigation, flashes and validation messages for these paths. Personal/member/report text stays exactly as entered. Prefer translated reference names by selected locale with Amharic fallback; do not automatically translate data. Review Ethiopic font usage and phone-width layout in source; do not claim visual/manual acceptance without owner confirmation. Defer full unrelated technical feature localization if it would expand scope; list exact remaining screens.
5. Make existing implemented operations usable end to end: nationwide hierarchy organization create/update, local-lead appointment assignment/status/dates, own-school department/coordinator management, member self-profile, membership approval, report submission/release and scoped report summaries. Do not claim full CRUD if delete/edit/registration workflows are absent. Do not add hard deletion of organizations/people or new full membership/reporting modules to fill a checklist.
6. Keep all 11 fictional demo accounts and existing passwords. Fix seed idempotency if needed; rerunning must not silently reset owner-modified membership/report statuses, language choices, appointments or passwords. Do not run a seeder over owner-modified records if it cannot preserve them; use a narrowly additive provisioning operation instead. No real people/data.
7. Run proportionate PHP syntax/format, relevant ESLint, TypeScript and production asset build checks. Fix failures within scope, rerun only affected checks and the final build as needed. **No automated unit/feature/integration/browser/end-to-end/snapshot/regression/coverage tests or testing packages; no scripted functional scenario harness.** Owner performs functional acceptance.
8. Finish with a source/diff self-review, then update PROJECT_STATUS, backlog and a final report at `docs/reports/2026-11-14-overnight-hierarchy-completion.md`. Stop when this milestone is delivered or a genuine blocker prevents safe progress; continue independent authorized work around a blocker. Never mark unfinished work complete merely because the owner is asleep.

## Environment and coordination

- Reuse installed dependencies and dedicated `sunday_school_unity` PostgreSQL configuration. Verify live install/migration state rather than trusting old missing-dependency notes.
- No database reset/drop/fresh migrations, no unrelated databases/roles, no production/external changes, no secret output, no replacing nonempty APP_KEY or owner DB password, no commits/pushes/deployment. Only necessary additive project migrations against the identified local DB are within scope.
- Owner changed DB password; trust-authenticated connectivity is not password parity. Do not change shared cluster authentication or reset passwords as a side task; report the existing limitation.
- Terminal cleanup agent remains read-only and must not edit overlapping files. Defer unrelated cleanup. Do not restore upstream tests/AGENTS/Boost instructions.
- Store demo credentials only in verified Git-ignored local credential files. Detailed tracked report must contain account emails and credential-file link, never passwords. Provide local start/stop commands and URL; do not expose a server publicly. No need to start a persistent server overnight solely for the report.

## Completion report (detailed but concrete)

- Completed vs deferred scope; exact files and behavior changed.
- Both reviewer findings: root cause, correction paths and remaining limitations. Do not present source inspection/build as proof that manual permission scenarios passed.
- Current hierarchy diagram; all account emails/roles/scopes, credential-file location and three main comparison personas.
- Allowed/denied matrix with actual routes and record identifiers, including direct requests, scope switching and technical account takeover attempts that must fail.
- Language coverage table and any remaining untranslated screens/messages.
- DB migrations/provisioning actually run, effects and preservation of existing data; no secrets.
- Commands actually run, outcomes and unresolved failures. No invented verification evidence.
- Numbered morning manual checklist: view-only scenarios first, then mutating actions, with expected results and how those changes affect subsequent checks.
- Current runtime/build state, local URL/start/stop commands, open risks and one recommended next task.
- Implementation status, reviewer status and owner acceptance separately; owner acceptance stays Pending.
