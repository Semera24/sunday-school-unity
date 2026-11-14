# Roles and permissions

Status: approved direction; matrix is proposed implementation policy pending detailed review.

## Authority rules

The local lead approves memberships/account links and local report release under higher-office oversight. Higher rank defines jurisdiction, not unlimited editing rights. Department membership, official appointment, account and permission assignment are distinct. One person may have several scoped appointments.

| Actor | Own profile/forms | Local members | Department work | Report approval | Higher reporting |
|---|---|---|---|---|---|
| Member | Permitted own data | No private directory by default | Own participation/information only | No | Published information only |
| Department assistant | Own | Delegated fields/scope only | Delegated tasks | No | No |
| Department coordinator | Own | Relevant authorized records | Assigned department | Submit, not final local release | No unpublished cross-school data |
| School lead | Own | Approve and manage own school | All authorized local departments & coordinator appointments | Local approve/release | Own submissions/feedback |
| District/diocese officer | Own | No default raw editing | Assigned coordination duties | Higher review where assigned | Released reports in jurisdiction |
| Union division officer | Own | Only explicitly granted functions | Own division and authorized reach | Assigned review functions | Authorized category/jurisdiction |
| Union board member | Own | No blanket editing | Governance responsibilities | Explicitly assigned | Authorized governance reports |
| Union Super Administrator | Own | No automatic private member directory or membership approval | Nationwide org create/update & local-lead appointments | No local draft access or release | Released reports, completeness & national summaries |
| Auditor | Own | Minimum assigned evidence | Record findings | Audit outcome, not source rewriting | Authorized review scope |
| Technical operator | Account operations | No automatic business mandate | Infrastructure duties | No automatic approval authority | Operational monitoring |

## Required enforcement surfaces

Queries, route/model binding, policies, write validation, form audiences, downloads, search, exports, reports, notification targets and sharing audiences must all enforce scope server-side. UI navigation is not enforcement. Prevent role grants beyond the actor's own authorized grant boundary.

Pending memberships must not disclose an existing person's profile. Ending an appointment must remove its effective access while retaining history. A technical recovery mechanism, if implemented, must be explicitly separate and auditable.

## Existing foundation gaps

The inherited five generic roles and global Super Admin Gate bypass are not this matrix. Do not just rename them. Current global user/media/audit/export paths need redesign; role-grant and API-login findings are in the feature register. Until resolved, do not use the foundation with real multi-school data.

## Unresolved details

Initial school/lead provisioning, temporary delegation, emergency intervention, child accounts, visibility of individual financial/audit evidence, and whether cross-school memberships are permitted. None changes the confirmed local approval authority.
