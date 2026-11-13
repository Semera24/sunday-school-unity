# GSSU domain research before requirements

Research date: 14 November 2026, Africa/Addis_Ababa.
Status: source-backed observations and proposed domain concepts; not an approved requirements document or database schema.

## Reading method and limits

The requested [English-section homepage](https://eotc-gssu.org/e/) and [projects page](https://eotc-gssu.org/e/ፕሮጀክቶች/) were read through direct HTTP retrieval after the browsing tool could not access them. Both contain substantial Amharic. Related Amharic pages were also read. English explanations here are working paraphrases, not certified translations.

The English section includes older content; a page being online does not prove that its procedures or committee appointments are current. The Amharic site includes newer curriculum announcements. Neither section establishes the intended application's permissions, pilot sponsor, or complete student workflows.

The linked [internal bylaws PDF](https://eotc-gssu.org/a/wp-content/uploads/001-bylaws.pdf) was discovered. The browser reported 70 pages but supplied no readable text; page screenshots failed. Its clauses have NOT been reviewed. No election terms, delegate quotas, quorum, meeting frequency, or promotion rules are asserted from it.

## Source-backed observations

### Institutional purpose

The [basic information page](https://eotc-gssu.org/a/መሰረታዊ-መረጃዎች/) describes a church-approved structure for coordinated Sunday School service from the central church office to parish level. Its reach includes international Sunday Schools. This supports an organizational model broader than Addis Ababa; it does not require worldwide deployment in the pilot.

### Organizational levels and school variation

The [Amharic structure page](https://eotc-gssu.org/a/መዋቅራዊ-አደረጃጀት/) describes four assembly levels: Patriarchate/global, diocese, district/sub-city, and parish. It also identifies four parish organizational categories related to differences in capacity. Their detailed classification rules were not established.

The [English-section structure page](https://eotc-gssu.org/e/መዋቅራዊ-አደረጃጀት/) identifies a management council office and nine standing committees. A district church office and a civil administrative district should not be assumed to have identical boundaries.

### Standing committees

The following names appear in the English-section structure/navigation. Responsibilities are paraphrased from the linked pages where retrieved.

| Amharic label | Working English label | Published emphasis |
|---|---|---|
| የግንኙነት ጉዳዮች ቋሚ ኮሚቴ | Relations and coordination | Connections among schools and coordination of information flow |
| የትምህርትና ሥልጠና ጉዳዮች ቋሚ ኮሚቴ | Education and training | Curriculum implementation and complementary training |
| የመዝሙርና ተጓዳኝ ጉዳዮች ቋሚ ኮሚቴ | Hymns and related affairs | Hymn service, tradition, and content review |
| የዕቅድ ዝግጅትና ትንተና ቋሚ ኮሚቴ | Planning and analysis | Service plans, implementation coordination, and performance follow-up |
| ልማትና ራስን ማስቻል ቋሚ ኮሚቴ | Development and self-reliance | Sustainable resources and contributions of skills, effort, time, and money |
| የአስተዳደርና ፋይናንስ ጉዳዮች ቋሚ ኮሚቴ | Administration and finance | Volunteers and employees, responsibilities, training, money, and material resources |
| የመረጃና ልዩ ልዩ ወቅታዊ ጉዳዮች ቋሚ ኮሚቴ | Information and current affairs | Records, archives, databases, technology, and public information |
| የጥናትና ምርምር ቋሚ ኮሚቴ | Study and research | Research projects supported by members and specialists |
| የበዓል ዝግጅት ቋሚ ኮሚቴ | Festival organization | Joint festivals, experience-sharing forums, and annual Sunday Schools Day |

Committee sources:

- [Relations](https://eotc-gssu.org/a/ቋሚ-ኮሚቴ/)
- [Education and training](https://eotc-gssu.org/a/2-የትምህርትና-ሥልጠና-ጉዳዮች-ቋሚ-ኮሚቴ/)
- [Hymns](https://eotc-gssu.org/a/3-የመዝሙርና-ተጓዳኝ-ጉዳዮች-ቋሚ-ኮሚቴ/)
- [Planning](https://eotc-gssu.org/a/4-የዕቅድ-ዝግጅትና-ትንተና-ቋሚ-ኮሚቴ/)
- [Development](https://eotc-gssu.org/e/5-ልማትና-ራስን-ማስቻል-ቋሚ-ኮሚቴ/)
- [Administration and finance](https://eotc-gssu.org/e/ቋሚ-ኮሚቴዎች/)
- [Information](https://eotc-gssu.org/e/6-የመረጃና-ልዩ-ልዩ-ወቅታዊ-ጉዳዮች-ቋሚ-ኮሚ/)
- [Research](https://eotc-gssu.org/e/8-የጥናትና-ምርምር-ቋሚ-ኮሚቴ/)
- [Festivals](https://eotc-gssu.org/e/9-የበዓል-ዝግጅት-ቋሚ-ኮሚቴ/)

These are confirmed published committee names, not nine mandatory software roles or nine approved modules. We have not verified that every parish uses these same committees.

### Curriculum and program work

The [curriculum index](https://eotc-gssu.org/a/ሥርዓተ-ትምህርት/) lists grades 1–12. Early-grade resources distinguish doctrine, scripture, Christian ethics, church order, Ge'ez, and church history. The page is not a complete grading or promotion policy.

The [2019 E.C. curriculum implementation announcement](https://eotc-gssu.org/a/የ2019-ዓ-ም-የሥርዓተ-ትምህርት-ማስተግበሪያ-የ/) describes an annual implementation schedule and work with children, youth, and adults in Ethiopia and abroad. Its linked schedule contents were not examined.

The [projects page](https://eotc-gssu.org/e/ፕሮጀክቶች/) groups work into curriculum preparation/implementation, media/technology, and capacity/organizational development. It lists textbook translation, teacher development, virtual teaching, software, and a database initiative. Current delivery status and integration access remain unknown. These project categories suggest possible future capabilities; they do not automatically belong in the first release.

## Actors versus entities — proposed interpretation

An actor is someone who uses the application in a responsibility. An entity is information we store. A person may hold several responsibilities and may exist in the database without a login.

Owner clarification after the initial research: conventional school-management software was an analogy for registration, CRUD and administration. The intended register concerns Sunday School students/disciples/members. Primary operators are senior coordinators and appointed administrators, including departmental coordinators, rather than a teacher role. Local schools manage departments and report upward through the application. See [department coordination requirements](../requirements/department-coordination.md), which distinguishes this confirmed direction from proposed departments and review workflows. Academic modules remain unconfirmed; the older entity candidates below are not an approved feature set.

| Proposed application actor | Responsibility to clarify | Related entities |
|---|---|---|
| Global/national program administrator | Structure, standards, reporting oversight | Organization, reporting cycle, curriculum |
| Diocese coordinator | Coordination within a diocese | Organization, role assignment, report submission |
| District/sub-city coordinator | Support and reporting review within a district | Organization, report review |
| Parish Sunday School administrator/registrar | Local records and enrollment | School, person, membership, enrollment |
| Department coordinator / authorized assistant | Guide members, manage assigned department activities and prepare reports | Department, membership, appointment, activity, report submission |
| Committee officer | Work assigned to a committee | Committee, appointment, activity, document |
| Delegate/assembly organizer | Representation and meeting administration | Assembly, delegate appointment, resolution |
| Treasurer | Financial responsibilities if in scope | Contribution, receipt, financial record |
| Guardian/learner | Optional access to own permitted records | Guardian relationship, enrollment |
| Technical operator | Deployment and maintenance | User account, audit record; no automatic program authority |

No application permission above is established by the website alone. Approval authority and access to named student records must be agreed with the sponsor.

## Proposed relational entity groups

This is a conceptual shortlist for the subsequent data-model document, not a commitment to table names or module scope.

| Group | Candidate entities | Relationships to preserve |
|---|---|---|
| Structure | Organization, organization type, parish, Sunday School, location | A coordinating unit can supervise several lower units; a school is associated with its parish; geography is separate from church reporting structure |
| Identity | Person, user account, contact, guardian relationship | A person need not have a login; guardian-to-child is many-to-many |
| Access | Role, permission, scoped role assignment | A person/account can hold several roles in different organizations and periods |
| Governance | Committee, committee type, appointment, governing body | A committee belongs to an organizational scope; people serve through dated appointments |
| Membership | School membership, membership status/history, transfer request | A person's membership history survives class changes and transfers; concurrent membership rules remain open |
| Education | Curriculum version, grade level, subject, curriculum subject, academic year, class offering, enrollment | Curriculum grade differs from a specific class in a particular school/year; enrollment preserves learning history |
| Instruction | Teaching assignment, lesson, class session, attendance record | Teachers can have multiple assignments; attendance refers to a scheduled occurrence and an eligible enrollment |
| Assessment | Assessment, result, promotion decision | Results belong to a defined assessment; promotion is a recorded decision, not just an overwritten current-grade field |
| Service | Service group, group membership, activity, participation | Choir or volunteer participation can coexist with classroom enrollment |
| Coordination | Reporting cycle, report submission/version, review | Reports have an organization, period, status, reviewer, and preserved submitted figures |
| Events | Assembly/event, delegate appointment, registration, attendance, resolution, action item | A delegate is a person representing an organization for a particular event; decisions can have follow-up owners |
| Resources | Document, version, language variant, publication scope | A document can have editions and translations with controlled visibility |
| Optional later | Training event, training participation, project, contribution, receipt | Keep specialized records distinct; add only where approved workflows need them |

## Relational design principles to carry forward

These are proposed engineering rules rather than facts from GSSU's website:

1. Use primary keys and foreign keys for relationships; do not store comma-separated teacher, subject, or committee-member IDs.
2. Represent many-to-many relationships with explicit association records that can carry dates, scope, status, and responsibility.
3. Separate stable definitions (grade, subject, role) from occurrences (this year's class, this week's session, this assembly's delegate).
4. Store historical enrollment, appointment, and transfer records rather than overwriting the past.
5. Define uniqueness at the correct scope: class codes per school/year; one attendance record per enrollment/session; results per assessment/enrollment when only one attempt is allowed.
6. Ensure linked records belong to compatible schools and periods. A foreign key to an existing class alone does not prove that class belongs to the intended school.
7. Use transactions for linked operations such as transfer acceptance and promotion. Establish deletion and retention rules before destructive operations.
8. Prevent cycles and invalid parent types in the organization hierarchy. Define how reorganizations affect historical reporting.
9. Treat names and shared phone numbers as unsuitable unique person identifiers. Duplicate resolution requires review.
10. Preserve the calculation rules or report versions needed to explain previously approved totals.

## Open questions before final requirements/schema

- Who is sponsoring the pilot: national/global GSSU, Addis Ababa Unity, a diocese, or an independent proposal?
- Which organizational and committee structures actually operate in the pilot schools?
- Is there one Sunday School per parish, or can a parish have several programs/branches?
- Can a person be an active member in more than one school? How do temporary visits differ from transfers?
- Which ages, curriculum levels, assessments, and promotion rules apply locally?
- What are the real reporting forms and approval steps? Which student details may higher offices access?
- Which existing software/database projects are operational, and what exports or integration access exist?
- What do the current approved bylaws say about assemblies, terms, representation, and authorization?

## Testing-policy impact

The owner requires manual functional testing. Future requirements will use manually checkable acceptance criteria. The backlog will contain manual verification handoffs and owner-reported issues, with no automated test-suite work. See [manual testing and acceptance](../09-manual-testing-and-acceptance.md).
