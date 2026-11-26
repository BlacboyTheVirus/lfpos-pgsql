# LfPOS

LfPOS is a point-of-sale and invoicing app for a print shop that sells by area, such as banners, vinyl and flex. It handles customers, products, invoices with dimension-based line items, payments, expenses and sales reporting. It's built on Laravel 12 and Filament 4 and runs on PostgreSQL.

## Features

- **Invoices.** Line items are priced by width × height × quantity. Each product can set a minimum charge, and totals round to a configurable amount. Invoices take partial payments by cash, transfer or POS, track their status (unpaid, partial or paid) and have a printable view.
- **Products.** Each product has a unit, a price, a minimum charge, and a default width and height that pre-fill new invoice lines.
- **Customers.** Each customer has an outstanding balance and a transaction history.
- **Expenses.** Expenses are grouped into categories (materials, utilities, repairs and cleaning, staff, assets, miscellaneous). The description field suggests past descriptions as you type.
- **Dashboard.** Shows sales stats, an invoice totals chart, revenue by product and top customers. All of these follow a shared date-range filter, which includes last-year ranges.
- **Exports.** Invoices, customers and expenses export to CSV, Excel and PDF, either the full list or selected rows.
- **Access control.** Roles and permissions are managed with Filament Shield. New users get the `panel_user` role automatically.
- **Settings.** Company details, bank details, currency format, invoice terms and footer, rounding, and the prefixes and number formats used for record codes (for example `IN-00001`).
- **Global search.** Search across records from anywhere in the panel.

## Tech stack

| | |
|---|---|
| Language | PHP 8.4+ |
| Framework | Laravel 12 |
| Admin UI | Filament 4, Livewire 3, Tailwind CSS 4 |
| Database | PostgreSQL |
| PDF | barryvdh/laravel-dompdf |
| Permissions | bezhansalleh/filament-shield (Spatie Permission) |
| Tests | Pest 4 |

## Requirements

- PHP 8.4 or newer, with the `intl` and `pdo_pgsql` extensions
- Composer
- Node.js and npm
- PostgreSQL

## Getting started

```bash
git clone https://github.com/BlacboyTheVirus/lfpos-pgsql.git
cd lfpos-pgsql
cp .env.example .env
```

Set your database connection in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lfpos_pgsql
DB_USERNAME=postgres
DB_PASSWORD=
```

Then install dependencies, run the migrations and build the frontend:

```bash
composer run setup
```

Seed the settings, roles, admin user and sample data:

```bash
php artisan db:seed
```

Start the app:

```bash
composer run dev
```

This runs the web server, queue worker, log viewer and Vite together. The app redirects to the admin panel at `/admin`.

### Default login

The seeder creates a super admin account:

- Email: `superadmin@lfpos.com`
- Password: `Pw.1`

**Change this password straight away on any server other than your own machine.**

After seeding, update the company and bank details under **Settings**.

## Development

```bash
composer run dev          # Server, queue, logs and Vite
npm run build             # Build frontend assets for production
vendor/bin/pint --dirty   # Format changed PHP files (run before committing)
```

### Project layout

Each Filament resource keeps its form, infolist, table and pages in separate classes:

```
app/Filament/Resources/Invoices/
├── InvoiceResource.php
├── Schemas/
│   ├── InvoiceForm.php
│   └── InvoiceInfolist.php
├── Tables/
│   └── InvoicesTable.php
└── Pages/
    ├── ListInvoices.php
    ├── CreateInvoice.php
    ├── EditInvoice.php
    └── ViewInvoice.php
```

Other useful places:

- `app/Models`: Eloquent models (`Invoice`, `InvoiceProduct`, `InvoicePayment`, `Customer`, `Product`, `Expense`, `Setting`, …)
- `app/Enums`: `InvoiceStatus`, `PaymentType`, `ExpenseCategory`
- `app/Filament/Widgets`: dashboard widgets
- `app/Http/Controllers/InvoicePrintController.php`: printable invoice at `/invoices/{invoice}/print`
- `resources/views/exports`: PDF export templates

## Testing

```bash
php artisan test                                      # Whole suite
php artisan test tests/Feature/InvoiceFormTotalsTest.php
php artisan test --filter="default dimensions"
```

> **Note:** `phpunit.xml` runs tests on in-memory SQLite, but some migrations use PostgreSQL-only SQL. Tests that need the database fail on SQLite. To run them, point the test config at a separate, empty PostgreSQL database (never your development database), for example by setting `DB_CONNECTION=pgsql` and `DB_DATABASE=lfpos_pgsql_testing` in `phpunit.xml`.
