# Hierarchy, member access and collaboration

Status: owner-confirmed direction with proposed design details.
Recorded: 14 November 2026, Africa/Addis_Ababa.
This clarification takes precedence over earlier suggestions limiting access to administrative users or treating member self-service as an optional future idea. It is not the final schema or complete requirements baseline.

## Confirmed product direction

- Organizational hierarchy is the heart of the application.
- Access extends to ordinary Sunday School members, who can complete forms, maintain their profile and find information.
- Each Sunday School has its own management area and authorized local administration. Multiple churches and Sunday Schools may exist in the same city.
- Sunday Schools also have common spaces for interaction, sharing and exchanging experience.
- Higher union/board administrators require their own organizational scopes, divisions and responsibilities; “highest admin” is not a single undifferentiated business role.
- Local lead approval precedes upward release of department reports. This records the owner's “yes” as approval of the lead-review option presented in the previous question.
- The Sunday School lead is the principal administrator for that local school and approves new memberships and requests to link an account to an existing member record.
- Higher offices provide general management, coordination and oversight. Their higher organizational position does not itself specify unrestricted editing of local member records; detailed higher-office powers remain to be agreed.
- Clarification: ordinary member registration and approval are local-lead responsibilities under higher-office oversight; no higher-office countersignature is required for each member. “School onboarding” means adding the Sunday School organization itself to the platform, a separate setup matter whose workflow remains undecided.

## Proposed hierarchy model

Three related structures must remain distinguishable:

| Structure | Purpose | Proposed examples |
|---|---|---|
| Coordinating hierarchy | Supervision, reporting and jurisdiction | Global/national union -> diocese -> district/sub-city coordinating unit -> local Sunday School |
| Internal units | Responsibilities within an organization | Union planning division; diocese membership unit; local audit or hymn department |
| Geography and church association | Location and parish identity | Country, region, city, parish/church, Sunday School |

City is not automatically an authorization parent: City A can contain Church 1 and Church 2, each with its own Sunday School and private management area. Church-administrative boundaries may differ from civil boundaries. The number of schools/programs permitted under one parish remains open.

A board is a governing body whose people hold appointments. A division/department is an internal unit. Neither should automatically be inserted as a new geographic reporting level. For example, a union may have a board and several divisions alongside its subordinate coordinating organizations.

The four-level chain above is a research-backed starting point; allowed organizational types, omitted/intermediate levels and reporting recipients require sponsor validation. Local departments need not be identical to union divisions.

## Proposed access rule

Effective access combines identity, active membership/appointment, organizational scope, department/division scope, capability, and the record's publication or workflow status.

| Actor | Intended workspace | Proposed boundary |
|---|---|---|
| Member | Own profile, assigned forms, permitted notices/resources and sharing spaces | Self-service does not grant access to another member's private profile or administration |
| Department coordinator | Assigned local department | Member/activity/form/report work only within authorized responsibilities |
| School lead | Principal local administrator | Approve membership/account links, coordinate departments, manage local access and approve reports within that school |
| District/diocese coordinator | Assigned branch of the organizational tree | Released reports and explicitly permitted coordination functions |
| Union division officer | Assigned union division and its authorized jurisdiction | Functional authority such as reporting or membership coordination, not automatic global write access |
| Union board member | Board information and assigned governance responsibilities | Board membership alone does not imply technical superuser access |
| Technical operator | System operation | Separate technical and program-management authority |

One person can hold multiple roles/scopes. Choosing an organization from a dropdown must never grant membership or access. All record reads, writes, downloads, exports and shared-space actions must enforce authorization on the server.

## Member self-service — proposed workflow

1. Member receives an invitation, joins through an approved registration flow, or claims an existing record using an agreed verification method.
2. The request remains pending until the Sunday School lead approves the membership or the link to an existing person/membership. A department coordinator may prepare or review information but has no independent final approval authority by default.
3. Member completes their profile and forms assigned by their authorized school/department.
4. Routine personal fields can be editable; membership status, official appointments and other controlled fields require authorized review.
5. Member sees notices, resources, events and shared content appropriate to their membership and audience.

Lead approval is owner-confirmed. Invitation methods, pre-review, field-editing rules and the exact pending-account experience remain proposals. A pending request must not grant access to restricted school information or another person's existing profile. Schools must still be able to register people who do not have an account; later linking should avoid duplicate person records. Assisted/guardian access for children remains to be designed.

## Recommended division of authority

The lead manages local operations within the permissions and standards established for the platform. This includes membership approvals, department setup, coordinator assignments and local report release. It does not include creating higher-office roles, changing another school's records or overriding platform access rules.

Higher offices coordinate schools in their jurisdiction, issue authorized notices/standards, review released reports, request corrections and consolidate information. Proposed additional capabilities such as approving school onboarding, appointing/replacing school leads, suspending a school workspace or intervening in local records require explicit role definitions. These are not automatically included in “general control.”

Keep platform technical administration separate from union governance. Multiple union board members and division officers should receive their actual responsibilities rather than a shared all-powerful account. If exceptional intervention is later authorized, record its scope, reason, actor and duration.

This is the assistant's recommended interpretation of local autonomy plus hierarchical oversight. It is not a claim about authority prescribed by church bylaws.

## Private management and common sharing

Proposed default: one shared hosted application with logically separate school workspaces and explicit shared publishing areas. This supplies a consistent management system to each school without requiring a separate software installation for every church. Deployment topology is still a technical proposal.

Private member profiles, internal reports, audit findings and financial documents are not made visible to other schools by participation in common spaces.

Proposed shared capabilities include announcements, experience posts, resources, event notices and moderated discussion. Comments, direct messaging and real-time chat are not automatically part of the first release. Each published item should have an author, owning organization, audience, publication state and responsible moderator. Audiences may be school-only, selected schools, district, diocese or platform-wide as authorized. Public internet publication is a separate decision.

## Reporting approval

Department draft -> submit to local lead -> return for correction or approve -> release the approved version to the designated upper office -> higher-level review/consolidation.

Upper offices receive released content rather than unpublished department draft contents. Report-completeness statistics may identify a missing submission without disclosing its draft. Revisions require review and re-release; earlier approved versions remain available as historical records. Any additional intermediate approval gates remain open.

## Proposed requirements and manual acceptance

| ID | Requirement | Manual acceptance example |
|---|---|---|
| HIER-001 | Register organizations with allowed parent relationships and stable IDs | Add two schools associated with different churches in one city without mixing records |
| HIER-002 | Prevent hierarchy cycles and preserve relevant history when organizations change parent | Invalid self/descendant parent is rejected; old report context remains explainable |
| HIER-003 | Support internal divisions/departments at higher and local levels | Create a union reporting division and local hymn department without treating either as a city |
| HIER-004 | Record board and coordinator appointments with scopes and effective dates | A board member receives only the governance permissions assigned to the appointment |
| HIER-005 | Enforce access on every relevant surface | A copied URL, export link or file link does not expose another school's private records |
| MEM-001 | Provide member login and own-profile access | A member edits permitted personal fields and cannot change official roles/status |
| MEM-002 | Link accounts to membership through verified onboarding | An existing registered person claims access without creating a second member record |
| MEM-003 | Publish forms to authorized audiences and collect member responses | A school form is visible to assigned members; responses remain within permitted review scope |
| MEM-004 | Provide information according to audience | A member sees relevant local and higher-office notices but no restricted departmental report |
| MEM-005 | Require Sunday School lead approval for membership and account-link requests | A membership coordinator can review a request but cannot activate it; the assigned school lead can approve it |
| MEM-006 | Preserve a pending state and approval history | Pending access cannot open restricted school pages; approval records identify the lead and decision time |
| SHARE-001 | Offer explicit cross-school experience/resource sharing | An approved experience post is visible to selected partner schools while its author's private profile stays protected |
| SHARE-002 | Support publication approval and moderation roles | An authorized moderator publishes or returns a submitted item; another school cannot edit it |
| REP-010 | Require local lead approval and release before upward disclosure | A submitted draft is invisible to upper readers until approved/released; rejection returns it locally |
| REP-011 | Restrict reporting access by organizational and functional scope | A division officer sees permitted reports in the assigned jurisdiction, not every raw member record |

These are proposed acceptance scenarios for the owner to perform manually, not automated test tasks.

## Relational design implications

- Keep organizations and their parent relationships separate from locations and parish associations.
- Distinguish internal organizational units, governing bodies and appointments; a single role-name string cannot represent all three.
- Keep people, school membership, department membership, accounts and scoped role assignments separate.
- Form definitions require versions, audience assignments and response records; approved response contents should not be silently changed by editing a form definition.
- Shared content requires publication/audience records separate from private operational records. Cross-school sharing does not change tenant ownership.
- Reports retain originating school/department, period, revision, approval and release history.

Final table names and constraints are deferred to the data-model document.

## MVP implications and open decisions

Member self-service, hierarchy, separate local workspaces, local department coordination and approved upward reporting belong in the core product scope. A bounded shared announcements/resources/experience area is proposed for the MVP; detailed discussion and messaging capabilities remain to be selected.

Member-school links are approved by the Sunday School lead under higher-office oversight; individual member approvals do not escalate to higher offices. Still decide which profile fields require review, which higher-office units can see which reports, the exact hierarchy allowed in the pilot, moderation responsibilities, whether any content should be public, and the separate initial platform setup for school organizations and lead accounts. No mandatory higher-office school-onboarding approval has been agreed. These decisions affect workflows but do not weaken the confirmed hierarchy-first direction.
