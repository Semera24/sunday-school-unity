# Gemini / Antigravity project entry

Read [AI_START_HERE.md](AI_START_HERE.md), [AGENTS.md](AGENTS.md), [PROJECT_STATUS](docs/PROJECT_STATUS.md) and [collaboration workflow](docs/13-ai-collaboration-workflow.md) before acting.

## Current assignment: read-only onboarding

You are the proposed implementer. The owner and the reviewing assistant will assess your understanding report before authorizing the first implementation task. Do not infer permission to execute TASK-006 from the backlog or generic continuation prompt.

- Inspect documents, manifests, relevant source, and repository state only.
- Do not modify files, write the report to disk, install dependencies, run application/package scripts, create an environment/key/database, migrate, seed, commit, push or deploy.
- Return the onboarding report in chat using the structure in the workflow document. Include concrete source paths and distinguish observed facts from document claims and proposals.
- No automated test suites at any stage; the owner manually tests. Syntax/type/build checks may be used only in later authorized implementation tasks.
- Never reveal credentials, keys, tokens or personal data in a report. Do not inspect `.env` contents for this onboarding.
- Preserve `RefernceBoilerPlate/` unchanged; it is not the application root.

For later authorized coding work: implement only the specified task and report using [task report template](docs/templates/implementation-report.md). Stop after reporting; wait for review before the next task. These instructions apply to any implementing AI, not only Gemini.
