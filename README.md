# BartaFlow

**Automated WhatsApp Notifications for Modern Businesses**

A multi-tenant SaaS platform for businesses to send transactional and reminder messages over WhatsApp. Built with Laravel 12, Blade, Alpine.js, and Tailwind CSS.

## Features

### Implemented
- **Authentication** — Register, login/logout, email verification, password reset, rate limiting
- **Workspaces & Multi-Tenancy** — Auto-provisioned on signup, workspace-scoped data, global `WorkspaceScope`
- **Team Management** — Role-based access (Owner/Admin/Staff), email invitations with token expiry, role management
- **WhatsApp Accounts** — Connect/verify via Cloud API, encrypted token storage, test messaging, health-check command
- **Contacts & Tags** — CRUD with E.164 phone normalization, per-workspace uniqueness, tags, search/filter, CSV import via background job, opt-out handling

### Planned (Phase 1)
- Templates — Reusable messages with `{{variable}}` system, live preview, TemplateRenderer
- Messaging — Compose, queue, SendMessageJob, retry policy
- Scheduling — Create/cancel, per-minute dispatch via cron
- Message Logs — List, filter, detail view, manual retry
- Dashboard — KPIs, onboarding checklist, recent activity
- Audit & Security — Activity logging, 2FA
- Deployment — VPS provisioning, Supervisor, cron, SSL, monitoring

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4+, MySQL (SQLite for local dev)
- **Frontend:** Blade, Alpine.js, Tailwind CSS v4, Vite
- **Queue:** Database driver (MVP); Redis-ready
- **Testing:** Pest PHP, PHPStan, Pint

## Getting Started

### Prerequisites
- PHP 8.4+
- Composer 2
- Node.js 20+
- SQLite (local) or MySQL 8

### Installation
```bash
git clone <repo-url> bartaflow
cd bartaflow
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
```

### Demo Login
- **Email:** `admin@email.com`
- **Password:** `password`

A "Demo Login" button is available on the login page for quick access.

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
```

## Architecture

- **Single-database multi-tenancy** with `workspace_id` column on all tenant tables
- **Action pattern** — Single-purpose classes (`CreateContactAction`, etc.) for business logic
- **Service layer** — `WhatsAppClient` interface + `CloudApiWhatsAppClient` / `FakeWhatsAppClient`
- **Defense-in-depth** — Auth, workspace middleware, global scope, policies, form request authorization
- **Queue workers** — `whatsapp`, `imports`, and `default` queues for async processing

## Project Status

**Current: 118 passing tests.** Tasks 001–005 complete. Working through MVP roadmap (tasks 006–012).

See `docs/` for full specification: PRD, architecture, database design, permissions, frontend guide, and task breakdown.
