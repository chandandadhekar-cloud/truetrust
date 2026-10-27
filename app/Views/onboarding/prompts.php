<?php defined('APP_ROOT') || exit; ?>
<form class="ob" method="post" action="/onboarding/prompts" data-submit-once>
    <?= csrf_field() ?>
    <?= partial('onboarding/_top', get_defined_vars()) ?>
    <div class="ob-body">
        <p class="ob-step">Last step</p>
        <h1 class="ob-title">Give them something to reply to</h1>
        <p class="ob-sub">Answer up to three prompts. Good answers start the best conversations. You can skip this.</p>
        <?= partial('partials/prompt-fields', ['prompts' => App\Models\User::prompts($me)]) ?>
    </div>
    <div class="ob-foot">
        <button class="btn btn-outline" type="submit" name="skip" value="1" style="flex:none">Skip</button>
        <button class="btn btn-primary btn-block" type="submit">Finish</button>
    </div>
</form>
