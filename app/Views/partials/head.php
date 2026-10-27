<?php defined('APP_ROOT') || exit;
$siteName = setting('site_name');
$pageTitle = isset($title) && $title !== '' ? $title . ' · ' . $siteName : $siteName . ' — ' . setting('tagline');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description ?? $siteName . ' is a completely free dating app for India. See who likes you, chat without limits and meet people near you.') ?>">
<meta name="theme-color" content="#FBF8F4" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#131012" media="(prefers-color-scheme: dark)">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php if (me()): ?><meta name="vapid-key" content="<?= e(App\Services\Push::publicKey()) ?>"><?php endif; ?>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($siteName . ' — ' . setting('tagline')) ?>">
<meta property="og:description" content="Free dating for India. No paywalls, no premium tiers.">
<meta property="og:image" content="<?= e(url('/assets/img/og.png')) ?>">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/assets/img/icon.svg" type="image/svg+xml">
<link rel="icon" href="/assets/img/favicon-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="/assets/img/icon-192.png" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
