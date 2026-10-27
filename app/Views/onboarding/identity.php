<?php defined('APP_ROOT') || exit;
$g = old('gender', $me['gender']);
$i = old('interested_in', $me['interested_in']);
?>
<form class="ob" method="post" action="/onboarding/identity" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">Nice to meet you, <?= e($me['first_name']) ?></p>
        <h1 class="ob-title">How do you identify?</h1>
        <div class="choices" style="margin-bottom:8px">
            <?php foreach (opt('gender') as $k => $label): ?>
                <label class="choice"><input type="radio" name="gender" value="<?= $k ?>"<?= checked($g === $k) ?> required><span class="choice-body"><?= e($label) ?></span></label>
            <?php endforeach; ?>
        </div>
        <?= error_for('gender') ?>
        <h2 class="ob-title" style="font-size:26px;margin-top:30px">Who would you like to meet?</h2>
        <div class="choices">
            <?php foreach (opt('interested_in') as $k => $label): ?>
                <label class="choice"><input type="radio" name="interested_in" value="<?= $k ?>"<?= checked($i === $k) ?> required><span class="choice-body"><?= e($label) ?></span></label>
            <?php endforeach; ?>
        </div>
        <?= error_for('interested_in') ?>
    </div>
    <div class="ob-foot"><button class="btn btn-primary btn-block" type="submit">Next</button></div>
</form>
