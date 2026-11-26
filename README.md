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
| `business:gmail-check`                  | Every 5 min   | Alert if watch-gmail hasn't run in 5 min (heartbeat)     |
| `vigil:horizon-check`                   | Every 5 min   | Alert if Horizon is down                                 |
| `business:sync-drive-states`            | Every 15 min  | Sync Drive subfolder file-presence to DB                 |
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
POST /business/files/move     Move incoming file to named subfolder (auth:business)
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

**Health monitoring:** `business:gmail-check` runs every 5 min; fires a Telegram alert if the heartbeat cache key is stale (≥5 min). Same alert/recover/dedup pattern as `vigil:horizon-check`.

---

## Google Drive Integration

**Drive watcher runs in two modes:**

| System         | Mode                    | Description                        |
| -------------- | ----------------------- | ---------------------------------- |
| Apps Script    | **Active** — real work  | Runs in Google's cloud every 5 min |
| Engine watcher | **Dry-run** — logs only | Runs every minute, no writes       |

Apps Script runs every 5 minutes (change-only). Engine watcher runs every minute and logs what it _would_ do — no Airtable changes, no color writes. When logs match, Apps Script trigger is deleted and `--dry-run` flag is removed.

**Folder colors:**

- Subfolder has files → 🟢
- Subfolder empty → ⬜
- ACT root: ALL tracked subfolders have files + ✅ ხელმოწერები → 🟢; partial → 🟡; empty → ⬜

**Auto-ticked Airtable checkboxes:**
`📥 ფოტოები`, `📥 შიდა ნახაზები`, `📥 გამიჯვნები`, `📥 ხარჯთაღრიცხვა`, `📥 ტოპო ნარგავები`

**`📥 ხელმოწერები`** — manual only (physical signature, no Drive folder).

> ⚠️ **Known issue:** If files are deleted from an already-green subfolder, Apps Script does not revert the color or untick the checkbox. Auto-fixed once engine watcher goes fully live (its 5-min grace + `fileRemoved` path handles this).

---

## Business Portal

A separate web UI (`/business/`) for businesses linked to ACTs.

- **Guard:** `auth('business')` — independent from dashboard session auth
- **Show page:** inline editing, correspondence rounds card, Drive link, upload button, Excel export
- **Upload modal:** loads Drive subfolders via AJAX; multi-file picker; uploads via `fetch()` + `FormData`; auto-closes after success; `Cache::lock` per business+filename prevents duplicate uploads
- **Incoming files:** lists all files from Drive correspondence folders recursively — files inside extracted archive subfolders are shown with their path and are directly clickable
- **File move:** `POST /business/files/move` — moves a file from the incoming folder to a named subfolder; validates subfolder against config whitelist

### Business Status Badges

| Badge          | Status value | Meaning                                    |
| -------------- | ------------ | ------------------------------------------ |
| 🔵 მიმდინარე   | `active`     | Active business — no letter sent yet       |
| 🟡 გასაგზავნი  | `pending`    | Draft correspondence created, not yet sent |
| 🟢 დასრულებული | `complete`   | Process complete                           |
| ⚠️ ვადაგადაც.  | `overdue`    | Correspondence round past its deadline     |

**Dashboard icons in name column:** 📥 = incoming reply received · ⚠️ = no email address on record · 📧 = letter sent, awaiting reply

### Business Card Fields

Each business record stores: name, registration code, ACT number, lot, cadastral code, type, Drive folder link, owner name/phone/email, accountant name/phone/email, notes.

### Correspondence

- A draft round is auto-created when the business record is created
- Incoming emails are auto-detected by `business:watch-gmail` and attached to the matching business as round #1, #2, …
- Each round shows the Drive folder link and its round number
- Outgoing letters set status → `pending`; incoming replies set status → `active`

### Drive Folder Structure

When a business record is created, the following subfolder structure is auto-created under the business Drive folder:

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

### File Upload Workflow (3 steps)

1. Open the upload modal on the business show page
2. Select the target subfolder from the dropdown (loads live from Drive)
3. Pick files and confirm — files upload to `Business/subfolder/` and the row turns green

> ⚠️ **Known issue:** Drive sync can lag. If a file doesn't appear immediately, wait for the auto-update shown as "განახლდა HH:MM" in the portal header.

---

## Excel Exports

Both exports use `maatwebsite/excel` — styled with bold header row (dark blue `#1F3864`, white text), alternating row shading, freeze row, auto-column widths, and clickable URL columns.

| Export          | Route                  | Auth          | File                     |
| --------------- | ---------------------- | ------------- | ------------------------ |
| ACT Export      | `GET /acts/export`     | session auth  | `acts-{date}.xlsx`       |
| Business Export | `GET /business/export` | auth:business | `businesses-{date}.xlsx` |

**ACT columns:** ACT №, სტატუსი, პასუხისმგებელი, გუნდი, სექტორი, რა აკლია, property flags (✅/❌), doc checkboxes (✅/❌), კვლევა, Drive საქაღალდე

**Business columns:** სახელი, სარეგ. კოდი, ტიპი, სტატუსი, ACT, ლოტი, მეპატრონე, ტელ., ელ-ფოსტა, ბუღალტერი, ტელ., ელ-ფოსტა, Drive, შენიშვნები

---

## Dashboard

**Auth:** Session-based login — manager / uploader / viewer roles

Features: ACT stats cards, instant JS search (normalises spaces around hyphens), filters (status / missing doc / კვლევა / ბიზნესი / Excel entered), paginated table (100/page), ⚠️ badge when status=done but doc checkboxes incomplete, `+ შექმნა` button for instant folder creation, last-good fallback with stale banner on Airtable failure. Tools modal: download scans/photos as ZIP, upload scans/photos as ZIP.

### Toolbar

| Element           | Meaning                                                            |
| ----------------- | ------------------------------------------------------------------ |
| 🟢 განახლდა HH:MM | Last successful Airtable data fetch time                           |
| 📊 stat cards     | Total / complete / incomplete ACT counts from Airtable             |
| 🔄 Rescan button  | Triggers a full Drive rescan — takes **30–60 minutes** to complete |

### Column Reference

| Column | Description                                                                 |
| ------ | --------------------------------------------------------------------------- |
| ნომ.   | ACT number — links directly to the Airtable record                          |
| სტატ.  | Airtable status (⏳ in-progress / ✅ done / ⚠️ done-with-missing)           |
| შენ.   | Notes field                                                                 |
| სკანი  | Physical scan PDF — ✅ present / ❌ missing / · not required                |
| ფოტო   | Photos — ✅ / ❌ / ·                                                        |
| ხელმ.  | Signatures (physical, manual only) — ✅ / ❌ / ·                            |
| ნახ.   | Drawings — ✅ / ❌ / ·                                                      |
| გამ.   | Boundary plan — ✅ / ❌ / ·                                                 |
| ხარჯ.  | Cost estimate — ✅ / ❌ / · (Excel entered button appears here for manager) |
| ტოპო   | Topo survey — ✅ / ❌ / ·                                                   |
| ბიზ.   | Business record count linked to this ACT                                    |
| კვლ.   | Research (კვლევა) field value                                               |
| Drive  | Google Drive folder link; `+ შექმნა` button appears when folder is missing  |

**Symbol legend:** ✅ document present · ❌ document missing but required · `·` not required for this ACT · ❗ ACT marked done but required docs are missing

### Filters

| Filter  | What it shows                                                          |
| ------- | ---------------------------------------------------------------------- |
| Search  | Instant JS search by ACT number (starts-with, normalises hyphens)      |
| სტატუსი | Filter by Airtable status (incomplete / done-with-missing / closed)    |
| აკლია   | Show ACTs missing a specific doc type (scan, photos, signatures, etc.) |
| has     | Show ACTs that have a specific doc type present                        |
| კვლევა  | Filter by research field value                                         |
| ბიზნესი | Show only ACTs with linked business records                            |
| Excel   | Show ACTs where cost estimate arrived but hasn't been entered yet      |

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

**Server permissions (after deploy):**

```bash
chown -R www-data:www-data /var/www/vigil-engine/storage
chmod -R 775 /var/www/vigil-engine/storage
chmod -R 777 /var/www/vigil-engine/storage/framework/cache/data
```

**OAuth setup (one-time):**

```bash
php artisan vigil:drive-auth
```

**RAR extraction requires `unrar-nonfree`:**

```bash
apt-get install unrar-nonfree
# or set via update-alternatives if installed under a different binary name
```

---

## Critical Gotchas

- **No base Controller class** — Vigil has no `App\Http\Controllers\Controller`. Never add `extends Controller`.
- **No application DB** — main app has zero migrations. Everything is Airtable + JSON state file. Business module is the only DB user.
- **Redis isolation** — `REDIS_DB=1`. After any infra change, verify Horizon keys exist on the correct DB.
- **Horizon restart** — always `systemctl restart vigil-horizon`. Never start manually.
- **config() not env()** — when config is cached, `env()` returns null everywhere.
- **Boolean strings** — `config('vigil.telegram.notifications_enabled')` is a bool. Use `! config(...)` not `!== 'true'`.
- **uploadFile arg order** — `uploadFile($parentFolderId, $filename, $content, $mimeType)` — parent is first.
- **Telegram HTML escaping** — always `e($value)` on dynamic strings. HTML parse mode breaks silently.
- **Upload lock ownership** — job owns the cache lock entirely. Controller must never set it.
- **Run tests on host** — never inside Docker.

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

Tests cover all commands, webhook controllers, Airtable client, Telegram service, and Drive services. Every push triggers CI — Pint + tests must pass.

---

## Project Context

Vigil was built for a large-scale property field inspection project. Field inspectors physically survey 1,500+ properties, produce inventory ACTs, and coordinate with surveyors, agronomists, and cost estimators. Vigil is the quality gate — nothing reaches the final Excel without passing through it. The business portal and Gmail engine handle the legal and financial document flow with property-owning businesses throughout the process.

The system runs entirely in Georgian (`ქართული`) for all user-facing content — Telegram messages, Airtable field names, Drive folder paths, and UI labels — reflecting its real-world production deployment.
