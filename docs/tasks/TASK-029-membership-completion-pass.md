# TASK-029 — Finish and harden the membership milestone

Owner asks Antigravity to finish the current task. TASK-028 is reported implemented, not owner-accepted. Do not rebuild it or start another domain module. Execute this bounded completion pass autonomously and stop with a detailed report/manual handoff. No per-slice confirmation needed for fixes within this brief.

Read AGENTS, current checkpoint, TASK-028 brief/report and current source/diff first. Preserve existing work, records, statuses, language preferences and all demo passwords.

## Reviewer source observations to address

### 1. Competing invitation and account-link state

`issueInvitationForMembership()` revokes only `pending_acceptance` rows and resets the membership link state; `MembershipInvitationController::accept()` can replace `pending_user_id`; `approveInvitationLink()` does not explicitly require the invitation's accepted user to equal the membership's current pending request before approving. A second invitation/reissue while a prior accepted request awaits approval can therefore leave stale decision candidates. Review the complete flow and fix it atomically, not by hiding controls.

- Choose one clear current pending link request per membership. Deny a new request while lead review is pending, or require an explicit audited cancellation/supersession that invalidates the old request. Never silently replace a pending account.
- Recheck current invitation, membership, pending target and school under transaction/locks at acceptance/approval/rejection/revocation. A stale decision must not clear/approve/reject a newer request. Bind review to the current accepted invitation/account; prevent overwriting existing links.
- Use a consistent lock acquisition order across issuance, acceptance, link decisions and lifecycle changes. Handle duplicate clicks/stale requests with translated errors or a clearly idempotent success, not inconsistent history.
- Check same-school duplicate account/person links and the deferred cross-school claim policy from TASK-028; do not let invitation acceptance turn one login into an unintended second person. Do not leak other-school matches or silently merge identities.
- Preserve hashed tokens, expiry, single use, scoped permissions, pre-approval business identity protection and existing-account authentication. No password/email overwrite or technical account eligibility.

### 2. Department participation integrity and history

TASK-028 dropped the member/department unique constraint and replaced it with a nonunique index. `storeDepartmentMembership()` locks matching active rows, but when no active row exists that does not serialize two concurrent inserts. `updateDepartmentMembership()` can reactivate an ended row by erasing its end date/actor.

- Add a PostgreSQL partial unique constraint/index for at most one `is_active=true` participation per membership/department, while permitting ended history. Use a new additive migration; inspect existing conflicts and report them rather than deleting/rewriting records silently.
- Serialize relevant operations with an existing parent membership lock and handle integrity conflicts as translated validation errors. Apply the same invariant in initial member creation, assignment, reactivation and seed provisioning.
- Preserve ended intervals/actor history. Returning a member to a department should create a new dated interval or use an equivalent explicit history-preserving model; do not erase a previous service period.
- Validate date order and meaningful interval handling, including partial updates/future dates. Do not silently clamp invalid end dates to start dates. Document how effective dates affect visibility; assignments must not gain authority merely from UI labels.

### 3. Mail delivery and committed state

`issueInvitationForMembership()` currently sends mail synchronously from within the callers' transaction. Mailpit may receive a link even if a later database operation rolls back.

- Deliver only after the invitation is committed; give the lead clear send/failure feedback and safe retry/resend behavior without losing the created member or silently creating conflicting pending links. Do not add a large messaging system or external delivery service.
- Do not log raw tokens/passwords or expose internal exception/connection details. Local Mailpit only; no external mail.
- Review account login/session side effects on transaction failure and keep them consistent with actual committed account/link state.

## Complete the original membership acceptance scope

Review TASK-028 against actual routes/screens: registration with/without account, duplicate warning, member edit, lead review queue approve/reject, lifecycle/history, department participation, member self-service and invitation expiry/revocation/retry. Fix reachable omissions within that scope; do not add transfer/finance/report-builder/forms/attendance modules.

Retain cross-school/department/role protections, safe scope switching, pending/rejected/suspended/archived restrictions and durable technical/business separation. Ensure lifecycle changes cannot approve a stale link or restore privileges silently. Check shared-Person edits do not modify another school's private data; defer unsupported shared-identity changes with a clear message.

Translate all changed/new interface text, flashes, validation and empty states using am/en catalogues. Amharic default; preserve original mixed text. Keep code organized in focused existing-style services/requests where needed for these fixes; avoid a wholesale controller refactor or unrelated formatting cleanup.

## Verification and delivery

- No automated tests, browser automation, ad hoc HTTP/functional/policy/password assertion scripts or testing packages. Owner manually verifies functional scenarios.
- Proportionate PHP lint/format, relevant ESLint, TypeScript and final production build; self-review source/diff. Record actual commands and failures honestly.
- Dedicated local project DB only, additive migrations only. No reset/wipe/drop/reseed over owner modifications, password resets, .env/key rotation, shared cluster-auth changes, public deployment, commits or pushes.
- Existing 11 demo accounts/passwords unchanged. Any new credentials stay in verified Git-ignored files. Never copy them into tracked reports.
- Update requirements/data model/checkpoint/backlog and write `docs/reports/2026-11-14-membership-completion-pass.md`.
- Report each finding's correction, exact files/migrations, data effects, commands/results, implemented vs deferred scope and limitations. Provide runnable local URL, credential-file location and a numbered manual checklist covering the normal invitation/link journey plus stale invites/reissue, wrong account, double clicks/two-tab decisions, lifecycle restriction and ended/new department periods.
- Keep reviewer status Pending and owner acceptance Pending. Source review/build are not manual acceptance. Finish this milestone/report and stop; recommend the next module without implementing it automatically.
