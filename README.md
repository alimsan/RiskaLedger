# RiskaLedger

**A Simple and Lightweight Cash Flow Management Application**

RiskaLedger is a lightweight digital bookkeeping application designed to simplify income and expense tracking, helping users organize financial transactions and generate financial reports.

Built with Laravel and Filament, RiskaLedger aims to provide a simple and accessible solution for digital financial record management.

## Tech Stack

- **Backend:** Laravel 11
- **Admin Panel:** Filament v3
- **Frontend:** Tailwind CSS
- **Database:** SQLite
- **Runtime:** PHP 8.2+

## Key Objectives

- Simplify daily income and expense recording.
- Digitize traditional bookkeeping processes.
- Organize financial transactions.
- Support more structured financial reporting.

## Installation

```bash
git clone https://github.com/alimsan/RiskaLedger.git
cd RiskaLedger

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

npm install
npm run build

php artisan serve
```

## Project Status

RiskaLedger is an evolving open-source project. Contributions, suggestions, bug reports, and improvements are welcome.

## License

See the LICENSE file for licensing information.