<?php defined('APP_ROOT') || exit;
$tabs = [
    ['/discover', 'Discover', 'flame', 'badge-discover'],
    ['/likes', 'Likes', 'heart', 'badge-likes'],
    ['/matches', 'Chats', 'chat', 'badge-chats'],
    ['/profile', 'Profile', 'user', ''],
];
$flame = '<svg class="icon tab-flame" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22c4.4 0 7.5-3 7.5-7.2 0-3.7-2.3-6.4-4.4-8.6-.4 2.2-1.6 3.6-3 4.3.2-3.4-1.2-6.6-4.3-8.5.3 3-1.2 5-2.8 7C3.8 10.6 4.5 12.6 4.5 14.8 4.5 19 7.6 22 12 22z"/><path d="M12 22c-1.9 0-3.2-1.3-3.2-3.1 0-1.9 1.6-3 2.4-4.6.9 1 1.3 1.9 1.4 2.9.7-.4 1.3-1 1.6-1.9.9 1 1 2.2 1 3.4 0 1.9-1.3 3.3-3.2 3.3z"/></svg>';
$navPath = App\Core\Request::path();
$isOn = static fn ($href) => $navPath === $href || strpos($navPath, $href . '/') === 0
    || ($href === '/matches' && strpos($navPath, '/chat/') === 0)
    || ($href === '/profile' && strpos($navPath, '/settings') === 0);
?>
<nav class="rail" aria-label="Main">
    <a href="/discover" class="brand"><?= partial('partials/logo') ?><span class="brand-word"><?= e(setting('site_name')) ?></span></a>
    <?php foreach ($tabs as [$href, $label, $ico, $badge]): ?>
        <a href="<?= $href ?>" class="rail-link<?= $isOn($href) ? ' is-active' : '' ?>">
            <?= $ico === 'flame' ? $flame : icon($ico, 24) ?>
            <span><?= $label ?></span>
            <?php if ($badge): ?><span class="dot-badge hidden" data-badge="<?= $badge ?>"></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
    <a href="/settings" class="rail-link<?= strpos($navPath, '/settings') === 0 ? ' is-active' : '' ?>"><?= icon('settings', 24) ?><span>Settings</span></a>
    <div class="rail-foot">
        <a href="/safety">Safety</a> · <a href="/guidelines">Guidelines</a> · <a href="/privacy">Privacy</a> · <a href="/terms">Terms</a><br>
        Free forever. No premium plans.
    </div>
</nav>
<nav class="tabbar" aria-label="Main">
    <?php foreach ($tabs as [$href, $label, $ico, $badge]): ?>
        <a href="<?= $href ?>" class="tab<?= $isOn($href) ? ' is-active' : '' ?>">
            <?= $ico === 'flame' ? $flame : icon($ico, 25) ?>
            <span><?= $label ?></span>
            <?php if ($badge): ?><span class="dot-badge hidden" data-badge="<?= $badge ?>"></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
