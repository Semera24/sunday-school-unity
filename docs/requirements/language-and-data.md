# Amharic-first interface and multilingual data

Updated 14 November 2026. Owner decision: Amharic is primary; English is secondary. Implementation choices below distinguish interface language from stored business data.

## Accepted direction

- Default interface and new accounts: Amharic (`am`). English (`en`) is selectable in profile settings. Preserve existing explicit English preferences.
- Amharic is the normal language for entry, labels, forms and reports. Accept English and mixed text as well; do not enforce an Ethiopic-only or ASCII-only alphabet.
- One PostgreSQL database stores Unicode text in UTF-8. Do not create separate English and Amharic databases or duplicate member records for language purposes.
- Never translate, transliterate or replace a submitted name/report just because the user switches interface language. Laravel translates interface messages; it does not automatically translate submitted data.
- No automated test suites. Owner manually accepts both languages and access boundaries.

## Storage design guidance (proposed until domain schema is reviewed)

| Data | Storage/display approach |
|---|---|
| Personal names, descriptions, submitted reports | Preserve original entered text in normal `varchar`/`text` columns; Amharic-first prompts. Optional separate English spelling only where users need it, never an invented translation. |
| Organization/department/template names maintained by administrators | Proposed explicit `name_am` plus optional `name_en`; primary Amharic name. English display falls back to Amharic when absent. Confirm imported English-only legacy data policy during import design. |
| Status/type/permission identifiers | Stable internal codes such as `pending`, independent of language; translate their visible labels. Never use translated labels as authorization keys. |
| Contacts, email addresses, identifiers | Field-specific validation; translating an email/identifier is not meaningful. Do not require all technical values to be Amharic. |
| User language preference | `users.locale`: `am` or `en`; independent of the language used in a report. |
| Dates/numbers | Store database-native dates/timestamps/numeric values. Locale is separate from Ethiopian-calendar support; calendar UX and export formats still need agreement. |

Avoid a generic translation table or JSON translations on every field at this stage. Add multilingual columns only for genuinely bilingual reference data. Do not add translation packages/services for this foundation.

## PostgreSQL operating requirements

`DB_CONNECTION=pgsql`, `DB_CHARSET=utf8`; this config describes the connection and does not convert/create an existing database. During dedicated local DB setup verify `SHOW server_encoding;` and `SHOW client_encoding;` report UTF8. Verify encoding on backup/restore and CSV exchange as well.

UTF-8 enables storage, not automatic translation, Amharic stemming, spelling equivalence or culturally correct sort order. Choose and record database collation when provisioning; confirm available provider/ICU support before promising Amharic sorting. Design case-insensitive English search deliberately (e.g. bound PostgreSQL `ILIKE`); preserve Amharic text and do not apply destructive search normalization to stored data. Global search must first respect organizational permissions.

CSV should use UTF-8 and be manually checked with actual spreadsheet software. Amharic PDF rendering requires a compatible embedded font/shaping engine and separate manual acceptance; browser font support alone does not verify PDF output.

## Foundation implemented in this session

- `am` default, `en` fallback; registered language options; existing profile preference flow reused.
- Shared translation catalogues merge English fallback per key; `useT` supports placeholders.
- Login labels, profile form/settings navigation, and main navigation translated.
- Self-hosted Noto Sans Ethiopic font with OFL license, alongside the existing Latin font.
- New migration changes only the default locale; does not rewrite existing users. Eloquent supplies the configured default for new users created without a locale.
- PostgreSQL selected as config default; example connection explicitly UTF-8.

Not yet completed: remaining inherited admin/auth screens, validation/flash/notification/export messages, account deletion dialog, guest language selector, translated system role display names, calendar UX and PDF rendering. Amharic copy requires owner/native-speaker review. No database migration, dependency installation, build or browser verification has occurred in this session.

## Manual acceptance after safe runtime setup

1. Open login without signing in: translated labels appear in Amharic; Ethiopic characters render on phone and desktop.
2. New account without an explicit preference defaults to Amharic. An existing explicitly English account stays English.
3. In profile settings select English and save; profile/main navigation change to English and remain so after reload/login. Switch back to Amharic.
4. Save a mixed name such as `ሐና Hanna ደሞ`; reload and switch languages: exact submitted text remains unchanged. Use only fictional records.
5. Check a missing Amharic interface key with an existing English key displays English; do this by temporary local inspection/manual development change, not an automated suite.
6. At DB setup, inspect encoding as above. Later feature tasks must manually exercise mixed text through their own CRUD/import/export paths.

Sources: [Laravel localization](https://laravel.com/framework/docs/13.x/localization), [PostgreSQL character sets](https://www.postgresql.org/docs/current/multibyte.html), [Google Fonts Noto Sans Ethiopic source/license](https://github.com/google/fonts/tree/main/ofl/notosansethiopic).
