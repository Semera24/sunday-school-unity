# Amharic-first foundation implementation plan

Owner requests this change directly before the next Antigravity implementation assignment.

- [x] Default application/new accounts to `am`, English fallback; register both locales. Preserve existing explicit preferences.
- [x] Keep PostgreSQL as the default connection with UTF-8; document original-language data storage separately from UI translation. Do not create/change a database in this session.
- [x] Add matching translation catalogues, merge English fallback, translate login/profile/navigation entry points and provide Ethiopic font support. Record remaining inherited English screens explicitly.
- [x] Record onboarding review and prepare hierarchy-first manual demonstration brief with fictional accounts and backend access boundaries.
- [x] Run proportionate PHP syntax and diff checks; update handoff/backlog. No automated tests, dependency installs or runtime acceptance claims.

Architecture: existing Laravel localization shared through Inertia and `useT`; no translation service or additional localization dependency. Domain multilingual field design remains a documented proposal until schema work.

Manual verification after runtime setup: Amharic guest login; profile language change to English persists; switch back; mixed Amharic/English name survives save/reload. Hierarchy acceptance is a separate first domain milestone.
