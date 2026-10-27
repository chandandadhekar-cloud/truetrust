<?php defined('APP_ROOT') || exit;
$nav = $nav ?? false;
$toast = App\Core\Session::pull('toast');
?>
<!doctype html>
<html lang="en">
<head>
<?= partial('partials/head', get_defined_vars()) ?>
</head>
<body class="<?= $nav ? 'has-tabs' : '' ?> <?= e($bodyClass ?? '') ?>" data-pwa-prompt="<?= setting('pwa_prompt') === '1' && me() ? '1' : '0' ?>">
<?php if (setting('announcement_active') === '1' && trim((string) setting('announcement')) !== '' && me()): ?>
    <div class="announcement"><?= icon('bell', 18) ?><span><?= e(setting('announcement')) ?></span></div>
<?php endif; ?>
<div class="shell">
    <?php if ($nav): ?><?= partial('partials/nav') ?><?php endif; ?>
    <main class="screen" id="main">
        <?= $content ?>
    </main>
</div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<?php if (me()): ?><form id="logout-form" method="post" action="/logout" hidden><?= csrf_field() ?></form><?php endif; ?>
<?php if ($toast): ?>
<script type="application/json" id="flash-toast"><?= json_encode($toast, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<?= partial('partials/install') ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php foreach ($scripts ?? [] as $s): ?>
<script src="<?= asset('js/' . $s . '.js') ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
