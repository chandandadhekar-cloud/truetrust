<?php defined('APP_ROOT') || exit;
$distances = [0 => 'Anywhere in India', 10 => '10 km', 25 => '25 km', 50 => '50 km', 100 => '100 km', 250 => '250 km', 500 => '500 km'];
?>
<div class="discover">
    <header class="appbar"><div class="appbar-inner">
        <a href="/discover" class="brand"><?= partial('partials/logo') ?><span class="brand-word"><?= e(setting('site_name')) ?></span></a>
        <span class="spacer"></span>
        <?php if ($likes > 0): ?>
            <a href="/likes" class="chip chip-brand" style="margin-right:4px"><?= icon('heart', 15) ?> <?= $likes ?> new</a>
        <?php endif; ?>
        <button class="icon-btn" data-open-filters aria-label="Filters"><?= icon('sliders', 22) ?></button>
    </div></header>

    <div class="deck-wrap">
        <div class="deck" id="deck"
             data-superlikes="<?= $remaining['superlikes'] === null ? '' : (int) $remaining['superlikes'] ?>"
             data-likes="<?= $remaining['likes'] === null ? '' : (int) $remaining['likes'] ?>">
            <div class="deck-loading" data-deck-loading><div class="pulse"><?= icon('heart', 34) ?></div></div>
            <div class="deck-empty empty hidden" data-deck-empty>
                <div class="empty-art"><?= icon('search', 40) ?></div>
                <h2>You're all caught up</h2>
                <p>You've seen everyone who matches your filters for now. Widen your age range or distance to meet more people.</p>
                <button class="btn btn-dark btn-sm" data-open-filters style="margin-top:8px"><?= icon('sliders', 18) ?> Adjust filters</button>
                <button class="btn btn-ghost btn-sm" data-deck-reload><?= icon('refresh', 18) ?> Check again</button>
            </div>
        </div>
        <div class="deck-actions" data-deck-actions>
            <button class="act act-sm act-rewind" data-act="rewind" aria-label="Undo last pass" title="Undo last pass"><?= icon('undo', 22) ?></button>
            <button class="act act-lg act-pass" data-act="pass" aria-label="Pass"><?= icon('x', 30) ?></button>
            <button class="act act-sm act-super" data-act="superlike" aria-label="Super Like" title="Super Like"><?= icon('star', 22) ?></button>
            <button class="act act-lg act-like" data-act="like" aria-label="Like"><?= icon('heart', 30) ?></button>
        </div>
    </div>
</div>

<template id="tpl-filters">
    <div class="sheet-grip"></div>
    <h2>Filters</h2>
    <p class="sheet-sub">Who you see in Discover.</p>
    <form data-filters-form>
        <div class="field">
            <span class="label">Show me</span>
            <div class="chips">
                <?php foreach (opt('interested_in') as $k => $label): ?>
                    <label class="chip"><input type="radio" name="interested_in" value="<?= $k ?>"<?= checked($me['interested_in'] === $k) ?>><?= e($label) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="field">
            <span class="label row">Age <span class="spacer"></span><span class="range-value" data-age-label><?= (int) $me['pref_age_min'] ?>–<?= (int) $me['pref_age_max'] ?></span></span>
            <div class="range-wrap" data-dual-range>
                <div class="range-track"></div><div class="range-fill"></div>
                <input type="range" name="age_min" min="18" max="70" value="<?= (int) $me['pref_age_min'] ?>" aria-label="Minimum age">
                <input type="range" name="age_max" min="18" max="70" value="<?= (int) $me['pref_age_max'] ?>" aria-label="Maximum age">
            </div>
        </div>
        <label class="field">
            <span>Distance</span>
            <select class="select" name="distance">
                <?php foreach ($distances as $km => $label): ?><option value="<?= $km ?>"<?= selected($me['pref_distance_km'], $km) ?>><?= $label ?></option><?php endforeach; ?>
            </select>
            <p class="hint">Measured from <?= e($me['city']) ?>. Change your city in Settings.</p>
        </label>
        <button class="btn btn-primary btn-block" type="submit">Show people</button>
    </form>
</template>

<?= partial('partials/report-sheet') ?>
