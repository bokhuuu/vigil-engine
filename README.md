# 🛡️ Vigil Engine

**A Laravel automation engine for large-scale field inspection workflows — tracking property inventory forms, coordinating field teams, and automating document routing end-to-end.**

Vigil tracks 1,500+ property ACTs (inventory forms), coordinates field teams, automates Google Drive file detection, routes incoming client documents from email directly to Drive, and ensures no document passes to the master Excel incomplete. It combines an Airtable database, a Laravel automation engine, Telegram notifications, a Google Drive watcher, a Gmail automation engine, and a business web portal into one cohesive platform.

---

## What It Does

- **Tracks every ACT** — each physical property form is entered into Airtable with its status, responsible teams and missing documents
- **Notifies teams automatically** — every evening at 20:00, each team receives their personal incomplete ACT list via Telegram
- **Watches Google Drive** — detects file uploads/deletions in ACT subfolders and auto-ticks Airtable checkboxes; sets folder colors (🟢/🟡/⬜)
- **Creates Drive folders** — automatically creates the correct subfolder structure per ACT based on property flags
- **Monitors correspondence** — government letters tracked with 5-day response deadlines; overdue items flagged daily
- **Handles form submissions** — when a team submits an update form, they receive a Telegram link to their full submission history 15 minutes later
- **Alerts managers** — daily summaries of ACT progress and correspondence status sent to management
- **Automates incoming email** — watches Gmail for client emails, extracts ZIP/RAR attachments (preserving folder structure), uploads files to the correct Drive subfolder, and fires a Telegram alert
- **Business portal** — separate web UI for business records linked to ACTs; supports Drive file uploads and full correspondence round management
- **Excel exports** — styled `.xlsx` exports for ACTs and business records

---

## Architecture

```
Airtable (source of truth)
        ↕ API
Laravel Engine (automation brain)
        ↕ HTTP              ↕ Google Drive API      ↕ Gmail API
Telegram Bot (delivery)   Drive Watcher            Gmail Watcher
                          + Apps Script            → Extract & upload

Airtable Form  → Webhook → Engine → 15-min delay → Telegram
Apps Script    → Webhook → Engine → tick Airtable checkbox
Drive Changes  → Engine Watcher   → tick checkbox + set folder color
Gmail (unread) → Engine           → extract attachments → Drive → Telegram
```

**Stack:** Laravel 13, PHP 8.4, Airtable API, Telegram Bot API, Google Drive API, Gmail API  
**Auth:** Webhook secret (HMAC), session auth (dashboard), `auth('business')` guard (business portal)  
**Server:** Vultr VPS (Ubuntu 24.04), Caddy + PHP-FPM  
**CI/CD:** GitHub Actions — test + lint on push to `main`

---

## Scheduled Commands

| Command                                 | Schedule      | Purpose                                                  |
| --------------------------------------- | ------------- | -------------------------------------------------------- |
| `vigil:send-update-reminders`           | Every minute  | Send განახლებები link 15 min after first form submission |
| `vigil:drive-watch --dry-run`           | Every minute  | Engine Drive watcher — logs decisions, no writes         |
| `vigil:drive-watch --seed`              | Every hour    | Rebuild folder+subfolder index from Airtable+Drive       |
| `vigil:create-drive-folders --limit=25` | Every 15 min  | Create folders for new ACTs + sync recently changed      |
| `vigil:correspondence-overdue`          | Mon–Fri 11:00 | Mark overdue government letters, alert                   |
| `vigil:deadline-warning`                | Mon–Fri 11:00 | Team commitment deadline warnings                        |
| `vigil:sector-deadline`                 | Mon–Fri 11:00 | Sector completion deadline warnings                      |
| `vigil:act-alert`                       | Mon–Fri 20:00 | Incomplete ACT alerts to all teams and managers          |
| `business:watch-gmail`                  | Every minute  | Watch Gmail for incoming client emails, route to Drive   |
| `vigil:backup`                          | Sunday 11:00  | JSON backup to Backblaze B2                              |

---

## Webhooks

| Endpoint                     | Auth           | Purpose                                                        |
| ---------------------------- | -------------- | -------------------------------------------------------------- |
| `POST /webhook/airtable`     | Webhook secret | Airtable form submission — schedules Telegram reminder         |
| `POST /webhook/drive`        | Webhook secret | Apps Script file detection — ticks/unticks Airtable checkboxes |
| `GET /webhook/act-statuses`  | Webhook secret | Bulk signature status for Apps Script (cached 60s)             |
| `POST /webhook/act-complete` | Webhook secret | ACT marked complete — engine verifies Drive, notifies          |

---

## Routes

```
POST /webhook/airtable        Airtable form submission webhook
POST /webhook/drive           Apps Script file detection webhook
GET  /webhook/act-statuses    Bulk signature status for Apps Script
POST /webhook/act-complete    ACT marked complete trigger
GET  /dashboard               Dashboard (session auth)
POST /dashboard/acts/{id}/folder  Create Drive folder immediately
GET  /acts/export             Download ACT Excel export

GET  /business/login          Business login form
POST /business/login          Business login submit
POST /business/logout         Business logout
GET  /business                Business list (auth:business)
GET  /business/create         Create form (auth:business)
POST /business                Store business (auth:business)
GET  /business/{id}           Business show page (auth:business)
PUT  /business/{id}           Update business (auth:business)
DELETE /business/{id}         Delete business (auth:business)
POST /business/{id}/correspondence        Add correspondence round (auth:business)
DELETE /business/{id}/correspondence/{c} Delete correspondence round (auth:business)
GET  /business/upload/folders List Drive subfolders for upload modal (auth:business)
POST /business/upload         Upload files to Drive subfolder (auth:business)
GET  /business/export         Download Business Excel export (auth:business)
```

---

## Services

- **AirtableClient** — paginated GET/PATCH/POST; retries 3× on 5xx/429/timeout; throws `AirtableException` (never returns partial data)
- **AirtableService** — all Airtable domain queries (incomplete ACTs, teams, sectors, deadlines, correspondence, checkboxes)
- **TelegramService** — `send()`, `sendToMe()`, `sendToGroup()`
- **GoogleDriveService** — Drive API via service account + OAuth. Manages folder creation, subfolder listing, recursive file listing, file upload, color setting, and change feed
- **GmailService** — Gmail API via OAuth. Fetches unread messages, extracts attachments, applies labels, marks read/unread
- **DriveEventService** — single source for tick/untick/alert logic; used by both the webhook and the engine watcher
- **DriveWatcherService** — engine-based Drive watcher; state transitions, grace timers, folder colors, auto-indexing
- **DriveChangesReader** — reads Drive changes feed via OAuth; returns changed item IDs
- **DriveWatcherStateRepository** — JSON file state store (`storage/app/google/drive-watcher-state.json`)
- **ActRequirements** — config-driven single source of truth for required doc fields and subfolders per ACT
- **ActCompletionService** — verifies a completed ACT against Airtable + Drive; notifies on success, warns on mismatch
- **ManagerNotifier** — sends one message to all managers with Telegram IDs
- **BusinessEmailMatcher** — matches incoming emails to a registered business by subject and body patterns
- **BusinessDriveService** — creates and manages Drive folder structure for business correspondence rounds; lists incoming files recursively

---

## Controllers

| Controller                  | Routes                                                  | Purpose                                   |
| --------------------------- | ------------------------------------------------------- | ----------------------------------------- |
| `WebhookController`         | `POST /webhook/airtable`                                | Update reminder scheduling                |
| `DriveWebhookController`    | `POST /webhook/drive`, `GET /webhook/act-statuses`      | Drive file events                         |
| `ActCompleteController`     | `POST /webhook/act-complete`                            | ACT completion verification               |
| `DashboardController`       | `GET /dashboard`                                        | Dashboard                                 |
| `DashboardFolderController` | `POST /dashboard/acts/{id}/folder`                      | Instant folder creation                   |
| `ActExportController`       | `GET /acts/export`                                      | Styled ACT Excel export                   |
| `BusinessController`        | `/business/*`                                           | Business CRUD (auth:business)             |
| `BusinessUploadController`  | `GET /business/upload/folders`, `POST /business/upload` | Drive file upload from business show page |
| `BusinessExportController`  | `GET /business/export`                                  | Styled Business Excel export              |

---

## Gmail Automation Engine

The `business:watch-gmail` command runs every minute and processes incoming client emails end-to-end:

1. Fetches unread Gmail messages
2. Matches each email to a registered business (by subject code or body pattern)
3. Skips outgoing internal team emails; detects forwarded client replies via quoted-body regex
4. Extracts ZIP and RAR attachments — preserving internal folder structure
5. Uploads all files to the correct Drive subfolder (`მიმოწერა/{round}/შემომავალი ფაილები/{subfolder}`)
6. Creates a correspondence round record in the DB
7. Fires a Telegram alert with file count and business name
8. Marks the email as read and applies the `ბიზნეს მოდული` Gmail label

**Archive extraction:** uses PHP `ZipArchive` for ZIP and `unrar x` for RAR. Internal subfolder structure is mirrored exactly to Drive.

---

## Google Drive Integration

**Drive watcher runs in two modes:**

| System         | Mode                    | Description                        |
| -------------- | ----------------------- | ---------------------------------- |
| Apps Script    | **Active** — real work  | Runs in Google's cloud every 5 min |
| Engine watcher | **Dry-run** — logs only | Runs every minute, no writes       |

**Folder colors:**

- Subfolder has files → 🟢
- Subfolder empty → ⬜
- ACT root: ALL tracked subfolders have files + ✅ ხელმოწერები → 🟢; partial → 🟡; empty → ⬜

**Auto-ticked Airtable checkboxes:**
`📥 ფოტოები`, `📥 შიდა ნახაზები`, `📥 გამიჯვნები`, `📥 ხარჯთაღრიცხვა`, `📥 ტოპო ნარგავები`

**`📥 ხელმოწერები`** — manual only (physical signature, no Drive folder).

> ⚠️ **Known issue:** If files are deleted from an already-green subfolder, Apps Script does not revert the color or untick the checkbox. Auto-fixed once engine watcher goes fully live.

---

## Business Portal

A separate web UI (`/business/`) for businesses linked to ACTs.

- **Guard:** `auth('business')` — independent from dashboard session auth
- **Show page:** inline editing, correspondence rounds card, Drive link, upload button, Excel export
- **Upload modal:** loads Drive subfolders via AJAX; multi-file picker; uploads via `fetch()` + `FormData`; auto-closes after success; `Cache::lock` per business+filename prevents duplicate uploads
- **Incoming files:** lists all files from Drive correspondence folders recursively

### Business Status Badges

| Badge          | Status value | Meaning                                    |
| -------------- | ------------ | ------------------------------------------ |
| 🔵 მიმდინარე   | `active`     | Active business — no letter sent yet       |
| 🟡 გასაგზავნი  | `pending`    | Draft correspondence created, not yet sent |
| 🟢 დასრულებული | `complete`   | Process complete                           |
| ⚠️ ვადაგადაც.  | `overdue`    | Correspondence round past its deadline     |

### Drive Folder Structure

```
📁 Business Name/
├── ფინანსური ანგარიშგება
├── ხელფასები
├── შემოსავლის ამონაწერი
├── თანამშრომლები
├── საბანკო ამონაწერი
├── იჯარის ხელშეკრულება
├── გამოთვლები
├── სხვა
└── მიმოწერა/
    └── round-1/
        └── შემომავალი ფაილები/
```

---

## Excel Exports

Both exports use `maatwebsite/excel` — styled with bold header row (dark blue `#1F3864`, white text), alternating row shading, freeze row, auto-column widths, and clickable URL columns.

| Export          | Route                  | Auth          | File                     |
| --------------- | ---------------------- | ------------- | ------------------------ |
| ACT Export      | `GET /acts/export`     | session auth  | `acts-{date}.xlsx`       |
| Business Export | `GET /business/export` | auth:business | `businesses-{date}.xlsx` |

---

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

**Required `.env` values:**

```env
AIRTABLE_TOKEN=
AIRTABLE_BASE_ID=
TELEGRAM_BOT_TOKEN=
TELEGRAM_MY_CHAT_ID=
TELEGRAM_GROUP_CHAT_ID=
TELEGRAM_NOTIFICATIONS_ENABLED=true
WEBHOOK_SECRET=
GOOGLE_DRIVE_CREDENTIALS=path/to/service-account.json
GOOGLE_DRIVE_ACT_FOLDER_ID=
GOOGLE_DRIVE_BUSINESS_FOLDER_ID=
GOOGLE_DRIVE_OAUTH_CLIENT=path/to/oauth-client.json
GOOGLE_DRIVE_OAUTH_TOKEN=path/to/oauth-token.json
DASHBOARD_USER=
DASHBOARD_PASSWORD=
VIGIL_BUSINESS_INTERNAL_DOMAIN=your-domain.com
SENTRY_LARAVEL_DSN=
B2_KEY_ID=
B2_APPLICATION_KEY=
B2_BUCKET=
B2_REGION=
B2_ENDPOINT=
```

**OAuth setup (one-time):**

```bash
php artisan vigil:drive-auth
```

**RAR extraction requires `unrar-nonfree`:**

```bash
apt-get install unrar-nonfree
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

---

## Project Context

Vigil was built for a large-scale property field inspection project. Field inspectors physically survey 1,500+ properties, produce inventory ACTs, and coordinate with surveyors, agronomists, and cost estimators. Vigil is the quality gate — nothing reaches the final Excel without passing through it. The business portal and Gmail engine handle the legal and financial document flow with property-owning businesses throughout the process.

The system runs entirely in Georgian (`ქართული`) for all user-facing content — Telegram messages, Airtable field names, Drive folder paths, and UI labels — reflecting its real-world production deployment.
