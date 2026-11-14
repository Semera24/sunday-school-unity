# TASK-024 Union Super Administrator extension — reviewer findings

Scope: targeted static source review of owner-relayed extension report. No manual login/exploit performed, no credentials changed, no automated tests run. Build/type/lint successes remain implementer-reported. Reviewer disposition: changes requested before accepting technical/business separation.

## High — Technical user editor can take over business identities

`app/Policies/UserPolicy.php:24` authorizes updates solely through `users.update`, without considering the target's business appointments/member links. `app/Http/Requests/Admin/UpdateUserRequest.php` permits password/email changes; `app/Http/Controllers/Admin/UserManagementController.php:113` applies them. Demo seeder assigns technical operator the Admin role, which includes user-management privileges.

A technical operator can therefore submit a new password for a union administrator or school lead and sign in as that business user (unless an additional independent login control blocks it). This defeats the promised separation even though direct school/report URLs deny the original technical account. This conclusion is from source, not an executed takeover.

Required correction: target-aware backend boundaries for business-identity password/email/role changes and any impersonation/recovery paths. For the demo, deny technical mutations of business identities rather than designing a new recovery workflow. Keep authorized self-service through the separate account settings flow. Include direct update-request denial in manual handoff; verify record unchanged and existing demo credentials retained.

## High — Business appointment exclusion relies on mutable global roles

`HierarchyDemoController::storeLocalLeadAppointment()` and `storeDepartmentCoordinatorAppointment()` identify technical users using `$targetUser->roles()->exists()`. A technical operator retains its `technical_operator` scoped appointment if its global Admin role is removed using inherited role administration. The role-presence check would then no longer recognize that identity as technical. A union administrator could assign it a school-lead appointment despite the stated no-mixed-authority constraint.

Required correction: check technical identity/appointments explicitly on the backend and enforce technical/business incompatibility across relevant write paths. Do not infer technical identity from any global role existing. Decide and document how inactive technical appointments are treated; preserve fixtures and do not grant business access as a side effect of technical role maintenance.

## Positive observations / limits

Union-super-admin role, organization management endpoints, scoped lead/coordinator grants, audit calls and translated extension sections are present. Direct member/report visibility helpers explicitly deny the technical operator and restrict union users to released reports. The new sample account is listed in the ignored credential file. These observations do not constitute full access review or owner acceptance. Full bilingual conversion and broader domain CRUD remain separate work.

## Next handoff

Return this targeted correction brief to Antigravity. No extra features, database resets, account password resets, deployment or automated tests. Preserve existing seeded credentials. Update implementation report with exact fixes, proportionate static/build evidence and manual allowed/denied steps. Owner can explore the demo now, but security acceptance remains Pending until corrections are reviewed and manually accepted.
