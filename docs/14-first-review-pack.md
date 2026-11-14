# የመጀመሪያ ግምገማ ማሸጊያ / First-review pack

Prepared 14 November 2026. Scope: current local implementation through TASK-029. Working name: Sunday School Unity. Official sponsorship/branding remains unconfirmed.

**ሁኔታ፦ ለመጀመሪያ ግምገማ የቀረበ ስሪት ነው፤ ሙሉ የታቀደው MVP አልተጠናቀቀም። የባለቤቱ የእጅ ማረጋገጫ በመጠባበቅ ላይ ነው።**

**Status: a first-review candidate for hierarchy and membership management. The broader proposed MVP is incomplete. Owner manual acceptance remains Pending.** Do not label this a production-ready national platform or imply measured adoption/performance results.

## Deliverables / ሰነዶች

- [Editable bilingual PowerPoint](../output/presentation/sunday-school-unity-first-review-am-en.pptx) - 15 slides; Amharic first, English alongside.
- [Presentation as PDF](../output/pdf/sunday-school-unity-first-review-slides-am-en.pdf) - 15 slides, embedded Ethiopic font; preferred for sharing without font installation.
- [Bilingual application manual](../output/pdf/sunday-school-unity-app-manual-am-en.pdf) - 15 pages; scope, actors, local setup, member registration, invitations/link approval, lifecycle, department periods, self-service, reporting boundaries and manual review checklist.
- [Editable content source](review/source/review-content.json) and [document builder](review/source/build_review_pack.py).

PPTX text and diagram objects are editable. Install the bundled [regular](../output/presentation/fonts/NotoSansEthiopic-Regular.ttf) and [bold](../output/presentation/fonts/NotoSansEthiopic-Bold.ttf) fonts for consistent PowerPoint appearance. Font license is included beside them. PDFs embed the font. The Amharic copy is a draft for owner/native-speaker review; language review is not recorded as completed.

## Feature inventory / የባህሪያት ዝርዝር

| Area / ክፍል | Current implementation / ያለው ትግበራ | Review boundary |
|---|---|---|
| መዋቅር / Hierarchy | Union, dioceses, districts, schools; separate departments/divisions/boards and city/church context | Production governance details still need stakeholder confirmation |
| ፈቃድ / Access | Scoped appointments, active-scope switcher, union/local/technical separation | Source corrections delivered; full functional/security acceptance Pending |
| አባላት / Members | Local list/search/filters/pagination, create/edit, without-login records, local duplicate warnings | No automatic identity merge or complete cross-school claims/transfers |
| መለያ / Accounts | Invitation-only acceptance, lead-reviewed links, revoke/resend/delivery status | Development delivery through Mailpit; production recovery/delivery not complete |
| ሁኔታ / Lifecycle | Approve/reject/suspend/archive/reactivate; decision history | Archive preserves records; no general destructive delete workflow |
| ክፍሎች / Departments | Local units, coordinators, multiple member participation periods and ended history | Activity/attendance module remains planned |
| የግል መረጃ / Self-service | Own permitted profile fields, status and assignments | Official status/scope/appointments are not self-editable |
| ሪፖርቶች / Reports | Demo submission, local-lead release, scoped released views and summaries | Full templates/cycles/revisions/return workflows remain planned |
| ቋንቋ / Languages | Amharic default, English secondary, UTF-8 original text | Inherited technical/recovery screens are not fully localized; calendar UX deferred |
| ማስተናገጃ / Operations | Local PostgreSQL and built application assets | Hosting, backups/recovery/deployment and pilot acceptance remain planned |

## First-review agenda / የግምገማ አጀንዳ

1. Explain the purpose, scope and hierarchy using the presentation.
2. Compare `union-admin@example.test`, `lead-a@example.test` and `tech-admin@example.test`. Passwords stay in the ignored local credentials file, never in this shareable pack.
3. Demonstrate local registration without a login, invitation through Mailpit, pending acceptance, lead approval and member self-service.
4. Demonstrate denied cross-school/private-record access, mixed-text preservation, a lifecycle restriction and ended/new department periods.
5. Record account, active scope, page/action, expected and actual result. Use view-only scenarios before mutations; do not reset the DB to repeat the demo.
6. Agree fixes, real organizational terminology, pilot participants and next milestone. Owner acceptance remains Pending until explicitly confirmed.

## Preparation and evidence

Fresh application checks during pack preparation: `npm run build` and `npm run types:check` exit 0; `composer validate --strict --no-check-publish` passed; PHP syntax checks passed for 43 changed/new PHP files. No automated functional tests run. Build emitted existing npm configuration/deprecation notices and a public-font runtime-path warning; the referenced font file is present under public/fonts.

Document checks: both PDFs rendered and all 30 pages/slides visually inspected; native PPTX package and geometry validators reported no findings. macOS Quick Look generated a readable PPTX cover preview. PowerPoint itself is unavailable, so this is not a claim of full native PowerPoint rendering validation. The supplied presentation runtime/Artifact Tool loader was unavailable on this host; the fallback builder writes editable OOXML directly (not python-pptx) and produces matching PDFs from the same content/layout. PDF tooling is isolated under /tmp, not installed into the app.

Sources: [checkpoint](PROJECT_STATUS.md), [backlog](08-backlog.md), [roles](03-roles-and-permissions.md), [language/data policy](requirements/language-and-data.md), [routes](../routes/web.php) and [TASK-029 report](reports/2026-11-14-membership-completion-pass.md). These are implementation records, not measured field outcomes.
