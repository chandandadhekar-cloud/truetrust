# TrueTrust

Free dating web app for India (installable PWA) with an admin panel. Plain PHP 7.4+ / 8.x and MariaDB/MySQL, no Composer dependencies. `main` deploys straight to https://truetrust.online.

## Layout

```
index.php            Front controller (nginx: try_files $uri /index.php)
config.php           App + database config; config.local.php (git-ignored) overrides it locally
sw.js                Service worker (must stay at the web root for scope "/")
app/
  bootstrap.php      Autoloader, error handling, config
  routes.php         Every URL in the app and admin panel
  Core/              Router, DB (PDO), sessions, CSRF, auth, settings, mailer, image processing, migrator
  Controllers/       Member app controllers; Admin/ holds the admin panel
  Models/            User, Photo, Swipe, MatchModel, Message, Block, Report, EmailToken
  Services/          Discovery (swipe deck), SeedGenerator, Emails
  Data/              Indian cities, profile options, seed-profile source data
  Views/             PHP templates (layouts, partials, member screens, admin/)
assets/              CSS, JS and icons, served directly by nginx
database/migrations/ Schema changes, applied automatically on the first request after deploy
```

Every PHP file except `index.php` starts with `defined('APP_ROOT') || exit;`, so opening it directly by URL does nothing.

## Conventions

- **Schema changes:** add a new file in `database/migrations/` (timestamped name, returns `function (PDO $pdo)`). Never edit a migration that has already run in production.
- **Settings** that admins can change live in the `settings` table; defaults are in `App\Core\Settings::DEFAULTS`.
- **Photos** are stored in the database (`photo_blobs`), so they survive redeploys, and are served by `/photo/{id}/{full|thumb}` to signed-in users only. Uploads are re-encoded, which strips EXIF/GPS.
- **Location privacy:** only the chosen city's centre point is stored, never a member's exact position.
- **Seed profiles** have `is_seed = 1`, an unusable password and a `.invalid` email address. They never like anyone back, so they can't create matches.
- **Admin actions** are written to `admin_logs` via `AdminAuth::log()`.

## Local development

Create `config.local.php` with just the values to override:

```php
<?php
return [
    'app' => ['url' => 'http://127.0.0.1:8080', 'debug' => true],
    'db'  => ['name' => 'tt_dev', 'user' => 'tt', 'pass' => 'tt'],
];
```

Then run `php -S 127.0.0.1:8080 dev-router.php`.

`dev-router.php` mimics the nginx rules and refuses to run outside PHP's built-in server.
