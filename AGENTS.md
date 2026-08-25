# Faysal Glass — AGENTS.md

## Stack

- **PHP 7/8** procedural, no framework, no composer
- **MySQL** via `mysqli_*` functions
- **Frontend**: Bootstrap 4.6, SB Admin 2, jQuery 3.6, Font Awesome 6, SweetAlert2 — all CDN
- **No build tools, no tests, no CI/CD**; `.htaccess` is cPanel PHP handler only (ea-php81)

## Entry & Auth

- `index.php` redirects to `login.php` (or `dashboard/dashboard.php` if session active)
- Plain-text passwords (`login.php:7` comment confirms)
- Session keys set at login: `user_id`, `username`, `full_name`, `user_role`, `logged_in`
- Two auth patterns coexist:
  - **Strict** (most pages): `!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true`
  - **Lax** (accounts/cashbook.php, reports/, some customers/): `!isset($_SESSION['user_id'])` only
- `includes/sidebar.php` gates on strict pattern — pages that include it get a second check

## Required includes

(include path uses `../` from subdirs; order doesn't matter)
```php
include('../includes/database.php');  // $conn (mysqli), escapeString(), executePrepared()
include('../includes/txt.php');       // $software_name, $currency_symbol, formatCurrency()
```
`footer.php` calls `closeConnection()` automatically.

## Architecture

Modules in top-level directories (no router, no controllers):

| Dir | Purpose |
|-----|---------|
| `dashboard/` | One file — `dashboard.php` |
| `sales/`, `purchases/`, `quotations/` | Add/view/print/refund (save via AJAX JSON endpoints) |
| `products/` | List, opening stock, categories, companies, units |
| `customers/`, `suppliers/`, `employees/` | Ledgers with payment/receipt sub-pages |
| `accounts/` | Cashbook, bank ledgers, withdraw, transfer, manage banks |
| `reports/` | 9 files — sale, purchase, profit/loss, inventory, invoice, refund, customer, supplier, salary |
| `expenses/` | Expense heads, details, view by head |
| `includes/` | `database.php`, `txt.php`, `header.php`, `sidebar.php`, `footer.php` |
| `sql/` | Migration scripts (`.sql` + one PHP runner) — run manually, no migration framework |

## Conventions

- **DB**: `faysal_glass` / `localhost` / `root` / no password / `utf8mb4`
  - SQL dump at repo root (`faysal_glass.sql`), but it was exported from `atrmarke_faysal_glass` (live server) — rename before import
- **Timezone**: `Asia/Karachi` (`database.php:28`); **Currency**: PKR — use `formatCurrency($amount)`
- **Page title**: set `$page_title` before `include('header.php')`
- **AJAX endpoints**: `error_reporting(0)` + `header('Content-Type: application/json')` + `ob_end_clean()` loop before `echo json_encode($response)`; return `{success: bool, message: string}`
- **Sales/purchases**: `mysqli_begin_transaction` + try/catch + commit/rollback; invoice numbers generated manually (`SAL-`/`PUR-` prefix, query `MAX`, increment, pad) — **not atomic**
- **Inventory**: glass area in sq ft via `client_height`, `client_width`, `std_height`, `std_width`
- **Hold bills**: incomplete sales in `hold_sales_master` / `hold_sales_details`, loaded back into `add_sale.php`
- **All queries use string interpolation** with `mysqli_real_escape_string()` — `escapeString()` and `executePrepared()` helpers exist in `database.php` but are **never called** anywhere
- **Session key gotcha**: login sets `$_SESSION['username']`; ~15 pages read `$_SESSION['user_name']` (wrong key) with `?? $_SESSION['username'] ?? 'User'` fallback — the fallback catches it, but it's fragile
- **`oldd/` dirs**: abandoned backup copies of the codebase (has `oldd/old2/old1/` nesting) — ignore them
- **No `.gitignore`**: 33 PHP-generated `error_log` files across the tree; will be committed unless excluded manually
- **No migrations**: schema must exist manually before running
