# Operations and local setup

Status: proposed procedures; no deployment or database configured.

## Current prerequisites

Target PHP 8.5, Composer, Node 22+ compatible with the locked frontend toolchain, npm and PostgreSQL. Use the status checkpoint for what has actually been resolved/installed. Inertia SSR is initially disabled.

## Safe local setup — future execution

1. Read AGENTS and the status checkpoint. Inspect Composer/npm scripts before invoking them.
2. Install approved locked dependencies with `composer install --no-interaction` and `npm ci`. These can execute package scripts; inspect their effects first.
3. Copy `.env.example` into an untracked `.env`, generate a unique app key, and configure an exclusively dedicated Sunday School Unity database and user. Never reuse the reference database/key.
4. Review migrations and seeders. Generic demo personas are inherited development data, not approved production access. Do not seed real users or run `migrate:fresh` on an existing database.
5. After database identity is confirmed, run appropriate non-destructive migrations. A first local setup can use a new empty database. No production seeding is authorized by this document.
6. Build assets; use the project's PHP server/Herd plus Vite for development. Database cache/session/queue configuration needs a working database; build alone does not prove the app works.

Allowed proportionate checks: Composer validation, PHP syntax checks, frontend type checking and production asset build. No unit/feature/browser test suites. Provide the owner manual login/role/media/notification smoke steps and label results pending.

## Hosting decision

Laravel Cloud is a managed candidate; ZERGAW is an Ethiopian candidate; a VPS requires a named operator. Provider, payment method, region, costs, bandwidth, database service, backup retention and support are undecided. Obtain current pricing before procurement. No hosting subscription/domain or deployment is created by the current setup.

## Deployment checklist to finalize before pilot

Environment secrets; HTTPS/trusted proxies; database migrations/rollback strategy; durable private file storage; worker/scheduler supervision; email delivery; upload limits; monitoring/error logs; backup schedule/retention; restore procedure and responsible operator; deployment rollback and maintenance communication.

Application, database and files must be backed up consistently. Define recovery point/time targets with the owner, then demonstrate isolated restoration. Do not treat snapshots or an untested backup job as proof of recoverability.

## Operational boundaries

Do not publish inherited broad administration/token APIs to real users before scope defects are resolved. No scheduled automated tests. Keep production debug off, secrets outside Git and demo credentials confined to reviewed local seed paths. Plan operator handover and account recovery before real member onboarding.
