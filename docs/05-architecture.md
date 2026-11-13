# Architecture

Status: working design; application foundation copied, domain design not implemented.

## Direction

One Laravel application with Inertia/Vue pages and PostgreSQL. Separate logical school workspaces share a deployment, while explicit publication enables collaboration. Keep modules coherent inside the application; no microservices or separate frontend API deployment is required for the MVP.

Framework target is Laravel 13 on PHP 8.5. Inertia 2 is retained initially to avoid coupling a frontend-major migration to the framework update. Vue 3, TypeScript, Tailwind 4 and Wayfinder come from the foundation. See project status for exact resolved versions and installation state.

## Request and authorization flow

Laravel routes -> authentication -> active organizational context -> Form Request validation and policy -> scoped query/service/transaction -> Inertia response. Browser components improve usability but cannot grant authority. Context switching selects an already-authorized scope; it never creates access.

Keep domain work in existing `app/Models`, `app/Policies`, `app/Http/Controllers`, `app/Http/Requests` and focused services under `app/Support` until a justified structure change is documented. Vue pages/components follow existing conventions. Do not force business memberships into a single `users.role` or global settings record.

## Modules

Identity/access; organizational structure; members/profiles/forms; departments/activities; reporting; shared information/resources. Reuse audit, media, notification, import and export services only after scope adaptations. Register each reuse decision in the foundation register.

## Storage and jobs

Use PostgreSQL relationships and transactions for domain consistency. Private file storage must be durable and outside ephemeral application filesystems when deployed. Queue long imports/exports or notification fanout when necessary; provide explicit failure/status records. A server-side framework does not supply offline synchronization automatically.

## Frontend and internationalization

Inertia/Vue remains in the Laravel repository. SSR is initially disabled. Batch attendance/activity/form interactions where practical; paginate large collections. Extend existing localization beyond English and remove hard-coded user-facing strings. Ethiopian-calendar entry/display and Ethiopic print fonts need deliberate implementation and owner review.

## Operational choices

Laravel Cloud is the proposed managed pilot host if payment/location requirements allow; ZERGAW is the local candidate. No provider selected or account created. Keep database close to application hosting. Configuration/secrets are environment-owned; never copy reference keys/databases.

## Known foundation limitations

Global RBAC/query scope, role escalation path, API login parity, import transactions/failure history, export scope/memory, localization completeness. These are recorded work, not resolved by dependency upgrades. Do not expose inherited token endpoints for real users until addressed. Keep generic routes local/development-only in practice until a proper access implementation is delivered.

## Alternatives and rationale

Blade/Livewire would reduce browser application code but Vue fits richer member/coordinator interactions. ASP.NET Core and a TypeScript backend remain viable alternatives; Laravel/Inertia reuse minimizes reimplementation here. A fresh Laravel 13 starter would offer current defaults but would require reintegrating the custom modules; the owner authorized the boilerplate-based start.
