<?php defined('APP_ROOT') || exit;
$looking = old('looking_for', $me['looking_for']);
$mine = old('interests', App\Models\User::interests($me)) ?: [];
?>
<form class="ob" method="post" action="/onboarding/about" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">The good stuff</p>
        <h1 class="ob-title">What are you looking for?</h1>
        <div class="choices">
            <?php foreach (opt('looking_for') as $k => $label): ?>
                <label class="choice"><input type="radio" name="looking_for" value="<?= $k ?>"<?= checked($looking === $k) ?> required><span class="choice-body"><?= e($label) ?></span></label>
            <?php endforeach; ?>
        </div>
        <?= error_for('looking_for') ?>

        <label class="field" style="margin-top:28px">
            <span>About you <span class="counter" data-counter-for="bio">0/500</span></span>
            <textarea class="textarea" name="bio" id="bio" maxlength="500" placeholder="A few lines about what makes you, you."><?= e(old('bio', $me['bio'])) ?></textarea>
            <?= error_for('bio') ?>
        </label>

        <div class="field">
            <span class="label">Interests <span class="counter" data-chip-count>0/8</span></span>
            <p class="hint" style="margin:-2px 0 12px">Pick 3 to 8. They help start conversations.</p>
            <div class="chips" data-chip-limit="8">
                <?php foreach (opt('interests') as $int): ?>
                    <label class="chip"><input type="checkbox" name="interests[]" value="<?= e($int) ?>"<?= checked(in_array($int, $mine, true)) ?>><?= e($int) ?></label>
                <?php endforeach; ?>
            </div>
            <?= error_for('interests') ?>
        </div>
    </div>
    <div class="ob-foot"><button class="btn btn-primary btn-block" type="submit">Next</button></div>
</form>
