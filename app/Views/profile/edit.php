<?php defined('APP_ROOT') || exit;
$interests = App\Models\User::interests($me);
$langs = App\Models\User::languages($me);
$list = cities();
usort($list, static fn ($a, $b) => strcmp($a[0], $b[0]));
$sel = static function (string $name, array $opts, string $current, string $placeholder = 'Prefer not to say') {
    $html = '<select class="select" name="' . $name . '"><option value="">' . e($placeholder) . '</option>';
    foreach ($opts as $k => $label) {
        $key = is_int($k) ? $label : $k;
        $html .= '<option value="' . e($key) . '"' . selected($current, $key) . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
};
?>
<form method="post" action="/profile/edit" data-submit-once>
    <?= csrf_field() ?>
    <header class="appbar"><div class="appbar-inner">
        <a href="/profile" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
        <span class="title-sm">Edit profile</span>
        <span class="spacer"></span>
        <button class="btn btn-primary btn-sm" type="submit">Save</button>
    </div></header>
    <div class="page">
        <p class="group-title" style="margin-top:6px">Photos</p>
        <?= partial('partials/photo-grid', ['photos' => $photos, 'max' => (int) setting('max_photos')]) ?>
        <p class="hint">Tap the star to make a photo your cover. <?= setting('photo_moderation') === '1' ? 'New photos are reviewed before others can see them.' : '' ?></p>

        <p class="group-title">About you</p>
        <label class="field">
            <span>Bio <span class="counter" data-counter-for="bio">0/500</span></span>
            <textarea class="textarea" name="bio" id="bio" maxlength="500"><?= e($me['bio']) ?></textarea>
            <?= error_for('bio') ?>
        </label>

        <p class="group-title">Prompts</p>
        <?= partial('partials/prompt-fields', ['prompts' => App\Models\User::prompts($me)]) ?>

        <p class="group-title">Work & education</p>
        <div class="form-row">
            <label class="field"><span>Job title</span><input class="input" name="job_title" value="<?= e($me['job_title']) ?>" maxlength="100"></label>
            <label class="field"><span>Company</span><input class="input" name="company" value="<?= e($me['company']) ?>" maxlength="100"></label>
        </div>
        <label class="field"><span>Education</span><input class="input" name="education" value="<?= e($me['education']) ?>" maxlength="120" placeholder="e.g. B.Tech, Computer Science"></label>

        <p class="group-title">Basics</p>
        <label class="field"><span>Looking for</span><?= $sel('looking_for', opt('looking_for'), $me['looking_for'], 'Choose') ?></label>
        <label class="field"><span>City</span>
            <select class="select" name="city"><?php foreach ($list as $c): ?><option value="<?= e($c[0]) ?>"<?= selected($me['city'], $c[0]) ?>><?= e($c[0]) ?>, <?= e($c[1]) ?></option><?php endforeach; ?></select>
            <?= error_for('city') ?>
        </label>
        <div class="form-row">
            <label class="field"><span>Height (cm)</span><input class="input" name="height_cm" type="number" inputmode="numeric" min="120" max="230" value="<?= e($me['height_cm']) ?>"><?= error_for('height_cm') ?></label>
            <label class="field"><span>Religion</span><?= $sel('religion', opt('religion'), $me['religion']) ?></label>
        </div>
        <div class="form-row">
            <label class="field"><span>Drinking</span><?= $sel('drinking', opt('drinking'), $me['drinking']) ?></label>
            <label class="field"><span>Smoking</span><?= $sel('smoking', opt('smoking'), $me['smoking']) ?></label>
        </div>
        <div class="form-row">
            <label class="field"><span>Diet</span><?= $sel('diet', opt('diet'), $me['diet']) ?></label>
            <label class="field"><span>Mother tongue</span><?= $sel('mother_tongue', opt('languages'), $me['mother_tongue']) ?></label>
        </div>
        <div class="field">
            <span class="label">Languages I speak</span>
            <div class="chips">
                <?php foreach (opt('languages') as $l): ?><label class="chip"><input type="checkbox" name="languages[]" value="<?= e($l) ?>"<?= checked(in_array($l, $langs, true)) ?>><?= e($l) ?></label><?php endforeach; ?>
            </div>
        </div>

        <p class="group-title">Interests <span class="counter" data-chip-count style="float:none;margin-left:6px">0/8</span></p>
        <div class="chips" data-chip-limit="8">
            <?php foreach (opt('interests') as $int): ?><label class="chip"><input type="checkbox" name="interests[]" value="<?= e($int) ?>"<?= checked(in_array($int, $interests, true)) ?>><?= e($int) ?></label><?php endforeach; ?>
        </div>
        <?= error_for('interests') ?>
        <button class="btn btn-primary btn-block" type="submit" style="margin-top:28px">Save profile</button>
    </div>
</form>
