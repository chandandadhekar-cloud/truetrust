<?php defined('APP_ROOT') || exit; ?>
<template id="tpl-report">
    <div class="sheet-grip"></div>
    <h2>Report <span data-report-name></span></h2>
    <p class="sheet-sub">Reports are private. We'll also block them so you won't see each other again.</p>
    <form data-report-form>
        <input type="hidden" name="id">
        <div class="choices" style="margin-bottom:16px">
            <?php foreach (opt('report_reasons') as $k => $label): ?>
                <label class="choice"><input type="radio" name="reason" value="<?= $k ?>" required><span class="choice-body" style="padding:13px 16px;font-size:14.5px"><?= e($label) ?></span></label>
            <?php endforeach; ?>
        </div>
        <label class="field">
            <span>Anything else we should know? (optional)</span>
            <textarea class="textarea" name="details" maxlength="2000" style="min-height:90px"></textarea>
        </label>
        <button class="btn btn-primary btn-block" type="submit">Send report</button>
    </form>
</template>
