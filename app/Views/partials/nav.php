<?php defined('APP_ROOT') || exit;
$tabs = [
    ['/discover', 'Discover', 'flame', 'badge-discover'],
    ['/likes', 'Likes', 'heart', 'badge-likes'],
    ['/matches', 'Chats', 'chat', 'badge-chats'],
    ['/profile', 'Profile', 'user', ''],
];
$flame = '<svg class="icon tab-seal" width="25" height="25" viewBox="0 0 64 64" aria-hidden="true"><path class="s" d="M32 8.3C32.99 8.3 34.07 8.8 34.98 9.35C35.89 9.89 36.68 10.92 37.47 11.57C38.27 12.22 38.92 12.89 39.77 13.25C40.62 13.6 41.55 13.58 42.58 13.68C43.6 13.79 44.88 13.61 45.91 13.87C46.94 14.13 48.06 14.54 48.76 15.24C49.46 15.94 49.87 17.06 50.13 18.09C50.39 19.12 50.21 20.4 50.32 21.42C50.42 22.45 50.4 23.38 50.75 24.23C51.11 25.08 51.78 25.73 52.43 26.53C53.08 27.32 54.11 28.11 54.65 29.02C55.2 29.93 55.7 31.01 55.7 32C55.7 32.99 55.2 34.07 54.65 34.98C54.11 35.89 53.08 36.68 52.43 37.47C51.78 38.27 51.11 38.92 50.75 39.77C50.4 40.62 50.42 41.55 50.32 42.58C50.21 43.6 50.39 44.88 50.13 45.91C49.87 46.94 49.46 48.06 48.76 48.76C48.06 49.46 46.94 49.87 45.91 50.13C44.88 50.39 43.6 50.21 42.58 50.32C41.55 50.42 40.62 50.4 39.77 50.75C38.92 51.11 38.27 51.78 37.47 52.43C36.68 53.08 35.89 54.11 34.98 54.65C34.07 55.2 32.99 55.7 32 55.7C31.01 55.7 29.93 55.2 29.02 54.65C28.11 54.11 27.32 53.08 26.53 52.43C25.73 51.78 25.08 51.11 24.23 50.75C23.38 50.4 22.45 50.42 21.42 50.32C20.4 50.21 19.12 50.39 18.09 50.13C17.06 49.87 15.94 49.46 15.24 48.76C14.54 48.06 14.13 46.94 13.87 45.91C13.61 44.88 13.79 43.6 13.68 42.58C13.58 41.55 13.6 40.62 13.25 39.77C12.89 38.92 12.22 38.27 11.57 37.47C10.92 36.68 9.89 35.89 9.35 34.98C8.8 34.07 8.3 32.99 8.3 32C8.3 31.01 8.8 29.93 9.35 29.02C9.89 28.11 10.92 27.32 11.57 26.53C12.22 25.73 12.89 25.08 13.25 24.23C13.6 23.38 13.58 22.45 13.68 21.42C13.79 20.4 13.61 19.12 13.87 18.09C14.13 17.06 14.54 15.94 15.24 15.24C15.94 14.54 17.06 14.13 18.09 13.87C19.12 13.61 20.4 13.79 21.42 13.68C22.45 13.58 23.38 13.6 24.23 13.25C25.08 12.89 25.73 12.22 26.53 11.57C27.32 10.92 28.11 9.89 29.02 9.35C29.93 8.8 31.01 8.3 32 8.3Z" fill="none" stroke="currentColor" stroke-width="4.6" stroke-linejoin="round"/><g transform="translate(32 32.5) scale(.82) translate(-32 -33)"><path class="h" d="M32 45.6c-3.4-2.4-12.6-8.6-12.6-16.4 0-4.3 3.2-7.4 7.1-7.4 2.4 0 4.3 1.2 5.5 3.1 1.2-1.9 3.1-3.1 5.5-3.1 3.9 0 7.1 3.1 7.1 7.4 0 7.8-9.2 14-12.6 16.4z" fill="currentColor"/></g></svg>';
$navPath = App\Core\Request::path();
$isOn = static fn ($href) => $navPath === $href || strpos($navPath, $href . '/') === 0
    || ($href === '/matches' && strpos($navPath, '/chat/') === 0)
    || ($href === '/profile' && strpos($navPath, '/settings') === 0);
?>
<nav class="rail" aria-label="Main">
    <a href="/discover" class="brand"><?= partial('partials/logo') ?><?= wordmark() ?></a>
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
