<?php defined('APP_ROOT') || exit;
$dob = $me['dob'] ? explode('-', $me['dob']) : ['', '', ''];
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$maxYear = (int) date('Y') - max(18, (int) setting('min_age'));
?>
<form class="ob" method="post" action="/onboarding/basics" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">Let's start</p>
        <h1 class="ob-title">What should we call you?</h1>
        <p class="ob-sub">Only your first name is shown on your profile.</p>
        <label class="field">
            <span>First name</span>
            <input class="input" name="first_name" value="<?= e(old('first_name', $me['first_name'])) ?>" maxlength="30" autocomplete="given-name" autocapitalize="words" required autofocus>
            <?= error_for('first_name') ?>
        </label>
        <div class="field">
            <span class="label">Birthday</span>
            <div class="dob-row">
                <select class="select" name="dob_day" required aria-label="Day">
                    <option value="">Day</option>
                    <?php for ($i = 1; $i <= 31; $i++): ?><option value="<?= $i ?>"<?= selected(old('dob_day', (int) $dob[2]), $i) ?>><?= $i ?></option><?php endfor; ?>
                </select>
                <select class="select" name="dob_month" required aria-label="Month">
                    <option value="">Month</option>
                    <?php foreach ($months as $i => $mn): ?><option value="<?= $i + 1 ?>"<?= selected(old('dob_month', (int) $dob[1]), $i + 1) ?>><?= $mn ?></option><?php endforeach; ?>
                </select>
                <select class="select" name="dob_year" required aria-label="Year">
                    <option value="">Year</option>
                    <?php for ($y = $maxYear; $y >= $maxYear - 62; $y--): ?><option value="<?= $y ?>"<?= selected(old('dob_year', (int) $dob[0]), $y) ?>><?= $y ?></option><?php endfor; ?>
                </select>
            </div>
            <?= error_for('dob') ?: '<p class="hint">Your age is shown, never your birthday.</p>' ?>
        </div>
    </div>
    <div class="ob-foot"><button class="btn btn-primary btn-block" type="submit">Next</button></div>
</form>
