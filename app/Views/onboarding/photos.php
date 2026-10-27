<?php defined('APP_ROOT') || exit; ?>
<form class="ob" method="post" action="/onboarding/photos" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">Show yourself</p>
        <h1 class="ob-title">Add your best photos</h1>
        <p class="ob-sub">Profiles with 3 or more clear photos get far more matches. Your first photo is your cover.</p>
        <?= partial('partials/photo-grid', ['photos' => $photos, 'max' => (int) setting('max_photos')]) ?>
        <?= error_for('photos') ?>
        <div class="notice" style="margin-top:18px"><?= icon('shield', 20) ?><span>Use photos of just you. We remove location data from every upload.</span></div>
    </div>
    <div class="ob-foot"><button class="btn btn-primary btn-block" type="submit" data-photos-next<?= $photos ? '' : ' disabled' ?>>Next</button></div>
</form>
