<?php defined('APP_ROOT') || exit;
$suggest = ['A perfect Sunday looks like', 'My go-to chai order', 'First date idea'];
?>
<?php for ($i = 0; $i < 3; $i++): $p = $prompts[$i] ?? ['q' => $suggest[$i], 'a' => '']; ?>
    <div class="prompt-card" style="margin:0 0 12px">
        <select class="select" name="prompt_q[]" style="min-height:44px;padding-top:9px;padding-bottom:9px;font-weight:700;font-size:14px;margin-bottom:10px">
            <?php foreach (opt('prompts') as $q): ?><option<?= selected($p['q'], $q) ?>><?= e($q) ?></option><?php endforeach; ?>
        </select>
        <textarea class="textarea" name="prompt_a[]" maxlength="160" placeholder="Your answer" style="min-height:80px"><?= e($p['a']) ?></textarea>
    </div>
<?php endfor; ?>
