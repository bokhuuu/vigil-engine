# 🛡️ Vigil Engine

**Quality control and automation engine for Colliers Georgia's large-scale property expropriation project.**

Vigil tracks 1,500+ property ACTs (inventory forms), coordinates field teams, automates Google Drive file detection and ensures no document passes to the master Excel incomplete. It combines an Airtable database, a Laravel automation engine, Telegram notifications, a Google Drive watcher and a business web portal into one cohesive system.

---

## What It Does

- **Tracks every ACT** - each physical property form is entered into Airtable with its status, responsible teams and missing documents
- **Notifies teams automatically** - every evening at 20:00, each team receives their personal incomplete ACT list via Telegram
- **Watches Google Drive** - detects file uploads/deletions in ACT subfolders and auto-ticks Airtable checkboxes; sets folder colors (🟢/🟡/⬜)
- **Creates Drive folders** - automatically creates the correct subfolder structure per ACT based on property flags
- **Monitors correspondence** - government letters tracked with 5-day response deadlines; overdue items flagged daily
- **Handles form submissions** - when a team submits an update form, they receive a Telegram link to their full submission history 15 minutes later
- **Alerts managers** - daily summaries of ACT progress and correspondence status sent to management
- **Business portal** - separate web UI for business records linked to ACTs; supports Drive file uploads directly from the show page
- **Excel exports** - styled `.xlsx` exports for ACTs and Business records

---

## Architecture

Airtable (source of truth)
↕ API
Laravel Engine (automation brain)
↕ HTTP ↕ Google Drive API
Telegram Bot (delivery) Drive Watcher + Apps Script

Airtable Form → Webhook → Engine → 15-min delay → Telegram
Apps Script → Webhook → Engine → tick Airtable checkbox
Drive Changes → Engine Watcher → tick checkbox + set folder color
Airtable Auto (ACT complete) → Engine → verify Drive → notify

**Stack:** Laravel 13, PHP 8.4, Airtable API, Telegram Bot API, Google Drive API  
**Auth:** Webhook secret (HMAC), Basic Auth (dashboard), `auth('business')` guard (business portal)  
**Server:** Vultr VPS Frankfurt `45.32.154.101`, Caddy + PHP-FPM  
**URL:** `https://vigil.45-32-154-101.nip.io`  
**CI/CD:** GitHub Actions - test + lint + deploy on push to main (no `cache:clear` on deploy)

---

## Scheduled Commands

| Command                                 | Schedule      | Purpose                                                    |
| --------------------------------------- | ------------- | ---------------------------------------------------------- |
| `vigil:send-update-reminders`           | Every minute  | Send განახლებები link 15 min after first form submission   |
| `vigil:drive-watch --dry-run`           | Every minute  | Engine Drive watcher - logs decisions, no writes (dry-run) |
| `vigil:drive-watch --seed`              | Every hour    | Rebuild folder+subfolder index from Airtable+Drive         |
| `vigil:create-drive-folders --limit=25` | Every 15 min  | Create folders for new ACTs + sync recently changed        |
| `vigil:correspondence-overdue`          | Mon–Fri 11:00 | Mark overdue government letters, alert                     |
| `vigil:deadline-warning`                | Mon–Fri 11:00 | Team commitment deadline warnings                          |
| `vigil:sector-deadline`                 | Mon–Fri 11:00 | Sector completion deadline warnings                        |
| `vigil:act-alert`                       | Mon–Fri 20:00 | Incomplete ACT alerts to all teams and managers            |
| `vigil:backup`                          | Sunday 11:00  | JSON backup to Backblaze B2                                |

---

## Webhooks

| Endpoint                     | Auth           | Purpose                                                        |
| ---------------------------- | -------------- | -------------------------------------------------------------- |
| `POST /webhook/airtable`     | Webhook secret | Airtable form submission - schedules Telegram reminder         |
| `POST /webhook/drive`        | Webhook secret | Apps Script file detection - ticks/unticks Airtable checkboxes |
| `GET /webhook/act-statuses`  | Webhook secret | Bulk signature status for Apps Script (cached 60s)             |
| `POST /webhook/act-complete` | Webhook secret | ACT marked complete - engine verifies Drive, notifies          |

---

## Routes

POST /webhook/airtable - Airtable form submission webhook
POST /webhook/drive - Apps Script file detection webhook
GET /webhook/act-statuses - Bulk signature status for Apps Script
POST /webhook/act-complete - ACT marked complete trigger
GET /dashboard - Dashboard (BasicAuth)
POST /dashboard/acts/{id}/folder - Create Drive folder immediately (BasicAuth)
GET /acts/export - Download ACT Excel export (BasicAuth)

GET /business/login - Business login form
POST /business/login - Business login submit
POST /business/logout - Business logout
GET /business - Business list (auth:business)
GET /business/create - Create form (auth:business)
POST /business - Store business (auth:business)
GET /business/{id} - Business show page (auth:business)
PUT /business/{id} - Update business (auth:business)
DELETE /business/{id} - Delete business (auth:business)
POST /business/{id}/correspondence - Add correspondence (auth:business)
DELETE /business/{id}/correspondence/{c} - Delete correspondence (auth:business)
GET /business/upload/folders - List Drive subfolders for upload modal (auth:business)
POST /business/upload - Upload files to Drive subfolder (auth:business)
GET /business/export - Download Business Excel export (auth:business)

---

## Services

- **AirtableClient** - paginated GET/PATCH/POST; retries 3× on 5xx/429/timeout; throws `AirtableException` (never returns partial data)
- **AirtableService** - all Airtable domain queries (incomplete ACTs, teams, sectors, deadlines, correspondence, checkboxes)
- **TelegramService** - `send()`, `sendToMe()`, `sendToGroup()`
- **GoogleDriveService** - Drive API via service account + OAuth (`zura.colliers.bypass@gmail.com`). Manages folder creation, subfolder listing, file upload, color setting, and change feed
- **DriveEventService** — single source for tick/untick/alert logic; used by both the webhook and the engine watcher
- **DriveWatcherService** — engine-based Drive watcher; state transitions, grace timers, folder colors, auto-indexing
- **DriveChangesReader** — reads Drive changes feed via OAuth; returns changed item IDs
- **DriveWatcherStateRepository** — JSON file state store (`storage/app/google/drive-watcher-state.json`)
- **ActRequirements** — config-driven single source of truth for required doc fields and subfolders per ACT
- **ActCompletionService** — verifies a completed ACT against Airtable + Drive; notifies on success, warns Zura only on mismatch
- **ManagerNotifier** — sends one message to Zura + all managers with Telegram IDs

---

## Controllers

| Controller                  | Routes                                                  | Purpose                                   |
| --------------------------- | ------------------------------------------------------- | ----------------------------------------- |
| `WebhookController`         | `POST /webhook/airtable`                                | Update reminder scheduling                |
| `DriveWebhookController`    | `POST /webhook/drive`, `GET /webhook/act-statuses`      | Drive file events                         |
| `ActCompleteController`     | `POST /webhook/act-complete`                            | ACT completion verification               |
| `DashboardController`       | `GET /dashboard`                                        | Dashboard (Basic Auth)                    |
| `DashboardFolderController` | `POST /dashboard/acts/{id}/folder`                      | Instant folder creation                   |
| `ActExportController`       | `GET /acts/export`                                      | Styled ACT Excel export                   |
| `BusinessController`        | `/business/*`                                           | Business CRUD (auth:business)             |
| `BusinessUploadController`  | `GET /business/upload/folders`, `POST /business/upload` | Drive file upload from business show page |
| `BusinessExportController`  | `GET /business/export`                                  | Styled Business Excel export              |

---

## Google Drive Integration

**Drive watcher runs in two modes (currently dual):**

| System         | Mode                    | Account                          |
| -------------- | ----------------------- | -------------------------------- |
| Apps Script    | **Active** — real work  | `zura.colliers.bypass@gmail.com` |
| Engine watcher | **Dry-run** — logs only | Same account via OAuth           |

Apps Script runs every 5 minutes (change-only). Engine watcher runs every minute and logs what it _would_ do — no Airtable changes, no color writes. When logs match, the Apps Script trigger is deleted and `--dry-run` flag is removed from the schedule.

**Folder colors (set by Apps Script, visible because same account):**

- Subfolder has files → 🟢
- Subfolder empty → ⬜
- ACT root: ALL tracked subfolders have files + ✅ ხელმოწერები → 🟢; partial → 🟡; empty → ⬜

**Auto-ticked Airtable checkboxes:**
`📥 ფოტოები`, `📥 შიდა ნახაზები`, `📥 გამიჯვნები`, `📥 ხარჯთაღრიცხვა`, `📥 ტოპო ნარგავები`

**`📥 ხელმოწერები`** — manual only (physical signature, no Drive folder).

> ⚠️ **Known bug:** If files are deleted from an already-green subfolder, Apps Script does not revert the color or untick the Airtable checkbox. Workaround: manual. Auto-fixed once engine watcher goes live (its 5-min grace + `fileRemoved` path handles this).

---

## Business Portal

A separate web UI (`/business/`) for businesses linked to ACTs.

- **Guard:** `auth('business')` — independent from dashboard Basic Auth
- **Show page:** inline editing, correspondence card, Drive link, ⬆️ upload button, Excel export button
- **Upload modal:** loads Drive subfolders via AJAX; multi-file picker; uploads via `fetch()` + `FormData`; auto-closes 1.5s after success; `Cache::lock` per business+filename prevents duplicate uploads on simultaneous requests

---

## Excel Exports

Both exports use `maatwebsite/excel` — styled with bold header row (dark blue `#1F3864`, white text), alternating row shading, freeze row, auto-column widths, and clickable URL columns.

| Export          | Route                  | Auth          | File                     |
| --------------- | ---------------------- | ------------- | ------------------------ |
| ACT Export      | `GET /acts/export`     | Basic Auth    | `acts-{date}.xlsx`       |
| Business Export | `GET /business/export` | auth:business | `businesses-{date}.xlsx` |

**ACT columns:** ACT №, სტატუსი, პასუხისმგებელი, გუნდი, სექტორი, რა აკლია, property flags (✅/❌), doc checkboxes (✅/❌), კვლევა, Drive საქაღალდე

**Business columns:** სახელი, სარეგ. კოდი, ტიპი, სტატუსი, ACT, ლოტი, მეპატრონე, ტელ., ელ-ფოსტა, ბუღალტერი, ტელ., ელ-ფოსტა, Drive, შენიშვნები

---

## Dashboard

**URL:** `https://vigil.45-32-154-101.nip.io/dashboard`  
**Auth:** Basic Auth — `DASHBOARD_USER` / `DASHBOARD_PASSWORD`

Features: ACT stats cards, instant JS search (normalises spaces around hyphens), filters (status / missing doc / კვლევა / ბიზნესი), paginated table (100/page), ⚠️ badge when status=done but doc checkboxes incomplete, `+ შექმნა` button for instant folder creation, last-good fallback with stale banner on Airtable failure.

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

**Required `.env` values:**

AIRTABLE_TOKEN=
AIRTABLE_BASE_ID=
TELEGRAM_BOT_TOKEN=
TELEGRAM_MY_CHAT_ID=
TELEGRAM_GROUP_CHAT_ID=
WEBHOOK_SECRET=
GOOGLE_DRIVE_CREDENTIALS=
GOOGLE_DRIVE_ACT_FOLDER_ID=
GOOGLE_DRIVE_OAUTH_CLIENT=
GOOGLE_DRIVE_OAUTH_TOKEN=
DASHBOARD_USER=
DASHBOARD_PASSWORD=
SENTRY_LARAVEL_DSN=
B2_KEY_ID=
B2_APPLICATION_KEY=
B2_BUCKET=
B2_REGION=
B2_ENDPOINT=

**Server permissions (after deploy):**

```bash
chown -R www-data:www-data /var/www/vigil-engine/storage
chmod -R 775 /var/www/vigil-engine/storage
chmod -R 777 /var/www/vigil-engine/storage/framework/cache/data
```

**OAuth setup (one-time, already done on server):**

```bash
php artisan vigil:drive-auth
```

---

## Development Workflow

```bash
# Run tests (on host, not inside Docker)
php artisan test

# Fix code style
./vendor/bin/pint

# Then push
git push
```

Tests cover all commands, webhook controllers, Airtable client, Telegram service, and Drive services. Every push triggers CI — tests must pass before deploy.

---

## Project Context

Vigil supports a Georgian Road Department property expropriation project managed by Colliers Georgia. Field inspectors physically survey 1,000+ properties, produce inventory ACTs, and coordinate with surveyors, agronomists, and cost estimators. Vigil is the quality gate — nothing reaches the final Excel without passing through it.

---
