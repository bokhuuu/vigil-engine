# 🛡️ Vigil Engine

**Quality control and automation engine for Colliers Georgia's large-scale property expropriation project.**

Vigil tracks 1,000+ property ACTs (inventory forms), coordinates 10+ field teams, and ensures no document passes to the master Excel incomplete. It combines an Airtable database, a Laravel automation engine, Telegram notifications, and a webhook layer into one cohesive system.

---

## What It Does

- **Tracks every ACT** - each physical property form is entered into Airtable with its status, responsible teams, and missing documents
- **Notifies teams automatically** - every evening at 20:00, each team receives their personal incomplete ACT list via Telegram
- **Monitors correspondence** - government letters are tracked with 5-day response deadlines; overdue items are flagged daily
- **Handles form submissions** - when a team submits an update form, they receive a Telegram link to their full submission history 15 minutes later
- **Alerts managers** - daily summaries of ACT progress and correspondence status sent to management

---

## Architecture

```
Airtable (source of truth)
    ↕ API
Laravel Engine (automation brain)
    ↕ HTTP
Telegram Bot (delivery channel)

Airtable Form → Webhook → Engine → 15-min delay → Telegram
```

**Stack:** Laravel 13, PHP 8.4, Airtable API, Telegram Bot API  
**Server:** Vultr VPS, Caddy reverse proxy, PHP-FPM  
**URL:** `https://vigil.45-32-154-101.nip.io`  
**CI/CD:** GitHub Actions - test + lint + deploy on push to main

---

## Scheduled Commands

| Command                        | Schedule      | Purpose                                                  |
| ------------------------------ | ------------- | -------------------------------------------------------- |
| `vigil:act-alert`              | Mon–Fri 20:00 | Incomplete ACT alerts to all teams and managers          |
| `vigil:correspondence-overdue` | Mon–Fri 11:00 | Mark overdue government letters, alert                   |
| `vigil:deadline-warning`       | Mon–Fri 11:00 | Team commitment deadline warnings                        |
| `vigil:sector-deadline`        | Mon–Fri 11:00 | Sector completion deadline warnings                      |
| `vigil:send-update-reminders`  | Every minute  | Send განახლებები link 15 min after first form submission |
| `vigil:backup`                 | Sunday 11:00  | JSON backup to Backblaze B2                              |

---

## Webhook

`POST /webhook/airtable?secret={WEBHOOK_SECRET}`

Triggered by Airtable automations on form submission. Identifies the submitting team, stores the event in file cache, and schedules a Telegram notification 15 minutes later.

---

## Services

- **AirtableClient** - paginated GET, PATCH, POST, single record fetch
- **AirtableService** - domain queries (incomplete ACTs, teams, sectors, deadlines, correspondence)
- **TelegramService** - `send()`, `sendToMe()`, `sendToGroup()`
- **WebhookController** - receives Airtable webhook, routes to cache

---

## Setup

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Required .env values
AIRTABLE_TOKEN=
AIRTABLE_BASE_ID=
TELEGRAM_BOT_TOKEN=
TELEGRAM_MY_CHAT_ID=
TELEGRAM_GROUP_CHAT_ID=
WEBHOOK_SECRET=
```

**Server permissions (after deploy):**

```bash
chmod -R 777 /var/www/vigil-engine/storage/framework/cache/data
chown -R www-data:www-data /var/www/vigil-engine/storage
chmod -R 775 /var/www/vigil-engine/storage
```

---

## Development Workflow

```bash
# Run tests
php artisan test

# Fix code style
./vendor/bin/pint

# Then push
git push
```

Tests cover all commands, the webhook controller, Airtable client, and Telegram service. Every push triggers CI - tests must pass before deploy.

---

## Project Context

Vigil supports a Georgian Road Department property expropriation project managed by Colliers Georgia. Field inspectors physically survey 1,000+ properties, produce inventory ACTs, and coordinate with surveyors, agronomists, and cost estimators. Vigil is the quality gate - nothing reaches the final Excel without passing through it.

---

_🛡️ Vigil - Nothing passes incomplete._
