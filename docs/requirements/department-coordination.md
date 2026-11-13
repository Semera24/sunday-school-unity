# Sunday School departments, coordinators and reporting

Status: owner-confirmed product direction with proposed requirements for review.
Recorded: 14 November 2026, Africa/Addis_Ababa.
This document supplements the domain research. It is not the complete requirements baseline or a finalized database schema.

## Owner-confirmed direction

- Primary operators are senior coordinators and appointed administrators who guide members and manage their Sunday School. “Teacher” was not the intended name for this operator role.
- Each participating Sunday School has departments with coordinators. The owner specifically identified membership administration, development, audit, income/business generation, hymn preparation and arts.
- Each Sunday School receives access to manage its own operation through the application.
- Higher coordinating offices receive reports through the application and obtain an overview across the country.
- Member/student/disciple registration and administration remain part of the product. Members can access their own profile, forms and information; registered people without accounts must also be supported.
- Local lead approval is required before department reports are released upward, interpreting the owner's subsequent “yes” as selecting this option.
- The Sunday School lead is the principal local administrator and final approver of membership and account-link requests. Department coordinators may support review but do not independently grant final approval by default.
- Hierarchy, higher-office divisions and member/cross-school access are further defined in [hierarchy, member access and collaboration](hierarchy-member-access-sharing.md).

The role labels below describe responsibilities, not automatically separate people. Exact permissions, additional intermediate report-review gates and department templates remain proposed.

## Research and interpretation

[GSSU's structure](https://eotc-gssu.org/a/መዋቅራዊ-አደረጃጀት/) describes global, diocese, district/sub-city and parish levels and different parish organizational categories. Its global standing committees should not be copied as a mandatory department list for every parish.

An [Anketse Birhan Sunday School page presenting local bylaws](https://sturaless.blogspot.com/p/blog-page_3.html), section 9.1.3, lists teaching, hymns/arts, membership, relations, development, accounts/property, children, charity, programs, audit/inspection, an office and a parish-council representative. It permits structural variation. This is a self-published local example; its current approval status and nationwide applicability are unverified.

A [Holy Trinity Cathedral explanation](https://eotc-htc.org/a/950/) distinguishes parish-council accountability from Sunday School organization. Its list of parish-office departments is not a list of departments inside every Sunday School. The application should support agreed reporting recipients without assuming that one reporting chain captures every governance relationship.

English/Amharic labels below are proposed UI terminology. They need validation with pilot coordinators.

## Configurable department catalogue

Evidence column: Owner = directly named by the owner; Local example = named on the linked local page; Proposal = suggested grouping or additional category. These establish candidates, not a compulsory organizational chart.

| Candidate department | Proposed Amharic label | Evidence | Proposed information/workspace |
|---|---|---|---|
| Membership affairs | የአባላት ጉዳይ ክፍል | Owner; local example | Registration, member status, service assignments and follow-up |
| Development | የልማት ክፍል | Owner; local example | Initiatives, activities, progress, needs and outputs |
| Income generation / business initiatives | የገቢ ማስገኛ ክፍል | Owner | Initiatives, responsible people and period income/cost summaries |
| Audit and inspection | የኦዲትና ኢንስፔክሽን ክፍል | Owner; local example | Review assignments, findings, recommendations and follow-up |
| Hymn preparation / service | የመዝሙር ክፍል | Owner; local example combines hymns/arts | Preparation activities, service groups and participation |
| Arts | የኪነ ጥበብ ክፍል | Owner; local example combines hymns/arts | Creative programs, participants and approved materials |
| Education and training | የትምህርትና ሥልጠና ክፍል | Local example; GSSU research | Training activities and participation; no assumed examination system |
| Finance and property | የሒሳብና ንብረት ክፍል | Local example | Submitted financial/resource summaries and supporting documents |
| Relations | የግንኙነት ክፍል | Local example | Coordination, visits and communication activity |
| Children’s affairs | የሕፃናት ክፍል | Local example | Age-appropriate programs, member links and participation |
| Charity / mutual support | የበጎ አድራጎት ክፍል | Local example | Support activities, outputs and restricted supporting records |
| Programs / events | የመርሐ ግብር ክፍል | Local example | Planned events, assignments and completion reports |
| Information and records | የመረጃና መዛግብት ክፍል | Proposal informed by GSSU information committee | Records, documents and authorized publication |
| Planning and reporting | የዕቅድና ሪፖርት ክፍል | Proposal informed by GSSU planning committee | Plans, reporting deadlines and compilation |

The chairperson, secretary and parish-council representative should initially be modeled as appointments/positions, not automatically as ordinary departments. A school's secretariat may be an office if its actual structure requires one.

## Proposed actors and access

| Actor | Proposed responsibility and boundary |
|---|---|
| Central administrator | Register coordinating organizations, set reporting standards and view authorized consolidated reports |
| Diocese/district coordinator | Review and consolidate reports within assigned organizational scope |
| Sunday School senior coordinator / lead administrator | Principal local administrator; approve memberships/account links, manage local departments and coordinator appointments, and release reports |
| Department coordinator | Guide department members, record activities, prepare department reports and respond to review comments |
| Authorized department assistant | Perform specifically delegated entry work within the department |
| Auditor | Read assigned evidence and record findings; no automatic ability to rewrite the records being audited |
| Registered member/disciple | Own profile, assigned forms and permitted information/sharing areas; no default administrative access |

One person may hold multiple appointments. “Senior” describes responsibility here, not an age rule or permission to administer other schools. Department membership, coordinator appointment and login permission are distinct associations.

## Proposed requirements and manual acceptance

| ID | Requirement | Manual acceptance example |
|---|---|---|
| DEP-001 | A school can create, rename and deactivate its departments from configurable templates | Configure hymns and arts together in School A and separately in School B |
| DEP-002 | Departments can map to a central reporting category while retaining their local name | Different local development labels appear under a comparable national category |
| DEP-003 | Appoint a lead coordinator and optionally assistants with start/end dates and responsibility | An expired appointment no longer authorizes access; history remains |
| DEP-004 | Register members and assign them to departments with dated membership records | One person can participate in multiple departments without duplicate person records |
| DEP-005 | Limit record access by school, department and assigned capability | School A coordinator cannot open School B's records by changing a URL |
| DEP-006 | Record department activities, responsibilities, participation, progress and attachments | A coordinator submits an activity with its period and supporting document |
| REP-001 | Higher offices define reporting cycles, templates, fields and due dates | Create a quarterly cycle without changing application code; frequency is not hard-coded |
| REP-002 | Department coordinators save drafts and submit reports | A draft is distinguishable from a submitted report; author and submission time are recorded |
| REP-003 | Require local lead review/approval before onward release | Lead coordinator returns a department report for correction; upper office cannot read its contents until approval/release |
| REP-004 | Higher offices receive submissions for their assigned scope and request corrections | A district reviewer sees the district's schools and can return a report with a comment |
| REP-005 | Preserve submitted versions and distinguish revisions | A corrected report does not silently replace figures in a previously approved version |
| REP-006 | Consolidate compatible figures and display reporting completeness | National overview identifies submitted, missing and late schools; missing data is not zero |
| REP-007 | Count people correctly across departments and report versions | A person in choir and arts counts once as a unique school member, while department participation remains separate |
| REP-008 | Notify responsible coordinators of deadlines and returned reports | The assigned coordinator receives an in-app notice and can open the relevant report |
| REP-009 | Export/print authorized summaries | A district export contains only permitted reports and includes its period and completeness |
| GOV-001 | Keep audit review separate from editing underlying financial/operational records | An auditor records a finding without altering the source transaction or activity |
| GOV-002 | Record appointments, submissions, reviews and significant changes in audit history | Reviewer can identify who changed a report and when |

All acceptance is manual under the owner's policy. These are reviewable requirements, not implemented behavior.

## Proposed reporting flow

Department coordinator prepares draft -> submits to Sunday School lead -> local review/compilation -> release to configured upper office -> review/consolidation -> national overview.

Local lead approval before release is recorded from the owner's subsequent confirmation. Additional intermediate approvals and designated upper recipients remain to be defined. Upper offices receiving reports does not automatically authorize unrestricted access to all member details or restricted audit findings.

Shared reports should include period, department, coordinator, activities, agreed numeric indicators, achievements, challenges, support requested and next-period plans. Department-specific sections can supplement these. Comparable figures require central definitions and template versions. Local report detail and national aggregation must not double-count source records, departments, school totals or superseded report versions.

## Relational implications — proposed

- `Organization` represents coordinating units; `SundaySchool` identifies the local operational scope.
- `DepartmentCategory` provides centrally comparable categories; `Department` is the actual local unit. Names alone are not stable identifiers.
- `Person` and `SchoolMembership` represent people and membership independently of accounts.
- `DepartmentMembership` links people to departments; `CoordinatorAppointment` links people, scope, position and service period.
- `UserAccount` and `ScopedRoleAssignment` establish login and capability; appointment changes must update access deliberately.
- `Activity` and `Participation` describe department work and involvement.
- `ReportingCycle`, `ReportTemplateVersion`, `ReportSubmission`, `ReportRevision` and `ReportReview` preserve reporting context and history.
- `AuditReview`, `Finding` and `FollowUpAction` are business records distinct from technical application audit logs.

Final table names, cardinalities and constraints belong in the data-model design after workflow review.

## Proposed MVP boundary

First implement hierarchy/local school setup, configurable departments, member registration and self-service profiles/forms, coordinator appointments, scoped access, basic activity records, locally approved upward reporting and higher-level overview. Include a bounded shared information/experience area as proposed in the hierarchy document. Reuse notifications, media and export infrastructure from the boilerplate after adding organizational controls.

Registering a department does not require implementing every possible specialist workflow. Full accounting, payroll, stock management, business point-of-sale, complex arts production, academic examination systems and electronic elections are not established requirements. A department can initially report its work through activities, indicators and documents.

## Decisions still open

- Which coordinating levels review reports, and which merely view summaries?
- Which department templates and national indicators are mandatory, and who approves custom categories?
- Which financial details, audit findings and member details can each upper role see?
- What terms, delegation and multiple-appointment rules apply to coordinators?
- Does “give access” mean one hosted shared application (proposed), or separate deployments per school? The owner's wording establishes access, not a deployment topology.
