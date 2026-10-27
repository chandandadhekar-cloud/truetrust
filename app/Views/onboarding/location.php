<?php defined('APP_ROOT') || exit;
$current = old('city', $me['city']);
$list = cities();
usort($list, static fn ($a, $b) => strcmp($a[0], $b[0]));
?>
<form class="ob" method="post" action="/onboarding/location" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">Almost there</p>
        <h1 class="ob-title">Where are you based?</h1>
        <p class="ob-sub">We show people your city, never your exact location.</p>
        <button type="button" class="btn btn-outline btn-block" data-locate><?= icon('crosshair', 20) ?> Use my current location</button>
        <div class="divider">or pick your city</div>
        <div class="input-group">
            <input class="input" type="search" placeholder="Search cities" data-city-search autocomplete="off">
        </div>
        <input type="hidden" name="city" value="<?= e($current) ?>" data-city-value>
        <div class="city-list" data-city-list>
            <?php foreach ($list as $c): ?>
                <div class="city-opt<?= $current === $c[0] ? ' is-on' : '' ?>" data-city="<?= e($c[0]) ?>" data-lat="<?= $c[2] ?>" data-lng="<?= $c[3] ?>" role="button" tabindex="0">
                    <span><?= e($c[0]) ?></span><small><?= e($c[1]) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <?= error_for('city') ?>
    </div>
    <div class="ob-foot"><button class="btn btn-primary btn-block" type="submit" data-city-next<?= $current ? '' : ' disabled' ?>>Next</button></div>
</form>
