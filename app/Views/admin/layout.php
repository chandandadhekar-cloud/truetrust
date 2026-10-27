<?php defined('APP_ROOT') || exit;
$admin = App\Core\AdminAuth::admin();
$toast = App\Core\Session::pull('toast');
$counts = App\Controllers\Admin\AdminController::navCounts();
$path = App\Core\Request::path();
$on = static fn ($p) => ($path === $p || ($p !== '/admin' && strpos($path, $p) === 0)) ? ' is-active' : '';
$nav = [
    ['Overview', [
        ['/admin', 'Dashboard', 'chart', 0],
    ]],
    ['Members', [
        ['/admin/users', 'All members', 'users', 0],
        ['/admin/seed', 'Seed profiles', 'sparkle', 0],
        ['/admin/photos', 'Photo review', 'image', $counts['photos']],
    ]],
    ['Safety', [
        ['/admin/conversations', 'Conversations', 'chat', 0],
        ['/admin/messages/flagged', 'Flagged messages', 'alert', $counts['flagged']],
        ['/admin/reports', 'Reports', 'flag', $counts['reports']],
    ]],
    ['System', [
        ['/admin/settings', 'Settings', 'settings', 0],
        ['/admin/team', 'Team', 'shield', 0],
        ['/admin/logs', 'Activity log', 'calendar', 0],
        ['/admin/system', 'Health', 'zap', 0],
    ]],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? 'Admin') . ' · ' . setting('site_name') . ' Admin') ?></title>
<link rel="icon" href="/assets/img/icon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin" data-pwa-prompt="0">
<div class="admin-shell">
    <div class="topbar">
        <button class="icon-btn" style="color:#fff" data-side-toggle aria-label="Menu"><?= icon('menu', 22) ?></button>
        <span class="brand"><?= partial('partials/logo') ?><?= wordmark() ?></span>
    </div>
    <aside class="side" id="side">
        <a href="/admin" class="brand"><?= partial('partials/logo') ?><span><?= wordmark() ?> <small>Admin</small></span></a>
        <?php foreach ($nav as [$label, $items]): ?>
            <div class="side-label"><?= e($label) ?></div>
            <?php foreach ($items as [$href, $text, $ico, $count]): ?>
                <a class="nav<?= $on($href) ?>" href="<?= $href ?>"><?= icon($ico, 19) ?><?= e($text) ?><?php if ($count): ?><span class="count"><?= $count ?></span><?php endif; ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="side-foot">
            <b><?= e($admin['name'] ?? '') ?></b>
            <span style="color:#6F6570"><?= e(ucfirst($admin['role'] ?? '')) ?></span>
            <div class="row" style="margin-top:10px;gap:14px">
                <a href="/" target="_blank">View app</a>
                <a href="/admin/password">Password</a>
                <form method="post" action="/admin/logout"><?= csrf_field() ?><button>Sign out</button></form>
            </div>
        </div>
    </aside>
    <main class="main">
        <?= $content ?>
    </main>
</div>
<div class="toasts" id="toasts"></div>
<?php if ($toast): ?><script type="application/json" id="flash-toast"><?= json_encode($toast, JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
