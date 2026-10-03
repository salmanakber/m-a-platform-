# nachfolge-experten.ch

Laravel 9 Plattform für Nachfolge- und M&A-Experten in der Schweiz (PHP **8.0+**).

## Voraussetzungen

- PHP 8.0 oder höher (8.0-kompatibler Code)
- Composer
- SQLite **oder** MySQL/MariaDB
- Optional: Node.js für Vite-Assets (`resources/css/app.css`)

### PHP unter macOS (XAMPP)

Homebrew-PHP kann auf älteren macOS-Versionen fehlschlagen. Stattdessen XAMPP verwenden:

```bash
export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"
php -v
```

## Installation

```bash
cd /Users/macbook/M&A-platform
export PATH="/Applications/XAMPP/xamppfiles/bin:$PATH"

composer install
cp .env.example .env
php artisan key:generate
```

### Datenbank

**SQLite (schnell lokal):**

```env
DB_CONNECTION=sqlite
# DB_DATABASE wird auf database/database.sqlite gesetzt (Datei anlegen)
```

```bash
touch database/database.sqlite
```

**MySQL:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nachfolge_experten
DB_USERNAME=root
DB_PASSWORD=
```

Migrationen und Seed-Daten:

```bash
php artisan migrate --seed
php artisan storage:link
```

### Admin-Zugang (nach Seed)

| Feld | Wert |
|------|------|
| E-Mail | `admin@nachfolge-experten.ch` |
| Passwort | `ChangeMe!Admin` |

Bitte Passwort nach dem ersten Login ändern.

### Telsearch-Import

1. Admin → **Import**, oder CLI: `php artisan import:telsearch`
2. CSV-Vorlage herunterladen **oder** Datei `Basis_telsearch.xlsx` nach `storage/app/imports/` legen
3. Upload starten — Einträge werden als `pending` / nicht öffentlich angelegt (keine Duplikate bei erneutem Import)

```bash
php artisan import:telsearch
php artisan import:telsearch --file=/path/to/liste.xlsx
```

## Entwicklungsserver

```bash
php artisan serve
```

Öffentliche Routen u.a.: `/`, `/experten`, `/registrierung`, `/login`  
Admin: `/admin` (Middleware `auth` + `role:admin`)  
Experten: `/expert` (Middleware `auth` + `role:expert`)

## Scheduler

Täglich: abgelaufene Promotionen (`ExpirePromotionsJob`) und Crawl-Jobs für fällige Experten (`CrawlService`).

```bash
php artisan schedule:work
```

In Produktion Cron: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Hosting

Deployment (Shared Hosting, VPS, etc.) folgt in einer späteren Phase. `.env` mit `APP_ENV=production`, `APP_DEBUG=false`, Queue/Scheduler auf dem Server einplanen.

## Lizenz

MIT (Laravel-Basis).
