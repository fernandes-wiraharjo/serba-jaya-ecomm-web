# Serba Jaya Backend

Laravel backend API and admin panel for the Serba Jaya ecommerce app.

## Stack

- PHP 8.3+ / Laravel 13
- Session auth for admins (`admin` guard)
- Sanctum ready for app users / buyers (`web` + API)
- Blade admin UI with Iconify icons
- Tailwind CSS 4 via Vite (optional for admin; panel styles also ship in `public/css/admin.css`)

## Features so far

- First-time setup when no admin exists (`/admin/setup`)
- Admin login / logout (`/admin/login`)
- Separate `admins` and `users` auth models/guards
- Admin CRUD (`/admin/admins`)
- Mobile-friendly admin shell with offcanvas sidebar

## Requirements

- PHP 8.3+
- Composer
- MySQL / MariaDB (or SQLite for local/testing)
- Node.js + npm only if you want Vite/Tailwind asset builds

Admin panel styles live in `public/css/admin.css` and work without `npm`.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run migrations:

```bash
php artisan migrate
```

Start the app:

```bash
php artisan serve
```

Optional frontend tooling:

```bash
npm install
npm run dev
# or
npm run build
```

## Admin access

1. Open `/admin`
2. If no admin exists, create the first account at `/admin/setup`
3. Otherwise sign in at `/admin/login`

Useful routes:

| Path | Description |
|------|-------------|
| `/admin/setup` | First admin setup |
| `/admin/login` | Admin login |
| `/admin` | Dashboard |
| `/admin/admins` | Manage admins |

## Auth guards

| Guard | Model | Purpose |
|-------|-------|---------|
| `admin` | `App\Models\Admin` | Admin panel |
| `web` | `App\Models\User` | App buyers / customers |

## Flash messages

From controllers:

```php
return redirect()->route('admin.admins.index')
    ->with('success', 'Saved.');
```

Supported flash keys: `success`, `error`, `warning`, `info`  
(`status` is treated as success for compatibility)

## Tests

```bash
php artisan test
```

## License

Proprietary / project-specific unless otherwise noted.
