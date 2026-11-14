# TASK-026 — Duplication and unnecessary-file cleanup audit

Owner requested parallel cleanup preparation while the reviewer sets up local PostgreSQL. First deliverable is a read-only candidate report. No deletion or refactoring is authorized by this brief until the reviewer identifies the exact safe changes from that report.

Read AI_START_HERE, AGENTS and PROJECT_STATUS. The repository has uncommitted reviewer changes: preserve them. Do not repeat domain research or initial onboarding.

## Inspect

- Exact duplicate files, redundant unused boilerplate assets, obsolete product-name references and contradictory current instructions.
- Distinguish intentional duplication (English/Amharic translation keys, example/live environment, entry-point summaries linking authoritative requirements) from redundant copies.
- For each candidate provide path, reason, references/imports/route use checked, proposed action and impact. A similar filename alone does not prove redundancy.
- Identify inherited features outside current scope separately; do not remove them merely because they are unused in the future domain UI.
- Explicitly exclude reference directories, live/runtime/generated data, secrets, lock files, vendor/node_modules and Git internals from cleanup candidates. Do not read .env or credentials. Use .env.example for configuration shape.

## Boundaries

No writes, deletes, installs, formatting, migrations, seeds, service/database changes, commits, pushes or automated tests. Do not edit AGENTS, AI_START_HERE, GEMINI, PROJECT_STATUS, backlog or the reviewer's localization/configuration files. Return report in chat only. This audit must not interfere with the hierarchy/access milestone.

## Report

Return a short table: candidate path(s), evidence of redundancy, proposed keep/remove/merge, risk and dependent references. Also list items inspected but intentionally retained. Wait for a bounded cleanup implementation assignment after reviewer assessment.
