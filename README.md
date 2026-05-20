# mammo care HK Sales CRM

Laravel 13 internal Sales CRM for managing accounts, leads, opportunities, Kanban conversion, deals, configurable sales plans/costs/commission rules, monthly commission calculation, approvals, overrides, and CSV reports.

## Stack

- PHP 8.4+ recommended for project environments
- Laravel 13, Laravel Fortify Livewire starter kit, Livewire 4, Flux UI, Tailwind CSS
- MySQL 8.0+ for production/staging
- Database sessions and database queues
- Spatie Laravel Permission and Spatie Activitylog
- SortableJS Kanban and Chart.js reports
- Pest PHP tests

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mammocare_crm
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Then run:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Default users:

- Admin: `admin@example.com` / `password`（同時擁有 admin + sales 角色，可被分配銷售工作及計佣）
- Staff Demo: `staff@example.com` / `password`（可管理 CRM 記錄，不可管理系統設定及用戶）
- Sales Demo: `sales@example.com` / `password`

## Password Reset Email

Forgot-password emails use Laravel Fortify password reset tokens and are sent through Mailgun.

Required environment values:

```env
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=noreply@mammocare.hk
MAIL_FROM_NAME="${APP_NAME}"
MAILGUN_DOMAIN=mg.example.com
MAILGUN_SECRET=key-your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
```

Use `MAILGUN_ENDPOINT=api.eu.mailgun.net` for an EU Mailgun domain. Microsoft Entra-only users should sign in through Microsoft instead of resetting a local password.

## Development

```bash
composer run dev
```

This starts the Laravel server, queue listener, logs, and Vite dev server.

For this local workspace, `.env` is configured to SQLite so the app can be previewed without a running MySQL service. `.env.example` remains MySQL-oriented for deployment.

## Main Modules

- Dashboard with admin-wide or sales-scoped metrics
- Admin user management with listing, detail, create, edit, active/inactive status, roles, and login policy
- Admin-created users receive invitation emails; admins can resend an invitation and copy the generated invitation link from the user detail page
- Account, lead, opportunity, and deal management
- Opportunity Kanban with Done Deal and Lost validation
- Lead conversion into account and opportunity
- Configurable sales plans, cost assumptions, commission rules, monthly tiers, high plan accelerators, and renewal/upgrade rates
- Monthly commission runs with approval and override reason audit logging
- CSV exports for sales and commission reports
- API resource classes for future integration responses

## Commission Notes

Commission is calculated by `App\Services\Commission\CommissionCalculator`.

Rules included:

- Paid deals only
- New deal plan commission plus lead/trial reward
- Passive renewal/upgrade at 1.5%, no monthly tier count-in
- AM managed renewal at 3.5%, 25% monthly tier count-in
- AM managed upgrade at 5%, 50% monthly tier count-in
- Cumulative monthly tier bonus requiring a new PLAN B or above deal unless admin override is enabled
- Highest matching high plan accelerator only
- Gross margin from configurable report, iPad, and sensor costs

## Tests

```bash
php artisan test
vendor/bin/pint --test
```

The feature suite covers account uniqueness, inactive login blocking, sales data isolation, lead conversion, Kanban deal/lost validation, commission calculation, monthly tier behavior, HPA behavior, and idempotent commission reruns.

## Queue Worker

Use Supervisor for traditional server deployments:

```ini
[program:mammocare-crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mammocare-crm/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/mammocare-crm/storage/logs/worker.log
stopwaitsecs=3600
```

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Point the web server document root at `public/`.
