# Manual testing and acceptance

Status: testing policy accepted by the project owner; checklist structure proposed.
Date: 14 November 2026 (Africa/Addis_Ababa).

## Policy

The owner will manually test the application and report problems. Automated unit, feature, integration, end-to-end, browser, snapshot, regression, and coverage testing are excluded from the project unless the owner explicitly changes this instruction.

Agents must not introduce automated test suites, test dependencies, coverage targets, TDD tasks, or test execution in CI. This supersedes earlier conversational suggestions about automated testing. The previously proposed `docs/09-testing-and-acceptance.md` is replaced by this document.

Builds and proportionate syntax/type checks may establish that code compiles; they do not prove that a workflow works. User acceptance is manual. Database foreign keys, uniqueness rules, transactions, server-side validation, permissions, and error handling still belong in the product.

## Handoff for each feature

Provide a concise description of the change, setup or sample data required, manual steps, expected results, and known limitations. Include role and school boundaries when relevant. Keep instructions short and specific to the delivered change.

Suggested acceptance record:

| Feature/task | Implementation | Manual acceptance | Owner feedback | Resolution |
|---|---|---|---|---|
| Example only | Implemented / in progress | Pending / passed / issue found | Reproduction details | Fix reference and retest status |

“Implemented” does not mean “manually accepted.” An agent must leave acceptance pending until the owner supplies the result.

## Manual checks to develop with the requirements

These are future checklist categories, not claims that these features are implemented:

- Account access and permissions at each organizational scope.
- Student registration, duplicate review, enrollment, and transfer.
- Attendance entry, correction, and repeat submission.
- Assessment results and promotion rules.
- Report totals, reporting periods, missing submissions, and exports.
- Amharic/English rendering, mobile screens, and Ethiopian-calendar input/display.
- Interrupted connections and clear save status.
- Deployment, backup restoration, and administrator handover.

The project backlog will track implementation and manual acceptance separately. No automated test tasks will be added.
