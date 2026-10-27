<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Health</h1><p>Server capabilities the app depends on.</p></div></div>
<div class="grid-side">
    <div class="panel"><div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Check</th><th>Value</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($checks as [$label, $value, $ok]): ?>
            <tr><td><b><?= e($label) ?></b></td><td><?= e($value) ?></td><td class="num"><?= $ok ? '<span class="tag tag-ok">OK</span>' : '<span class="tag tag-warn">Check</span>' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <div class="panel">
        <div class="panel-h"><h2>Database migrations</h2></div>
        <div class="panel-b">
            <?php foreach ($migrations as $m): ?><div style="padding:6px 0;border-bottom:1px solid var(--line)"><div class="mono"><?= e($m['name']) ?></div><small class="muted">Ran <?= date('j M Y, g:i A', strtotime($m['ran_at'])) ?></small></div><?php endforeach; ?>
        </div>
    </div>
</div>
<div class="panel" style="margin-top:18px">
    <div class="panel-h"><h2>Recent errors</h2><span class="muted small">Unexpected server errors from the last 30 days. Empty is good.</span></div>
    <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>When</th><th>Error</th><th>Where</th><th>Request</th></tr></thead>
        <tbody>
        <?php foreach ($errors as $er): ?>
            <tr>
                <td class="muted" style="white-space:nowrap"><?= date('j M, g:i A', strtotime($er['created_at'])) ?></td>
                <td style="max-width:460px"><?= e($er['message']) ?></td>
                <td class="mono muted"><?= e($er['location']) ?></td>
                <td class="mono muted"><?= e($er['url']) ?><?= $er['user_id'] ? ' · user #' . (int) $er['user_id'] : '' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$errors): ?><tr><td colspan="4" class="muted" style="padding:22px;text-align:center">No errors recorded.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
