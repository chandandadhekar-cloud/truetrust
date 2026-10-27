<?php defined('APP_ROOT') || exit;
/*
 * Spot illustrations for empty states. Colours come from CSS variables so they
 * follow light and dark mode. Usage: partial('partials/art', ['name' => 'chats'])
 */
$seal = 'M32 8.3C32.99 8.3 34.07 8.8 34.98 9.35C35.89 9.89 36.68 10.92 37.47 11.57C38.27 12.22 38.92 12.89 39.77 13.25C40.62 13.6 41.55 13.58 42.58 13.68C43.6 13.79 44.88 13.61 45.91 13.87C46.94 14.13 48.06 14.54 48.76 15.24C49.46 15.94 49.87 17.06 50.13 18.09C50.39 19.12 50.21 20.4 50.32 21.42C50.42 22.45 50.4 23.38 50.75 24.23C51.11 25.08 51.78 25.73 52.43 26.53C53.08 27.32 54.11 28.11 54.65 29.02C55.2 29.93 55.7 31.01 55.7 32C55.7 32.99 55.2 34.07 54.65 34.98C54.11 35.89 53.08 36.68 52.43 37.47C51.78 38.27 51.11 38.92 50.75 39.77C50.4 40.62 50.42 41.55 50.32 42.58C50.21 43.6 50.39 44.88 50.13 45.91C49.87 46.94 49.46 48.06 48.76 48.76C48.06 49.46 46.94 49.87 45.91 50.13C44.88 50.39 43.6 50.21 42.58 50.32C41.55 50.42 40.62 50.4 39.77 50.75C38.92 51.11 38.27 51.78 37.47 52.43C36.68 53.08 35.89 54.11 34.98 54.65C34.07 55.2 32.99 55.7 32 55.7C31.01 55.7 29.93 55.2 29.02 54.65C28.11 54.11 27.32 53.08 26.53 52.43C25.73 51.78 25.08 51.11 24.23 50.75C23.38 50.4 22.45 50.42 21.42 50.32C20.4 50.21 19.12 50.39 18.09 50.13C17.06 49.87 15.94 49.46 15.24 48.76C14.54 48.06 14.13 46.94 13.87 45.91C13.61 44.88 13.79 43.6 13.68 42.58C13.58 41.55 13.6 40.62 13.25 39.77C12.89 38.92 12.22 38.27 11.57 37.47C10.92 36.68 9.89 35.89 9.35 34.98C8.8 34.07 8.3 32.99 8.3 32C8.3 31.01 8.8 29.93 9.35 29.02C9.89 28.11 10.92 27.32 11.57 26.53C12.22 25.73 12.89 25.08 13.25 24.23C13.6 23.38 13.58 22.45 13.68 21.42C13.79 20.4 13.61 19.12 13.87 18.09C14.13 17.06 14.54 15.94 15.24 15.24C15.94 14.54 17.06 14.13 18.09 13.87C19.12 13.61 20.4 13.79 21.42 13.68C22.45 13.58 23.38 13.6 24.23 13.25C25.08 12.89 25.73 12.22 26.53 11.57C27.32 10.92 28.11 9.89 29.02 9.35C29.93 8.8 31.01 8.3 32 8.3Z';
$heart = 'M32 45.6c-3.4-2.4-12.6-8.6-12.6-16.4 0-4.3 3.2-7.4 7.1-7.4 2.4 0 4.3 1.2 5.5 3.1 1.2-1.9 3.1-3.1 5.5-3.1 3.9 0 7.1 3.1 7.1 7.4 0 7.8-9.2 14-12.6 16.4z';
$spark = static fn ($x, $y, $s, $c = 'var(--gold)') => '<path transform="translate(' . $x . ' ' . $y . ') scale(' . $s . ')" d="M0-10C1-3 3-1 10 0 3 1 1 3 0 10-1 3-3 1-10 0-3-1-1-3 0-10z" fill="' . $c . '"/>';
$name = $name ?? 'chats';
?>
<svg class="art" viewBox="0 0 200 160" width="200" height="160" aria-hidden="true">
    <ellipse cx="100" cy="86" rx="78" ry="64" fill="var(--brand-soft)"/>
<?php if ($name === 'chats'): ?>
    <g transform="rotate(-6 70 64)">
        <rect x="30" y="34" width="82" height="56" rx="22" fill="var(--ink)"/>
        <path d="M46 86 38 104 64 88z" fill="var(--ink)"/>
        <circle cx="56" cy="62" r="5" fill="var(--bg)"/><circle cx="71" cy="62" r="5" fill="var(--bg)"/><circle cx="86" cy="62" r="5" fill="var(--bg)"/>
    </g>
    <g transform="rotate(5 130 98)">
        <rect x="88" y="70" width="86" height="60" rx="24" fill="var(--brand)"/>
        <path d="M158 126 170 146 140 128z" fill="var(--brand)"/>
        <g transform="translate(131 101) scale(.95) translate(-32 -33)"><path d="<?= $heart ?>" fill="var(--bg)"/></g>
    </g>
    <?= $spark(170, 42, 1.1) ?><?= $spark(24, 118, .7, 'var(--brand)') ?><?= $spark(150, 24, .55) ?>
<?php elseif ($name === 'likes'): ?>
    <rect x="44" y="36" width="64" height="88" rx="14" fill="var(--surface-3)" transform="rotate(-12 76 80)"/>
    <rect x="92" y="36" width="64" height="88" rx="14" fill="var(--ink)" transform="rotate(10 124 80)"/>
    <rect x="66" y="26" width="68" height="94" rx="15" fill="var(--surface)" stroke="var(--line)" stroke-width="2"/>
    <rect x="76" y="36" width="48" height="50" rx="9" fill="var(--brand-soft)"/>
    <circle cx="100" cy="56" r="9" fill="var(--brand)" opacity=".55"/>
    <path d="M86 86c2-10 26-10 28 0z" fill="var(--brand)" opacity=".55"/>
    <rect x="76" y="94" width="34" height="6" rx="3" fill="var(--ink)" opacity=".85"/>
    <rect x="76" y="104" width="22" height="5" rx="2.5" fill="var(--muted)" opacity=".6"/>
    <g transform="translate(140 112) scale(.62) translate(-32 -32)"><path d="<?= $seal ?>" fill="var(--brand)"/><path d="<?= $heart ?>" fill="var(--bg)"/></g>
    <?= $spark(40, 30, .9) ?><?= $spark(168, 40, .6, 'var(--brand)') ?>
<?php elseif ($name === 'caught-up'): ?>
    <circle cx="100" cy="82" r="50" fill="none" stroke="var(--brand)" stroke-width="2" stroke-dasharray="3 7" opacity=".6"/>
    <g transform="translate(100 82) scale(1.35) translate(-32 -32)"><path d="<?= $seal ?>" fill="var(--brand)"/></g>
    <path d="m84 82 11 11 22-23" fill="none" stroke="var(--bg)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="150" cy="44" r="8" fill="var(--ink)"/><circle cx="46" cy="118" r="6" fill="var(--gold)"/>
    <?= $spark(44, 40, 1) ?><?= $spark(160, 120, .8, 'var(--brand)') ?>
<?php elseif ($name === 'mail'): ?>
    <rect x="38" y="44" width="124" height="84" rx="12" fill="var(--surface)" stroke="var(--ink)" stroke-width="3"/>
    <path d="M40 50 100 94 160 50" fill="none" stroke="var(--ink)" stroke-width="3" stroke-linejoin="round"/>
    <path d="M40 124 86 84M160 124 114 84" stroke="var(--ink)" stroke-width="3" stroke-linecap="round" opacity=".35"/>
    <g transform="translate(100 96) scale(.82) translate(-32 -32)"><path d="<?= $seal ?>" fill="var(--brand)"/><path d="<?= $heart ?>" fill="var(--bg)"/></g>
    <?= $spark(168, 36, 1) ?><?= $spark(30, 120, .7, 'var(--brand)') ?>
<?php endif; ?>
</svg>
